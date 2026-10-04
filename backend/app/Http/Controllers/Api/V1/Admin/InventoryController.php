<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ImageUrl;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public const LOW_STOCK_BELOW = 10;

    public function index(Request $request): JsonResponse
    {
        $query = ProductVariant::query()->with('product');

        if (($search = Search::term($request)) !== '') {
            $query->where(function ($q) use ($search) {
                Search::where($q, ['product_variants.sku'], $search);
                $q->orWhereHas('product', fn ($p) => Search::where($p, ['products.name', 'products.hindi_name'], $search));
            });
        }

        $rows = $query->orderBy('product_id')->orderBy('price_paise')->get()
            ->map(fn (ProductVariant $v) => $this->row($v))
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function adjust(AdjustInventoryRequest $request, int $variantId): JsonResponse
    {
        $data = $request->validated();

        $variant = DB::transaction(function () use ($data, $variantId, $request) {
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variantId);

            $current = (int) $variant->stock;
            $new = isset($data['stock']) ? (int) $data['stock'] : $current + (int) $data['change'];

            if ($new < 0) {
                throw ValidationException::withMessages(['change' => ['Stock cannot go below zero.']]);
            }

            $change = $new - $current;
            if ($change !== 0) {
                $variant->update(['stock' => $new]);

                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'change' => $change,
                    'stock_after' => $new,
                    'reason' => $data['reason'] ?? 'Manual adjustment',
                    'user_id' => $request->user()?->id,
                ]);
            }

            return $variant;
        });

        return response()->json(['data' => $this->row($variant->load('product'))]);
    }

    private function row(ProductVariant $v): array
    {
        return [
            'variant_id' => $v->id,
            'product_id' => $v->product_id,
            'product_name' => $v->product?->name,
            'size_label' => $v->size_label,
            'sku' => $v->sku,
            'stock' => (int) $v->stock,
            'low' => (int) $v->stock < self::LOW_STOCK_BELOW,
            'image_url' => ImageUrl::resolve($v->product?->image),
        ];
    }
}
