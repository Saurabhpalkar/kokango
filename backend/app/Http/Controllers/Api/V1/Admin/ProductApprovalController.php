<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\MarketplaceService;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Admin: review vendor listings before they go on the shop. */
class ProductApprovalController extends Controller
{
    private const LISTED = ['pending_review', 'rejected', 'active', 'paused'];

    public function __construct(private MarketplaceService $marketplace)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->whereNotNull('products.vendor_id')
            ->whereIn('products.status', self::LISTED)
            ->with(['category:id,name', 'variants', 'vendor:id,store_name,status']);

        $status = $request->query('status', 'pending_review');
        if (is_string($status) && in_array($status, self::LISTED, true)) {
            $query->where('products.status', $status);
        }
        if (($search = Search::term($request)) !== '') {
            $query->where(function ($q) use ($search) {
                Search::where($q, ['products.name'], $search);
                $q->orWhereHas('vendor', fn ($v) => Search::where($v, ['vendors.store_name'], $search));
            });
        }

        $page = $query->orderByRaw('products.submitted_at is null')->orderByDesc('products.submitted_at')->orderByDesc('products.id')
            ->paginate(8)->withQueryString();

        $counts = array_fill_keys(['pending_review', 'rejected', 'active'], 0);
        $grouped = Product::whereNotNull('vendor_id')->whereIn('status', array_keys($counts))
            ->groupBy('status')->selectRaw('status, count(*) as n')->pluck('n', 'status');
        foreach ($grouped as $key => $n) {
            $counts[$key] = (int) $n;
        }

        return response()->json($this->marketplace->page($page, fn (Product $p) => $this->marketplace->productPayload($p) + [
            'vendor_name' => $p->vendor?->store_name ?? '—',
            'vendor_status' => $p->vendor?->status,
        ]) + ['counts' => $counts]);
    }

    public function act(Request $request, int $id, string $action): JsonResponse
    {
        $product = Product::whereNotNull('vendor_id')->where('status', '!=', 'archived')->findOrFail($id);
        $reason = trim((string) $request->input('reason', ''));

        switch ($action) {
            case 'approve':
                $product->update(['status' => 'active', 'review_note' => null]);
                break;

            case 'reject':
                if ($reason === '') {
                    throw ValidationException::withMessages(['reason' => ['Add a reason so the vendor can fix the listing.']]);
                }
                $product->update(['status' => 'rejected', 'review_note' => mb_substr($reason, 0, 255)]);
                break;

            case 'unpublish':
                if ($product->status !== 'active') {
                    abort(409, 'Only live listings can be unpublished.');
                }
                $product->update(['status' => 'paused']);
                break;
        }

        $this->marketplace->refreshVisibility($product->fresh());

        return response()->json(['data' => $this->marketplace->productPayload($product->fresh(['category', 'variants']))]);
    }
}
