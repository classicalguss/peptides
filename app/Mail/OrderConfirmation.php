<?php

namespace App\Mail;

use App\Support\Catalog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\Order;
use Lunar\Models\OrderAddress;

/**
 * Receipt sent to the customer once their order is in.
 *
 * The wording follows the confirmation page: an order whose payment has
 * settled (placed_at set) is "confirmed", anything still awaiting payment
 * is "received". Everything shown is precomputed here so the Markdown
 * template stays free of money formatting and relation lookups.
 */
class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $state = $this->isConfirmed() ? 'confirmed' : 'received';

        return new Envelope(
            subject: "Order {$this->order->reference} {$state} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        $shipping = $this->order->shippingAddress;

        return new Content(
            markdown: 'mail.orders.confirmation',
            with: [
                'confirmed' => $this->isConfirmed(),
                'reference' => $this->order->reference,
                'firstName' => $shipping?->first_name,
                'lines' => $this->lines(),
                'subtotal' => Catalog::money($this->order->sub_total->value),
                'shipping' => ($this->order->shipping_total?->value ?? 0) > 0 ? Catalog::money($this->order->shipping_total->value) : 'Free',
                'tax' => Catalog::money($this->order->tax_total?->value ?? 0),
                'total' => Catalog::money($this->order->total->value),
                'address' => $shipping ? $this->addressLines($shipping) : [],
                'steps' => site_list('confirmation.steps')->pluck('body')->filter()->values()->all(),
                'orderUrl' => route('checkout.confirmation', $this->order->reference),
                'contactUrl' => route('contact'),
            ],
        );
    }

    protected function isConfirmed(): bool
    {
        return $this->order->placed_at !== null;
    }

    /**
     * @return list<array{name: string, quantity: int, total: string}>
     */
    protected function lines(): array
    {
        return $this->order->lines
            ->map(fn ($line) => [
                'name' => trim($line->description.($line->option ? " — {$line->option}" : '')),
                'quantity' => (int) $line->quantity,
                'total' => Catalog::money($line->sub_total->value),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function addressLines(OrderAddress $address): array
    {
        return array_values(array_filter([
            trim("{$address->first_name} {$address->last_name}"),
            $address->company_name,
            $address->line_one,
            $address->line_two,
            trim("{$address->city}, {$address->state} {$address->postcode}", ', '),
            $address->country?->name,
        ]));
    }
}
