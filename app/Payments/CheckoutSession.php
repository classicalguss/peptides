<?php

namespace App\Payments;

use Illuminate\Support\Carbon;

/**
 * A payment session returned by the VERIFIED partner API.
 */
class CheckoutSession
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $checkoutUrl,
        public readonly ?string $routingMode = null,
        public readonly ?Carbon $expiresAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        $sessionId = $payload['session_id'] ?? null;
        $checkoutUrl = $payload['checkout_url'] ?? null;

        if (! is_string($sessionId) || $sessionId === '' || ! is_string($checkoutUrl) || $checkoutUrl === '') {
            throw new VerifiedCryptoException('Session response is missing session_id or checkout_url.');
        }

        return new self(
            sessionId: $sessionId,
            checkoutUrl: $checkoutUrl,
            routingMode: $payload['routing_mode'] ?? null,
            expiresAt: isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toMeta(): array
    {
        return [
            'session_id' => $this->sessionId,
            'checkout_url' => $this->checkoutUrl,
            'routing_mode' => $this->routingMode,
            'expires_at' => $this->expiresAt?->toIso8601String(),
        ];
    }
}
