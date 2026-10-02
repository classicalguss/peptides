<?php

namespace App\Shipping;

use App\Models\ShippingSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Contracts\Order as OrderContract;

/**
 * Thin client over the EasyPost shipping API.
 *
 * Buying a label is two calls: creating a shipment prices it with every
 * enabled carrier and costs nothing, then buying one of the returned rates
 * is what charges the account and produces the label and tracking number.
 */
class EasyPostClient
{
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    /**
     * Test keys produce sample labels and never charge the account.
     */
    public function isTestMode(): bool
    {
        return str_starts_with($this->apiKey(), 'EZTK');
    }

    /**
     * Create a shipment for an order and return its rates, cheapest first.
     *
     * @param  array{weight_oz: float|int|string, length: float|int|string, width: float|int|string, height: float|int|string}  $parcel
     * @return array{id: string, rates: list<array{id: string, carrier: string, service: string, rate: int, delivery_days: int|null}>}
     */
    public function createShipment(OrderContract $order, array $parcel): array
    {
        $address = $order->shippingAddress;

        if (! $address) {
            throw new EasyPostException('This order has no shipping address.');
        }

        $shipment = $this->send('/shipments', [
            'shipment' => [
                'reference' => $order->reference,
                'to_address' => array_filter([
                    'name' => trim("{$address->first_name} {$address->last_name}"),
                    'company' => $address->company_name,
                    'street1' => $address->line_one,
                    'street2' => $address->line_two,
                    'city' => $address->city,
                    'state' => $address->state,
                    'zip' => $address->postcode,
                    'country' => $address->country?->iso2 ?? 'US',
                    'phone' => $address->contact_phone,
                    'email' => $address->contact_email,
                ]),
                'from_address' => $this->fromAddress(),
                'parcel' => [
                    'weight' => (float) $parcel['weight_oz'],
                    'length' => (float) $parcel['length'],
                    'width' => (float) $parcel['width'],
                    'height' => (float) $parcel['height'],
                ],
                'options' => [
                    'label_format' => (string) config('easypost.label_format', 'PDF'),
                ],
            ],
        ]);

        $rates = collect($shipment['rates'] ?? [])
            ->map(fn (array $rate) => [
                'id' => (string) $rate['id'],
                'carrier' => (string) ($rate['carrier'] ?? ''),
                'service' => (string) ($rate['service'] ?? ''),
                'rate' => self::toCents($rate['rate'] ?? 0),
                'delivery_days' => isset($rate['delivery_days']) ? (int) $rate['delivery_days'] : null,
            ])
            ->sortBy('rate')
            ->values()
            ->all();

        if ($rates === []) {
            $reasons = collect($shipment['messages'] ?? [])->pluck('message')->filter()->implode(' ');

            throw new EasyPostException(trim('No carrier returned a rate for this shipment. '.$reasons));
        }

        return ['id' => (string) $shipment['id'], 'rates' => $rates];
    }

    /**
     * Buy a rate on a shipment. This is the call that spends money.
     */
    public function buy(string $shipmentId, string $rateId): ShippingLabel
    {
        return ShippingLabel::fromResponse(
            $this->send("/shipments/{$shipmentId}/buy", ['rate' => ['id' => $rateId]])
        );
    }

    /**
     * EasyPost quotes rates as decimal strings; everything in the app is
     * integer cents.
     */
    public static function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function send(string $path, array $payload): array
    {
        try {
            $response = $this->request()->post($path, $payload);
        } catch (ConnectionException $e) {
            throw new EasyPostException('Could not reach EasyPost.', 0, $e);
        }

        if ($response->failed()) {
            Log::error('EasyPost request failed.', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new EasyPostException($this->errorMessage($response));
        }

        return (array) $response->json();
    }

    protected function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new EasyPostException('EASYPOST_API_KEY is not configured.');
        }

        // EasyPost authenticates with the API key as the basic-auth username
        // and an empty password.
        return Http::baseUrl(rtrim((string) config('easypost.api_base'), '/'))
            ->withBasicAuth($this->apiKey(), '')
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('easypost.timeout', 20));
    }

    /**
     * EasyPost explains failures in an `error` object, with per-field detail
     * (a bad ZIP, an undeliverable street) nested under `errors`.
     */
    protected function errorMessage(Response $response): string
    {
        $error = (array) $response->json('error', []);
        $message = is_string($error['message'] ?? null) ? $error['message'] : "EasyPost returned HTTP {$response->status()}.";

        $details = collect((array) ($error['errors'] ?? []))
            ->map(fn ($detail) => is_array($detail)
                ? trim(($detail['field'] ?? '').' '.($detail['message'] ?? ''))
                : (string) $detail)
            ->filter()
            ->implode('; ');

        return $details === '' ? $message : "{$message} ({$details})";
    }

    /**
     * @return array<string, string>
     */
    protected function fromAddress(): array
    {
        $from = ShippingSetting::current()->fromAddress();

        foreach (['street1', 'city', 'state', 'zip'] as $required) {
            if (! isset($from[$required])) {
                throw new EasyPostException("The ship-from address is incomplete ({$required} is missing). Set it under Shipping Settings in the admin.");
            }
        }

        return $from;
    }

    protected function apiKey(): string
    {
        return (string) config('easypost.api_key');
    }
}
