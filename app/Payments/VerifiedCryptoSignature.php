<?php

namespace App\Payments;

use Illuminate\Support\Carbon;

/**
 * Verifies the HMAC signature on inbound VERIFIED relay callbacks.
 *
 * Implements Section 6 of the partner API guide exactly:
 *   message   = X-VCC-Timestamp . "." . raw_request_body
 *   signature = lowercase hex HMAC-SHA256(message, webhook_secret)
 * compared in constant time, with X-VCC-Timestamp rejected when more than
 * 300 seconds from the current time. The raw body is used as received —
 * re-serialising it changes the bytes and breaks verification.
 *
 * Callbacks are the authoritative payment signal, so an order is only ever
 * marked paid off the back of a callback that passes this check.
 */
class VerifiedCryptoSignature
{
    public function __construct(protected ?string $secret = null) {}

    /**
     * Whether signature checking is active. Without a configured secret there
     * is nothing to verify against, and callbacks are refused outright rather
     * than trusted — an unauthenticated callback can mark any order paid.
     */
    public function isConfigured(): bool
    {
        return is_string($this->secret) && $this->secret !== '';
    }

    public function verify(string $rawBody, ?string $timestamp, ?string $signature): bool
    {
        if (! $this->isConfigured() || ! is_string($timestamp) || ! is_string($signature)) {
            return false;
        }

        if (! $this->timestampIsFresh($timestamp)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, (string) $this->secret);

        return hash_equals($expected, strtolower(trim($signature)));
    }

    /**
     * Reject stale timestamps so a captured callback cannot be replayed.
     */
    protected function timestampIsFresh(string $timestamp): bool
    {
        $tolerance = (int) config('verified-crypto.signature_tolerance', 300);

        try {
            $sentAt = is_numeric($timestamp)
                ? Carbon::createFromTimestamp((int) $timestamp)
                : Carbon::parse($timestamp);
        } catch (\Throwable) {
            return false;
        }

        return $sentAt->diffInSeconds(Carbon::now(), absolute: true) <= $tolerance;
    }
}
