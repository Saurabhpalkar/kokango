<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Presenter;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $threshold = (int) config('kokango.low_stock_threshold', 10);

        $lowStockQuery = ProductVariant::query()
            ->where('is_active', true)
            ->where('stock', '<', $threshold)
            ->whereHas('product', fn ($q) => $q->where('is_active', true));

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::whereIn('status', ['confirmed', 'processing'])->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'total_sales_paise' => (int) Order::where('payment_status', 'paid')->sum('total_paise'),
            'low_stock_count' => (clone $lowStockQuery)->count(),
            'pending_shipments' => Shipment::whereIn('status', ['pending', 'ready_to_ship'])->count(),
            'customers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->count(),
        ];

        $recent = Order::orderByDesc('placed_at')->orderByDesc('id')->limit(8)->get()
            ->map(fn (Order $o) => [
                'order_no' => $o->order_no,
                'customer_name' => $o->customer_name,
                'total_paise' => (int) $o->total_paise,
                'status' => $o->status,
            ])->values()->all();

        $lowStock = (clone $lowStockQuery)->with('product')
            ->orderBy('stock')->orderBy('id')->limit(10)->get()
            ->map(fn (ProductVariant $v) => [
                'variant_id' => $v->id,
                'product_name' => $v->product?->name,
                'size_label' => $v->size_label,
                'stock' => (int) $v->stock,
                'image_url' => Presenter::imageUrl($v->product?->image),
            ])->values()->all();

        return response()->json(['data' => [
            'stats' => $stats,
            'recent_orders' => $recent,
            'low_stock' => $lowStock,
        ]]);
    }
}
