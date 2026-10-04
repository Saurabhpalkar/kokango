<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Thin facade over the configured shipping provider.
 */
class ShippingService
{
    public const STATUSES = [
        'pending', 'ready_to_ship', 'pickup_scheduled', 'picked_up', 'in_transit',
        'out_for_delivery', 'delivered', 'returned', 'cancelled',
    ];

    public function __construct(private ShippingProviderInterface $provider)
    {
    }

    public function providerName(): string
    {
        return $this->provider->name();
    }

    /**
     * @return array{serviceable:bool,city:?string,state:?string,etd_days_min:?int,etd_days_max:?int}
     */
    public function serviceability(string $pincode, int $weightGrams = 500): array
    {
        try {
            $result = $this->provider->serviceability($pincode, max(1, $weightGrams));
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Timeout / DNS failure at the courier API: answer cleanly instead of a 500.
            Log::warning('Serviceability lookup failed', ['error' => $e->getMessage()]);
            abort(503, 'We could not check delivery for this pincode right now. Please try again.');
        }

        return [
            'serviceable' => (bool) ($result['serviceable'] ?? false),
            'city' => $result['city'] ?? null,
            'state' => $result['state'] ?? null,
            'etd_days_min' => $result['etd_days_min'] ?? null,
            'etd_days_max' => $result['etd_days_max'] ?? null,
        ];
    }

    /** Create (or fill in) a shipment row for the order using the provider. */
    public function createForOrder(Order $order, ?Shipment $shipment = null): Shipment
    {
        $data = $this->provider->createShipment($order);

        $status = $data['status'] ?? 'ready_to_ship';
        if (! in_array($status, self::STATUSES, true)) {
            $status = 'ready_to_ship';
        }

        $shipment ??= new Shipment();
        $shipment->forceFill([
            'order_id' => $order->id,
            'provider' => $this->provider->name(),
            'provider_order_id' => $data['provider_order_id'] ?? null,
            'courier' => $data['courier'] ?? null,
            'awb' => $data['awb'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'status' => $status,
            'payload' => $data['payload'] ?? null,
        ])->save();

        return $shipment;
    }

    /** Pull the latest status from the provider and store it. Order status is synced by the caller. */
    public function sync(Shipment $shipment): Shipment
    {
        if (! $shipment->awb) {
            abort(409, 'This shipment has no AWB yet.');
        }

        $result = $this->provider->track($shipment);
        $status = $result['status'] ?? $shipment->status;

        if (in_array($status, self::STATUSES, true) && $status !== $shipment->status) {
            $shipment->forceFill(['status' => $status])->save();
        }

        return $shipment;
    }

    /** Best-effort refresh used by customer tracking pages; never throws. */
    public function syncIfStale(Shipment $shipment, int $minutes = 10): Shipment
    {
        $terminal = ['pending', 'delivered', 'returned', 'cancelled'];
        if (! $shipment->awb || in_array($shipment->status, $terminal, true)) {
            return $shipment;
        }
        if ($shipment->updated_at && $shipment->updated_at->gt(now()->subMinutes($minutes))) {
            return $shipment;
        }

        try {
            $before = $shipment->status;
            $this->sync($shipment);
            if ($shipment->status === $before) {
                $shipment->touch();
            }
        } catch (\Throwable $e) {
            Log::warning('Shipment sync failed', ['shipment' => $shipment->id, 'error' => $e->getMessage()]);
        }

        return $shipment;
    }

    /** Best-effort provider-side cancel. */
    public function cancel(Shipment $shipment): void
    {
        try {
            $this->provider->cancel($shipment);
        } catch (\Throwable $e) {
            Log::warning('Shipment provider cancel failed', ['shipment' => $shipment->id, 'error' => $e->getMessage()]);
        }
    }
}
