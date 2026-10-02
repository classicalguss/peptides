<?php

namespace App\Mail;

use App\Shipping\ShippingLabel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\Order;

/**
 * Tells the customer their order has shipped and how to track it.
 */
class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ShippingLabel $label) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->reference} shipped — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.shipped',
            with: [
                'reference' => $this->order->reference,
                'firstName' => $this->order->shippingAddress?->first_name,
                'carrier' => $this->label->carrier,
                'trackingCode' => $this->label->trackingCode,
                'trackingUrl' => $this->label->trackingUrl,
                'lines' => $this->order->productLines
                    ->map(fn ($line) => [
                        'name' => trim($line->description.($line->option ? " — {$line->option}" : '')),
                        'quantity' => (int) $line->quantity,
                    ])
                    ->values()
                    ->all(),
                'contactUrl' => route('contact'),
            ],
        );
    }
}
