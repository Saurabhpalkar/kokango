<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $rohan = User::where('email', 'rohan@example.com')->first();

        $customers = [
            'rohan' => ['Rohan Patil', 'rohan@example.com', '9876543210', 'Flat 12, Shivaji Nagar', 'Pune', 'Maharashtra', '411005'],
            'sneha' => ['Sneha Joshi', 'sneha.joshi@example.com', '9822011122', '14 Kothrud Road', 'Pune', 'Maharashtra', '411038'],
            'amit' => ['Amit Deshmukh', 'amit.deshmukh@example.com', '9890123456', '7 College Road', 'Nashik', 'Maharashtra', '422005'],
            'priya' => ['Priya Kulkarni', 'priya.kulkarni@example.com', '9765432101', '22 Dadar West', 'Mumbai', 'Maharashtra', '400028'],
            'neha' => ['Neha Sawant', 'neha.sawant@example.com', '9921345670', 'Ratnagiri Main Road', 'Ratnagiri', 'Maharashtra', '415612'],
            'vikram' => ['Vikram Naik', 'vikram.naik@example.com', '9422567891', '5 Panaji Market', 'Panaji', 'Goa', '403001'],
            'anita' => ['Anita More', 'anita.more@example.com', '9012345678', '31 Rajarampuri', 'Kolhapur', 'Maharashtra', '416008'],
            'meera' => ['Meera Pawar', 'meera.pawar@example.com', '8805671234', '9 Camp Area', 'Satara', 'Maharashtra', '415001'],
            'sunil' => ['Sunil Bhosale', 'sunil.bhosale@example.com', '9370011223', '18 Vishrambag', 'Sangli', 'Maharashtra', '416415'],
            'pooja' => ['Pooja Shinde', 'pooja.shinde@example.com', '9657123400', '2 Gandhi Chowk', 'Thane', 'Maharashtra', '400601'],
            'rahul' => ['Rahul Kadam', 'rahul.kadam@example.com', '9545009988', '40 MG Road', 'Mumbai', 'Maharashtra', '400001'],
        ];

        // order number, customer, items [sku, qty], status, payment status, shipment status|null, days ago, method, shipment awb
        $orders = [
            [112, 'sneha', [['KKG-NP-100', 1], ['KKG-BP-100', 1]], 'delivered', 'paid', 'delivered', 14, 'upi'],
            [113, 'amit', [['KKG-JV-500', 1]], 'delivered', 'paid', 'delivered', 12, 'card'],
            [114, 'priya', [['KKG-CL-100', 2], ['KKG-JC-100', 1]], 'delivered', 'paid', 'delivered', 11, 'upi'],
            [115, 'neha', [['KKG-MP-250', 1]], 'cancelled', 'refunded', null, 10, 'netbanking'],
            [116, 'vikram', [['KKG-TP-250', 1], ['KKG-NP-250', 1]], 'delivered', 'paid', 'delivered', 9, 'card'],
            [117, 'anita', [['KKG-JC-200', 2]], 'shipped', 'paid', 'out_for_delivery', 6, 'upi'],
            [118, 'meera', [['KKG-MP-50', 3]], 'processing', 'paid', 'pickup_scheduled', 4, 'upi'],
            [119, 'sunil', [['KKG-JV-250', 2]], 'confirmed', 'paid', 'pending', 3, 'card'],
            [120, 'pooja', [['KKG-BP-250', 1]], 'pending', 'failed', null, 3, null],
            [121, 'rahul', [['KKG-CL-250', 1], ['KKG-TP-100', 1]], 'confirmed', 'paid', 'pending', 2, 'upi'],
            [122, 'sneha', [['KKG-MP-100', 1]], 'processing', 'paid', 'ready_to_ship', 1, 'upi'],
            [123, 'rohan', [['KKG-MP-100', 2], ['KKG-TP-100', 1], ['KKG-JC-200', 1]], 'shipped', 'paid', 'in_transit', 2, 'upi'],
        ];

        Model::unguarded(function () use ($orders, $customers, $rohan) {
            foreach ($orders as [$number, $key, $lines, $status, $payStatus, $shipStatus, $daysAgo, $method]) {
                $orderNo = sprintf('KKG-2026-%06d', $number);
                if (Order::where('order_no', $orderNo)->exists()) {
                    continue;
                }

                [$name, $email, $phone, $line1, $city, $state, $pincode] = $customers[$key];
                $placedAt = now()->subDays($daysAgo)->setTime(11, 30);

                $items = [];
                $subtotal = 0;
                foreach ($lines as [$sku, $qty]) {
                    $variant = ProductVariant::with('product')->where('sku', $sku)->first();
                    if (! $variant) {
                        continue 2;
                    }
                    $total = $variant->price_paise * $qty;
                    $subtotal += $total;
                    $items[] = [
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'product_slug' => $variant->product->slug,
                        'name' => $variant->product->name,
                        'size_label' => $variant->size_label,
                        'image' => $variant->product->image,
                        'unit_price_paise' => $variant->price_paise,
                        'qty' => $qty,
                        'total_paise' => $total,
                    ];
                }

                $shipping = 6000;
                $tax = (int) round($subtotal * 5 / 100);

                $order = Order::create([
                    'id' => $number,
                    'order_no' => $orderNo,
                    'public_token' => Str::random(40),
                    'user_id' => $key === 'rohan' ? $rohan?->id : null,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'ship_name' => $name,
                    'ship_phone' => $phone,
                    'ship_line1' => $line1,
                    'ship_line2' => null,
                    'ship_city' => $city,
                    'ship_state' => $state,
                    'ship_pincode' => $pincode,
                    'shipping_method' => 'standard',
                    'subtotal_paise' => $subtotal,
                    'shipping_paise' => $shipping,
                    'tax_paise' => $tax,
                    'discount_paise' => 0,
                    'total_paise' => $subtotal + $shipping + $tax,
                    'status' => $status,
                    'payment_status' => $payStatus,
                    'placed_at' => $placedAt,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);

                foreach ($items as $item) {
                    OrderItem::create($item + ['order_id' => $order->id]);
                }

                Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'fake',
                    'gateway_order_id' => 'fake_order_'.$number,
                    'gateway_payment_id' => in_array($payStatus, ['paid', 'refunded'], true) ? 'fake_pay_'.$number : null,
                    'signature' => in_array($payStatus, ['paid', 'refunded'], true) ? 'fake' : null,
                    'method' => $method,
                    'amount_paise' => $order->total_paise,
                    'currency' => 'INR',
                    'status' => $payStatus,
                    'payload' => $payStatus === 'failed' ? ['reason' => 'Payment declined by bank'] : null,
                    'paid_at' => in_array($payStatus, ['paid', 'refunded'], true) ? $placedAt : null,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);

                if ($shipStatus !== null) {
                    $hasAwb = $shipStatus !== 'pending';
                    $started = in_array($shipStatus, ['picked_up', 'in_transit', 'out_for_delivery', 'delivered'], true);
                    $awb = $number === 123 ? 'DLV884211 9033' : sprintf('DLV%06d %04d', 700000 + $number * 37, 1000 + $number * 11);

                    Shipment::create([
                        'order_id' => $order->id,
                        'provider' => 'fake',
                        'provider_order_id' => 'fake_ship_'.$number,
                        'courier' => $hasAwb ? 'Delhivery' : null,
                        'awb' => $hasAwb ? $awb : null,
                        'status' => $shipStatus,
                        'tracking_url' => $hasAwb ? 'https://www.delhivery.com/tracking' : null,
                        'shipped_at' => $started ? (clone $placedAt)->addDay() : null,
                        'delivered_at' => $shipStatus === 'delivered' ? (clone $placedAt)->addDays(4) : null,
                        'payload' => null,
                        'created_at' => $placedAt,
                        'updated_at' => $placedAt,
                    ]);
                }
            }
        });
    }
}
