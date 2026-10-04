<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketProvider implements ShippingProviderInterface
{
    private const TOKEN_CACHE_KEY = 'kokango.shiprocket.token';

    public function name(): string
    {
        return 'shiprocket';
    }

    public function serviceability(string $pincode, int $weightGrams): array
    {
        $response = $this->call('GET', '/courier/serviceability/', [
            'pickup_postcode' => (string) config('kokango.shipping.shiprocket.pickup_pincode'),
            'delivery_postcode' => $pincode,
            'weight' => max(0.5, round($weightGrams / 1000, 2)),
            'cod' => 0,
        ]);

        $companies = [];
        if ($response->successful()) {
            $companies = (array) data_get($response->json(), 'data.available_courier_companies', []);
        }

        if (empty($companies)) {
            return [
                'serviceable' => false,
                'city' => null,
                'state' => null,
                'etd_days_min' => null,
                'etd_days_max' => null,
            ];
        }

        $days = [];
        foreach ($companies as $company) {
            $d = (int) ($company['estimated_delivery_days'] ?? 0);
            if ($d > 0) {
                $days[] = $d;
            }
        }

        $first = $companies[0];

        return [
            'serviceable' => true,
            'city' => $first['city'] ?? null,
            'state' => $first['state'] ?? null,
            'etd_days_min' => $days ? min($days) : null,
            'etd_days_max' => $days ? max($days) : null,
        ];
    }

    public function createShipment(Order $order): array
    {
        $order->loadMissing('items');

        $items = [];
        $weightGrams = 0;
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->name.' '.$item->size_label,
                'sku' => 'V'.$item->product_variant_id,
                'units' => (int) $item->qty,
                'selling_price' => round($item->unit_price_paise / 100, 2),
            ];
            $weightGrams += 250 * (int) $item->qty;
        }
        $weightKg = max(0.5, round($weightGrams / 1000, 2));

        $placedAt = \Illuminate\Support\Carbon::parse($order->placed_at ?? now());
        $address2 = (string) ($order->ship_line2 ?? '');

        $payload = [
            'order_id' => (string) $order->order_no,
            'order_date' => $placedAt->format('Y-m-d H:i'),
            'pickup_location' => 'Primary',
            'billing_customer_name' => (string) $order->ship_name,
            'billing_last_name' => '',
            'billing_address' => (string) $order->ship_line1,
            'billing_address_2' => $address2,
            'billing_city' => (string) $order->ship_city,
            'billing_pincode' => (string) $order->ship_pincode,
            'billing_state' => (string) $order->ship_state,
            'billing_country' => 'India',
            'billing_email' => (string) $order->customer_email,
            'billing_phone' => (string) $order->ship_phone,
            'shipping_is_billing' => true,
            'order_items' => $items,
            'payment_method' => 'Prepaid',
            'sub_total' => round($order->subtotal_paise / 100, 2),
            'length' => 15,
            'breadth' => 12,
            'height' => 10,
            'weight' => $weightKg,
        ];

        $created = $this->call('POST', '/orders/create/adhoc', $payload);
        if ($created->failed() || ! $created->json('shipment_id')) {
            $this->fail('create order', $created);
        }

        $srOrderId = (string) $created->json('order_id');
        $srShipmentId = (string) $created->json('shipment_id');

        $assigned = $this->call('POST', '/courier/assign/awb', ['shipment_id' => $srShipmentId]);
        if ($assigned->failed()) {
            $this->fail('assign awb', $assigned);
        }

        $awb = data_get($assigned->json(), 'response.data.awb_code');
        $courier = data_get($assigned->json(), 'response.data.courier_name');

        return [
            'provider_order_id' => $srOrderId,
            'courier' => $courier,
            'awb' => $awb ?: null,
            'tracking_url' => $awb ? 'https://shiprocket.co/tracking/'.$awb : null,
            'status' => $awb ? 'ready_to_ship' : 'pending',
            'payload' => ['shiprocket_order_id' => $srOrderId, 'shiprocket_shipment_id' => $srShipmentId],
        ];
    }

    public function track(Shipment $shipment): array
    {
        if (! $shipment->awb) {
            return ['status' => (string) $shipment->status, 'events' => []];
        }

        $response = $this->call('GET', '/courier/track/awb/'.rawurlencode($shipment->awb));
        if ($response->failed()) {
            $this->fail('track', $response);
        }

        $tracking = (array) $response->json('tracking_data', []);
        $label = (string) data_get($tracking, 'shipment_track.0.current_status', '');
        if ($label === '') {
            $label = (string) data_get($tracking, 'shipment_status', '');
        }

        $events = [];
        foreach ((array) ($tracking['shipment_track_activities'] ?? []) as $activity) {
            $events[] = [
                'status' => (string) ($activity['activity'] ?? $activity['sr-status-label'] ?? ''),
                'location' => $activity['location'] ?? null,
                'at' => $activity['date'] ?? null,
            ];
        }

        return [
            'status' => $label === '' ? (string) $shipment->status : $this->mapStatus($label),
            'events' => $events,
        ];
    }

    public function cancel(Shipment $shipment): void
    {
        if (! $shipment->provider_order_id) {
            return;
        }

        $response = $this->call('POST', '/orders/cancel', ['ids' => [(int) $shipment->provider_order_id]]);
        if ($response->failed()) {
            $this->fail('cancel', $response);
        }
    }

    /** Map a Shiprocket status string to our shipment status enum. */
    public function mapStatus(string $label): string
    {
        $s = strtolower(trim($label));

        return match (true) {
            str_contains($s, 'rto'), str_contains($s, 'return'), str_contains($s, 'undelivered') => 'returned',
            str_contains($s, 'cancel') => 'cancelled',
            str_contains($s, 'out for delivery') => 'out_for_delivery',
            str_contains($s, 'delivered') => 'delivered',
            str_contains($s, 'in transit'), str_contains($s, 'shipped'), str_contains($s, 'reached'), str_contains($s, 'dispatched') => 'in_transit',
            str_contains($s, 'picked up'), str_contains($s, 'pickup done') => 'picked_up',
            str_contains($s, 'pickup scheduled'), str_contains($s, 'pickup generated'), str_contains($s, 'pickup queued') => 'pickup_scheduled',
            str_contains($s, 'ready'), str_contains($s, 'manifest'), str_contains($s, 'awb assigned') => 'ready_to_ship',
            default => 'pending',
        };
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('kokango.shipping.shiprocket.base_url'), '/');
    }

    private function token(bool $fresh = false): string
    {
        if ($fresh) {
            Cache::forget(self::TOKEN_CACHE_KEY);
        }

        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addDays(9), function () {
            $response = Http::acceptJson()->asJson()->timeout(20)->post($this->baseUrl().'/auth/login', [
                'email' => config('kokango.shipping.shiprocket.email'),
                'password' => config('kokango.shipping.shiprocket.password'),
            ]);

            $token = $response->json('token');
            if ($response->failed() || ! $token) {
                Log::error('Shiprocket login failed', ['status' => $response->status()]);
                abort(502, 'The shipping provider is unavailable. Please try again.');
            }

            return (string) $token;
        });
    }

    private function call(string $method, string $path, array $data = []): Response
    {
        $send = function (string $token) use ($method, $path, $data): Response {
            return Http::withToken($token)->acceptJson()->timeout(25)->send(
                $method,
                $this->baseUrl().$path,
                [$method === 'GET' ? 'query' : 'json' => $data]
            );
        };

        $response = $send($this->token());
        if ($response->status() === 401) {
            $response = $send($this->token(true));
        }

        return $response;
    }

    private function fail(string $action, Response $response): never
    {
        Log::error('Shiprocket '.$action.' failed', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 800),
        ]);
        abort(502, 'The shipping provider rejected the request. Please try again.');
    }
}
