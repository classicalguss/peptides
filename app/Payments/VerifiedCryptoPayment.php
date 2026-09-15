<?php

namespace App\Payments;

use Illuminate\Support\Facades\DB;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;

/**
 * Lunar payment driver for VERIFIED Crypto Checkout.
 *
 * Authorization is asynchronous: the customer pays on a VERIFIED-hosted page,
 * the on-ramp settles USDC on Polygon, and only then does a relay callback
 * reach us. So {@see self::authorize()} is driven by the webhook rather than
 * by the checkout request, and records the on-chain transaction hash against
 * the order.
 */
class VerifiedCryptoPayment extends AbstractPayment
{
    public const DRIVER = 'verified-crypto';

    /**
     * Cents of slack allowed between the settled amount and the order total,
     * to absorb rounding between the decimal the relay sends and our integers.
     */
    public const AMOUNT_TOLERANCE = 2;

    /**
     * Mark the order paid against a confirmed on-chain settlement.
     *
     * Expects `withData(['tx_hash' => ..., 'session_id' => ...])`. Callbacks
     * are retried by VERIFIED, so this is idempotent: a repeated callback for
     * a transaction hash we have already recorded succeeds without writing a
     * second transaction or re-placing the order.
     */
    public function authorize(): ?PaymentAuthorize
    {
        if (! $this->order) {
            return new PaymentAuthorize(
                success: false,
                message: 'No order supplied to the VERIFIED payment driver.',
                paymentType: self::DRIVER,
            );
        }

        $reference = (string) ($this->data['tx_hash'] ?? '');

        if ($reference === '') {
            return new PaymentAuthorize(
                success: false,
                orderId: $this->order->id,
                message: 'Callback did not include an on-chain transaction hash.',
                paymentType: self::DRIVER,
            );
        }

        $paid = $this->paidAmountInCents();

        if ($paid !== null && $paid + self::AMOUNT_TOLERANCE < $this->order->total->value) {
            return new PaymentAuthorize(
                success: false,
                orderId: $this->order->id,
                message: "Callback settled {$paid} but the order total is {$this->order->total->value}.",
                paymentType: self::DRIVER,
            );
        }

        $existing = Transaction::where('order_id', $this->order->id)
            ->where('reference', $reference)
            ->where('success', true)
            ->first();

        if ($existing) {
            return new PaymentAuthorize(
                success: true,
                orderId: $this->order->id,
                paymentType: self::DRIVER,
            );
        }

        DB::transaction(function () use ($reference, $paid) {
            Transaction::create([
                'order_id' => $this->order->id,
                'success' => true,
                'type' => 'capture',
                'driver' => self::DRIVER,
                'amount' => $paid ?? $this->order->total->value,
                'reference' => $reference,
                'status' => 'settled',
                'notes' => $this->settlementNote(),
                'card_type' => substr(strtolower((string) ($this->data['coin'] ?? 'usdc')) ?: 'usdc', 0, 25),
                'last_four' => null,
                'captured_at' => now(),
                'meta' => array_filter([
                    'session_id' => $this->data['session_id'] ?? null,
                    'tx_hash' => $reference,
                    'order_total' => $this->order->total->value,
                    'settled_amount' => $paid,
                    // Coin amount that reached the wallet, per the relay. The live
                    // relay sends value_coin; the guide documents value_forwarded_coin.
                    'value_coin' => $this->data['value_coin'] ?? null,
                    'value_forwarded_coin' => $this->data['value_forwarded_coin'] ?? null,
                    'coin' => $this->data['coin'] ?? null,
                ], fn ($value) => $value !== null),
            ]);

            $this->order->update([
                'status' => $this->config['authorized'] ?? 'payment-received',
                'placed_at' => $this->order->placed_at ?? now(),
                'meta' => array_merge((array) $this->order->meta, [
                    'verified_crypto' => array_merge(
                        (array) (((array) $this->order->meta)['verified_crypto'] ?? []),
                        ['tx_hash' => $reference, 'settled_at' => now()->toIso8601String()],
                    ),
                ]),
            ]);
        });

        $response = new PaymentAuthorize(
            success: true,
            orderId: $this->order->id,
            paymentType: self::DRIVER,
        );

        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    /**
     * Human-readable settlement note. The provider route decides the coin and
     * network — Banxa settles ETH on Ethereum, Stripe USDC on Polygon — so the
     * note names what actually arrived rather than assuming USDC.
     */
    protected function settlementNote(): string
    {
        $coin = strtoupper((string) ($this->data['coin'] ?? ''));
        $value = $this->data['value_coin'] ?? null;

        if ($coin === '') {
            return 'Settled on-chain via VERIFIED.';
        }

        $network = match (true) {
            str_contains(strtolower($coin), 'polygon') || $coin === 'POL' => 'Polygon',
            $coin === 'ETH' => 'Ethereum',
            default => null,
        };

        return trim(sprintf('Settled as %s%s%s.', $value !== null ? "{$value} " : '', $coin, $network ? " on {$network}" : ''));
    }

    /**
     * The amount the relay says was settled, in minor units.
     *
     * Returns null when the callback carries no amount, in which case the
     * order total is trusted — there is nothing to compare against.
     */
    protected function paidAmountInCents(): ?int
    {
        $amount = $this->data['amount'] ?? null;

        if ($amount === null || ! is_numeric($amount)) {
            return null;
        }

        return (int) round(((float) $amount) * 100);
    }

    /**
     * On-chain settlement is final — USDC cannot be clawed back by us, so a
     * refund has to be sent manually from the settlement wallet.
     */
    public function refund(TransactionContract $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        return new PaymentRefund(
            success: false,
            message: 'USDC settlements are irreversible and must be refunded manually from the settlement wallet.',
        );
    }

    /**
     * Settlement is captured at authorization time; there is no separate
     * capture step in the hosted flow.
     */
    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        return new PaymentCapture(true);
    }
}
