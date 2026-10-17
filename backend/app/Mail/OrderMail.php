<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * One email for every order event. $kind decides the words:
 *   placed    - to the customer: order received / confirmed
 *   admin     - to the shop owner: a new order arrived
 *   shipped   - to the customer: the parcel is on its way
 *   delivered - to the customer: the parcel was delivered
 *
 * It is sent straight away (not queued) because the free host has no queue worker.
 */
class OrderMail extends Mailable
{
    public const KINDS = ['placed', 'admin', 'shipped', 'delivered'];

    public function __construct(public Order $order, public string $kind)
    {
        $this->order->loadMissing(['items', 'payment', 'shipment']);
    }

    public function envelope(): Envelope
    {
        $no = $this->order->order_no;
        $store = (string) Setting::get('store_name', 'Kokango');

        $subject = match ($this->kind) {
            'admin' => "New order {$no} ({$this->rupees($this->order->total_paise)})",
            'shipped' => "Your {$store} order {$no} has been shipped",
            'delivered' => "Your {$store} order {$no} was delivered",
            default => "Your {$store} order {$no} is confirmed",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $order = $this->order;
        $cod = $order->payment?->gateway === 'cod';
        $shipment = $order->shipment;

        $headline = match ($this->kind) {
            'admin' => 'New order received',
            'shipped' => 'Your order is on its way',
            'delivered' => 'Your order was delivered',
            default => 'Thank you, your order is confirmed',
        };

        $intro = match ($this->kind) {
            'admin' => "{$order->customer_name} placed order {$order->order_no}.".($cod ? ' It is Cash on Delivery: collect the money when you hand over the parcel.' : ' It is already paid.'),
            'shipped' => 'Your parcel has left us and is on its way to you.',
            'delivered' => 'Your parcel has been delivered. We hope you enjoy it!',
            default => $cod
                ? 'We have received your order. Please keep the amount ready to pay in cash when the parcel arrives.'
                : 'We have received your payment and are now packing your order.',
        };

        $front = rtrim((string) config('cors.frontend_url', ''), '/');

        return new Content(
            view: 'emails.order',
            with: [
                'store' => (string) Setting::get('store_name', 'Kokango'),
                'headline' => $headline,
                'intro' => $intro,
                'cod' => $cod,
                'payNow' => $cod && $order->payment_status !== 'paid' && $this->kind !== 'delivered',
                'trackingUrl' => $shipment?->tracking_url,
                'trackPage' => $front !== '' ? $front.'/track-order' : null,
                'isAdmin' => $this->kind === 'admin',
                'money' => fn (int $paise) => $this->rupees($paise),
            ],
        );
    }

    private function rupees(int $paise): string
    {
        $value = $paise / 100;

        return 'Rs '.number_format($value, fmod($value, 1) == 0 ? 0 : 2);
    }
}
