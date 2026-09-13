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

        DB::transaction(function () use ($reference) {
            Transaction::create([
                'order_id' => $this->order->id,
                'success' => true,
                'type' => 'capture',
                'driver' => self::DRIVER,
                'amount' => $this->order->total->value,
                'reference' => $reference,
                'status' => 'settled',
                'notes' => 'USDC settled on Polygon.',
                'card_type' => 'usdc',
                'last_four' => null,
                'captured_at' => now(),
                'meta' => [
                    'session_id' => $this->data['session_id'] ?? null,
                    'tx_hash' => $reference,
                ],
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
