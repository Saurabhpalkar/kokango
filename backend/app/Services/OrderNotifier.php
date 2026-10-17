<?php

namespace App\Services;

use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the order emails. An email problem (wrong password, server offline) is logged and never
 * stops an order from being placed or updated.
 */
class OrderNotifier
{
    /** New confirmed order: email the customer and the shop owner. */
    public function placed(Order $order): void
    {
        $this->send($order->customer_email, new OrderMail($order, 'placed'));

        if ($admin = $this->adminEmail()) {
            $this->send($admin, new OrderMail($order, 'admin'));
        }
    }

    public function shipped(Order $order): void
    {
        $this->send($order->customer_email, new OrderMail($order, 'shipped'));
    }

    public function delivered(Order $order): void
    {
        $this->send($order->customer_email, new OrderMail($order, 'delivered'));
    }

    /** Where "new order" alerts go: Admin > Settings > Notification email, if empty no owner email is sent. */
    private function adminEmail(): ?string
    {
        $email = trim((string) Setting::get('notification_email', ''));

        return $email !== '' ? $email : null;
    }

    private function send(?string $to, OrderMail $mail): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mail);
        } catch (\Throwable $e) {
            Log::warning('Order email failed', ['kind' => $mail->kind, 'order' => $mail->order->order_no, 'error' => $e->getMessage()]);
        }
    }
}
