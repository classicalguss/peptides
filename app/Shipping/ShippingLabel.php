<?php

namespace App\Shipping;

use Lunar\Models\Contracts\Order as OrderContract;

/**
 * A purchased EasyPost label, as stored on the order's meta.
 */
class ShippingLabel
{
    public const META_KEY = 'easypost';

    public function __construct(
        public readonly string $shipmentId,
        public readonly string $trackingCode,
        public readonly string $carrier,
        public readonly string $service,
        public readonly int $rate,
        public readonly string $labelUrl,
        public readonly ?string $trackingUrl,
        public readonly bool $test,
    ) {}

    /**
     * @param  array<string, mixed>  $shipment  A bought EasyPost shipment.
     */
    public static function fromResponse(array $shipment): self
    {
        $trackingCode = (string) ($shipment['tracking_code'] ?? '');
        $labelUrl = (string) ($shipment['postage_label']['label_url'] ?? '');

        if ($trackingCode === '' || $labelUrl === '') {
            throw new EasyPostException('EasyPost did not return a label for this shipment.');
        }

        return new self(
            shipmentId: (string) ($shipment['id'] ?? ''),
            trackingCode: $trackingCode,
            carrier: (string) ($shipment['selected_rate']['carrier'] ?? ''),
            service: (string) ($shipment['selected_rate']['service'] ?? ''),
            rate: EasyPostClient::toCents($shipment['selected_rate']['rate'] ?? 0),
            labelUrl: $labelUrl,
            trackingUrl: $shipment['tracker']['public_url'] ?? null,
            test: ($shipment['mode'] ?? null) === 'test',
        );
    }

    public static function forOrder(OrderContract $order): ?self
    {
        $meta = ((array) $order->meta)[self::META_KEY] ?? null;

        if (! is_array($meta) || empty($meta['tracking_code'])) {
            return null;
        }

        return new self(
            shipmentId: (string) ($meta['shipment_id'] ?? ''),
            trackingCode: (string) $meta['tracking_code'],
            carrier: (string) ($meta['carrier'] ?? ''),
            service: (string) ($meta['service'] ?? ''),
            rate: (int) ($meta['rate'] ?? 0),
            labelUrl: (string) ($meta['label_url'] ?? ''),
            trackingUrl: $meta['tracking_url'] ?? null,
            test: (bool) ($meta['test'] ?? false),
        );
    }

    /**
     * Kept flat and scalar so Lunar's order screen can list it as-is under
     * "Additional Information".
     *
     * @return array<string, string|int|bool|null>
     */
    public function toMeta(): array
    {
        return [
            'shipment_id' => $this->shipmentId,
            'tracking_code' => $this->trackingCode,
            'carrier' => $this->carrier,
            'service' => $this->service,
            'rate' => $this->rate,
            'label_url' => $this->labelUrl,
            'tracking_url' => $this->trackingUrl,
            'test' => $this->test,
            'purchased_at' => now()->toIso8601String(),
        ];
    }
}
