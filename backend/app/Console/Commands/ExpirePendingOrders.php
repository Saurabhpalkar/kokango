<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Checkout holds stock until the payment succeeds or fails. Shoppers who close the tab never trigger
 * either, so unpaid orders older than the window are marked as failed and their stock is released.
 * A late payment still works (OrderService::markPaid re-reserves the stock when available).
 */
class ExpirePendingOrders extends Command
{
    protected $signature = 'kokango:expire-pending-orders {--minutes=60 : Age of an unpaid order before it expires}';

    protected $description = 'Release stock held by unpaid orders that were abandoned at checkout';

    public function handle(OrderService $orders): int
    {
        $cutoff = now()->subMinutes(max(5, (int) $this->option('minutes')));
        $count = 0;

        Order::query()
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('placed_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($chunk) use ($orders, &$count) {
                foreach ($chunk as $order) {
                    $orders->markPaymentFailed($order, 'Payment window expired');
                    $count++;
                }
            });

        $this->info("Expired {$count} unpaid order(s).");

        return self::SUCCESS;
    }
}
