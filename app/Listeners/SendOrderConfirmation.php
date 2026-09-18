<?php

namespace App\Listeners;

use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Order;
use Throwable;

/**
 * Emails the customer their order confirmation.
 *
 * Runs off Lunar's PaymentAttemptEvent, which the VERIFIED driver dispatches
 * once a settlement callback has marked the order paid. Checkout paths that
 * place an order without a payment attempt call {@see self::send()} directly.
 */
class SendOrderConfirmation
{
    public function handle(PaymentAttemptEvent $event): void
    {
        $authorize = $event->paymentAuthorize;

        if (! $authorize->success || ! $authorize->orderId) {
            return;
        }

        $order = Order::find($authorize->orderId);

        if ($order) {
            $this->send($order);
        }
    }

    /**
     * Send the confirmation once per order.
     *
     * Payment callbacks are retried, so a sent marker is kept in the order
     * meta. A mail failure is logged and swallowed: by the time this runs the
     * payment is already recorded, and a receipt that did not go out must
     * never make a settled callback look like it failed.
     */
    public function send(Order $order): void
    {
        $meta = (array) $order->meta;

        if (! empty($meta['confirmation_emailed_at'])) {
            return;
        }

        $email = $this->recipient($order);

        if ($email === null) {
            Log::warning('Order has no email address to send a confirmation to.', [
                'order_reference' => $order->reference,
            ]);

            return;
        }

        try {
            Mail::to($email)->send(new OrderConfirmation($order));
        } catch (Throwable $e) {
            Log::error('Could not send the order confirmation email.', [
                'order_reference' => $order->reference,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $order->update([
            'meta' => array_merge($meta, ['confirmation_emailed_at' => now()->toIso8601String()]),
        ]);
    }

    protected function recipient(Order $order): ?string
    {
        $email = $order->shippingAddress?->contact_email
            ?: $order->billingAddress?->contact_email
            ?: $order->user?->email
            ?: $order->customer?->email;

        return is_string($email) && $email !== '' ? $email : null;
    }
}
