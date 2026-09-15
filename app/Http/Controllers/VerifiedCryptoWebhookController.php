<?php

namespace App\Http\Controllers;

use App\Payments\VerifiedCryptoPayment;
use App\Payments\VerifiedCryptoSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Payments;
use Lunar\Models\Order;

/**
 * Receives VERIFIED relay callbacks.
 *
 * VERIFIED's own guidance is that the post-payment browser redirect is
 * best-effort and the callback is the authoritative payment signal, so this is
 * the only path that marks an order paid.
 */
class VerifiedCryptoWebhookController extends Controller
{
    /**
     * Status values accepted as a confirmed settlement when a callback carries
     * no `event` field. The documented event is `payment.confirmed`.
     */
    protected const SETTLED_STATUSES = ['success', 'completed', 'complete', 'settled', 'paid', 'confirmed'];

    public function __invoke(Request $request, VerifiedCryptoSignature $signature): JsonResponse
    {
        if (! config('verified-crypto.enabled')) {
            return response()->json(['ok' => false, 'error' => 'disabled'], 404);
        }

        if (config('verified-crypto.log_callbacks')) {
            Log::debug('VERIFIED raw callback.', [
                'body' => $request->getContent(),
                'timestamp' => $request->header('X-VCC-Timestamp'),
                'signature' => $request->header('X-VCC-Signature'),
                'headers' => array_diff_key($request->headers->all(), array_flip(['cookie', 'authorization'])),
            ]);
        }

        if (! $signature->verify(
            $request->getContent(),
            $request->header('X-VCC-Timestamp'),
            $request->header('X-VCC-Signature'),
        )) {
            Log::warning('Rejected VERIFIED callback with an invalid signature.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        $payload = $request->json()->all();
        $reference = $payload['order_id'] ?? null;
        $callbackSession = $payload['session_id'] ?? null;

        // The guide says order_id is always echoed back; VERIFIED's own
        // description of the live payload lists only event, amount, coin,
        // value_coin and tx_hash. Fall back to the session_id stored on the
        // order at creation so a confirmation without order_id still lands.
        $order = null;

        if (is_string($reference) && $reference !== '') {
            $order = Order::where('reference', $reference)->first();
        } elseif (is_string($callbackSession) && $callbackSession !== '') {
            $order = Order::where('meta->verified_crypto->session_id', $callbackSession)->first();
            $reference = $order?->reference ?? $callbackSession;
        }

        if (! is_string($reference) || $reference === '') {
            return response()->json(['ok' => false, 'error' => 'missing order_id and session_id'], 422);
        }

        if (! $order) {
            Log::warning('VERIFIED callback referenced an unknown order.', [
                'order_reference' => $reference,
            ]);

            return response()->json(['ok' => false, 'error' => 'unknown order'], 404);
        }

        $expectedSession = ((array) $order->meta)['verified_crypto']['session_id'] ?? null;

        if (is_string($expectedSession) && is_string($callbackSession) && ! hash_equals($expectedSession, $callbackSession)) {
            Log::warning('VERIFIED callback session_id does not match the order.', [
                'order_reference' => $reference,
                'expected' => $expectedSession,
                'received' => $callbackSession,
            ]);

            return response()->json(['ok' => false, 'error' => 'session mismatch'], 422);
        }

        $event = strtolower((string) ($payload['event'] ?? ''));
        $status = strtolower((string) ($payload['status'] ?? ''));

        // The guide's documented settlement event is payment.confirmed. If a
        // relay omits `event`, fall back to the status field so a documented
        // status alone can still settle the order.
        $isConfirmed = $event === 'payment.confirmed'
            || ($event === '' && in_array($status, self::SETTLED_STATUSES, true));

        if (! $isConfirmed) {
            Log::info('VERIFIED callback received for an unsettled payment.', [
                'order_reference' => $reference,
                'event' => $event,
                'status' => $status,
            ]);

            return response()->json(['ok' => true, 'handled' => false]);
        }

        $response = Payments::driver(VerifiedCryptoPayment::DRIVER)
            ->order($order)
            ->withData([
                'tx_hash' => $payload['tx_hash'] ?? $payload['txid_out'] ?? null,
                'session_id' => $payload['session_id'] ?? null,
                'amount' => $payload['amount'] ?? null,
                'value_coin' => $payload['value_coin'] ?? null,
                'value_forwarded_coin' => $payload['value_forwarded_coin'] ?? null,
                'coin' => $payload['coin'] ?? null,
            ])
            ->authorize();

        if (! $response?->success) {
            Log::error('VERIFIED callback could not be authorized.', [
                'order_reference' => $reference,
                'message' => $response?->message,
            ]);

            return response()->json(['ok' => false, 'error' => 'authorization failed'], 422);
        }

        return response()->json(['ok' => true, 'handled' => true]);
    }
}
