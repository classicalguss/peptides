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
     * Statuses the relay uses to indicate a confirmed on-chain settlement.
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

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 403);
        }

        $payload = $request->json()->all();
        $reference = $payload['order_id'] ?? null;

        if (! is_string($reference) || $reference === '') {
            return response()->json(['ok' => false, 'error' => 'missing order_id'], 422);
        }

        $order = Order::where('reference', $reference)->first();

        if (! $order) {
            Log::warning('VERIFIED callback referenced an unknown order.', [
                'order_reference' => $reference,
            ]);

            return response()->json(['ok' => false, 'error' => 'unknown order'], 404);
        }

        $status = strtolower((string) ($payload['status'] ?? ''));

        if (! in_array($status, self::SETTLED_STATUSES, true)) {
            Log::info('VERIFIED callback received for an unsettled payment.', [
                'order_reference' => $reference,
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
