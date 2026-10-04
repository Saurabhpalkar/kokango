<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;

/**
 * Local development / test provider.
 */
class FakeShippingProvider implements ShippingProviderInterface
{
    /** pincode prefix => [city, state] */
    private const PREFIXES = [
        '11' => ['New Delhi', 'Delhi'],
        '12' => ['Gurugram', 'Haryana'],
        '20' => ['Noida', 'Uttar Pradesh'],
        '22' => ['Lucknow', 'Uttar Pradesh'],
        '30' => ['Jaipur', 'Rajasthan'],
        '38' => ['Ahmedabad', 'Gujarat'],
        '40' => ['Mumbai', 'Maharashtra'],
        '41' => ['Pune', 'Maharashtra'],
        '50' => ['Hyderabad', 'Telangana'],
        '56' => ['Bengaluru', 'Karnataka'],
        '60' => ['Chennai', 'Tamil Nadu'],
        '68' => ['Kochi', 'Kerala'],
        '70' => ['Kolkata', 'West Bengal'],
        '80' => ['Patna', 'Bihar'],
    ];

    public function name(): string
    {
        return 'fake';
    }

    public function serviceability(string $pincode, int $weightGrams): array
    {
        if (! preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
            return [
                'serviceable' => false,
                'city' => null,
                'state' => null,
                'etd_days_min' => null,
                'etd_days_max' => null,
            ];
        }

        [$city, $state] = self::PREFIXES[substr($pincode, 0, 2)] ?? ['Your city', 'Your state'];

        return [
            'serviceable' => true,
            'city' => $city,
            'state' => $state,
            'etd_days_min' => 3,
            'etd_days_max' => 5,
        ];
    }

    public function createShipment(Order $order): array
    {
        return [
            'provider_order_id' => 'fake_'.$order->order_no,
            'courier' => 'Delhivery',
            'awb' => 'DLV'.random_int(100000, 999999).' '.random_int(1000, 9999),
            'tracking_url' => 'https://www.delhivery.com/tracking',
            'status' => 'ready_to_ship',
            'payload' => ['driver' => 'fake'],
        ];
    }

    public function track(Shipment $shipment): array
    {
        return [
            'status' => (string) $shipment->status,
            'events' => [],
        ];
    }

    public function cancel(Shipment $shipment): void
    {
        // Nothing to do for the fake provider.
    }
}
