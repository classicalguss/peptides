<?php

namespace App\Payments;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Verifies the HMAC signature on inbound VERIFIED relay callbacks.
 *
 * Callbacks are the authoritative payment signal — the browser redirect after
 * payment is explicitly best-effort — so an order is only ever marked paid off
 * the back of a callback that passes this check.
 *
 * NOTE: VERIFIED's docs state the callback carries X-VCC-Timestamp and
 * X-VCC-Signature (HMAC-SHA256, keyed on the webhook_secret sent at session
 * creation) but do not publish the exact signing base string. This implements
 * the conventional "{timestamp}.{raw body}" construction. Confirm it against a
 * real callback during the first live test — if it does not match, only
 * {@see self::payload()} needs to change.
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

        $provided = strtolower(trim($signature));

        foreach ($this->candidates($rawBody, $timestamp) as $label => $payload) {
            $expected = hash_hmac('sha256', $payload, (string) $this->secret);

            if (hash_equals($expected, $provided)) {
                Log::info('VERIFIED callback signature verified.', ['format' => $label]);

                return true;
            }

            if (hash_equals(base64_encode(hash_hmac('sha256', $payload, (string) $this->secret, true)), trim($signature))) {
                Log::info('VERIFIED callback signature verified.', ['format' => $label.'+base64']);

                return true;
            }
        }

        return false;
    }

    /**
     * Plausible constructions of the signed string, keyed by a label that is
     * logged on a match so the format can be pinned down afterwards.
     *
     * @return array<string, string>
     */
    protected function candidates(string $rawBody, string $timestamp): array
    {
        return [
            'timestamp.body' => $timestamp.'.'.$rawBody,
            'body' => $rawBody,
            'timestamp+body' => $timestamp.$rawBody,
            'body+timestamp' => $rawBody.$timestamp,
        ];
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
