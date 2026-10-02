<?php

namespace App\Shipping;

use App\Listeners\SendOrderConfirmation;
use App\Mail\OrderShipped;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Lunar\Models\Order;
use Throwable;

/**
 * Buys the shipping label for an order and records the shipment.
 */
class ShipOrder
{
    public const DISPATCHED_STATUS = 'dispatched';

    public function __construct(
        protected EasyPostClient $easyPost,
        protected SendOrderConfirmation $confirmation,
    ) {}

    /**
     * Buy the chosen rate, store the label and tracking number on the order,
     * mark it dispatched and email the customer their tracking details.
     *
     * An order only ever gets one label through here — a second purchase
     * would be a second charge, so it is refused rather than overwritten.
     */
    public function handle(Order $order, string $shipmentId, string $rateId): ShippingLabel
    {
        if (ShippingLabel::forOrder($order)) {
            throw new EasyPostException('A shipping label has already been bought for this order.');
        }

        $label = $this->easyPost->buy($shipmentId, $rateId);

        $order->update([
            'status' => self::DISPATCHED_STATUS,
            'meta' => array_merge((array) $order->meta, [ShippingLabel::META_KEY => $label->toMeta()]),
        ]);

        $this->notifyCustomer($order, $label);

        return $label;
    }

    /**
     * A mail failure is logged and swallowed: the label is already paid for
     * and recorded, so it must not look like the purchase failed.
     */
    protected function notifyCustomer(Order $order, ShippingLabel $label): void
    {
        $email = $this->confirmation->recipient($order);

        if ($email === null) {
            return;
        }

        try {
            Mail::to($email)->send(new OrderShipped($order, $label));
        } catch (Throwable $e) {
            Log::error('Could not send the shipping notification email.', [
                'order_reference' => $order->reference,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
