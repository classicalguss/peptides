<?php

namespace App\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Contracts\Order as OrderContract;

/**
 * Thin client over the VERIFIED Crypto Checkout partner API.
 *
 * The API is unauthenticated by design — there is no key to send. The wallet
 * address in each request is what identifies us and determines where USDC
 * settles, so it is validated before any request leaves the app.
 */
class VerifiedCryptoClient
{
    /**
     * Create a hosted payment session for an order.
     *
     * The returned checkout URL is opaque and must be handed to the customer
     * exactly as received — VERIFIED builds provider routing into it and
     * rebuilding or re-encoding it breaks the payment.
     */
    public function createSession(OrderContract $order, string $email, string $callbackUrl): CheckoutSession
    {
        $payload = array_filter([
            'partner_id' => $this->partnerId(),
            'address' => $this->walletAddress(),
            'amount' => (float) $order->total->decimal,
            'currency' => $order->currency_code,
            'email' => $email,
            'callback' => $callbackUrl,
            'order_id' => $order->reference,
            'provider' => config('verified-crypto.provider'),
            'webhook_secret' => config('verified-crypto.webhook_secret'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout((int) config('verified-crypto.timeout', 20))
                ->post($this->endpoint('/v1/partner-session'), $payload);
        } catch (ConnectionException $e) {
            throw new VerifiedCryptoException('Could not reach the VERIFIED partner API.', 0, $e);
        }

        if ($response->failed()) {
            Log::error('VERIFIED session creation failed.', [
                'order_reference' => $order->reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new VerifiedCryptoException("VERIFIED partner API returned HTTP {$response->status()}.");
        }

        $body = $response->json();

        if (! is_array($body) || ($body['ok'] ?? null) !== true) {
            Log::error('VERIFIED session creation rejected.', [
                'order_reference' => $order->reference,
                'body' => $response->body(),
            ]);

            throw new VerifiedCryptoException('VERIFIED partner API rejected the session request.');
        }

        return CheckoutSession::fromResponse($body);
    }

    /**
     * The configured settlement address, validated as an EVM address.
     *
     * USDC sent to a malformed or wrong address is unrecoverable, so a bad
     * value fails loudly here rather than silently routing real money away.
     */
    protected function walletAddress(): string
    {
        $address = (string) config('verified-crypto.wallet_address');

        if (! preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
            throw new VerifiedCryptoException(
                'VERIFIED_CRYPTO_WALLET_ADDRESS is missing or is not a valid 0x-prefixed Polygon address.'
            );
        }

        return $address;
    }

    protected function partnerId(): string
    {
        $partnerId = (string) config('verified-crypto.partner_id');

        if ($partnerId === '') {
            throw new VerifiedCryptoException('VERIFIED_CRYPTO_PARTNER_ID is not configured.');
        }

        return $partnerId;
    }

    protected function endpoint(string $path): string
    {
        return rtrim((string) config('verified-crypto.api_base'), '/').$path;
    }
}
