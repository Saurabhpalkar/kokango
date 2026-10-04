<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Admin list row: the full Order shape plus customer_name and a short items summary.
 */
class OrderSummaryResource extends OrderResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $order->loadMissing('items');

        $summary = $order->items
            ->map(fn ($i) => $i->name.' ('.$i->size_label.') x'.$i->qty)
            ->implode(', ');

        return parent::toArray($request) + [
            'customer_name' => $order->customer_name,
            'items_count' => (int) $order->items->sum('qty'),
            'items_summary' => $summary,
        ];
    }
}
