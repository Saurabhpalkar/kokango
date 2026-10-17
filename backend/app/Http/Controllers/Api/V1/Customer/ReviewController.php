<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product reviews. Anyone can read published reviews; only a signed-in customer who bought the product
 * (an order that went ahead) can write one, and only one per product (writing again updates it).
 */
class ReviewController extends Controller
{
    use ResolvesViewer;

    public function index(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $published = ProductReview::where('product_id', $product->id)->where('status', 'published');

        $page = (clone $published)->with('user:id,name')->orderByDesc('created_at')
            ->paginate(min(20, max(1, (int) $request->query('per_page', 6))));

        $stars = (clone $published)->selectRaw('rating, count(*) as n')->groupBy('rating')->pluck('n', 'rating');
        $count = (int) $stars->sum();

        $viewer = $this->viewer();
        $mine = $viewer ? ProductReview::where('product_id', $product->id)->where('user_id', $viewer->id)->first() : null;

        return response()->json([
            'data' => collect($page->items())->map(fn ($r) => $this->row($r))->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'summary' => [
                'count' => $count,
                'average' => $count ? round((clone $published)->avg('rating'), 1) : 0,
                'stars' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => (int) ($stars[$s] ?? 0)])->all(),
            ],
            'viewer' => [
                'signed_in' => (bool) $viewer,
                'can_review' => $viewer ? $this->bought($viewer, $product) : false,
                'review' => $mine ? ['rating' => $mine->rating, 'title' => (string) $mine->title, 'body' => $mine->body, 'status' => $mine->status] : null,
            ],
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user();

        if (! $this->bought($user, $product)) {
            abort(403, 'You can review a product after you have ordered it.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], ['body.min' => 'Please write at least a few words (10 characters).']);

        $existing = ProductReview::where('product_id', $product->id)->where('user_id', $user->id)->first();
        $review = ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            // an edit of a review the shop hid stays hidden
            $data + ['status' => $existing?->status === 'hidden' ? 'hidden' : 'published'],
        );

        return response()->json(['data' => $this->row($review->load('user:id,name'))], $existing ? 200 : 201);
    }

    /** The customer has an order for this product that went ahead (not pending, cancelled or failed). */
    private function bought(User $user, Product $product): bool
    {
        return OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)
                ->whereNotIn('status', ['pending', 'cancelled'])
                ->where('payment_status', '!=', 'failed'))
            ->exists();
    }

    private function row(ProductReview $r): array
    {
        $parts = preg_split('/\s+/', trim((string) $r->user?->name)) ?: [];
        $name = ($parts[0] ?? 'Customer').(isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '');

        return [
            'id' => $r->id,
            'rating' => (int) $r->rating,
            'title' => (string) $r->title,
            'body' => $r->body,
            'name' => $name,
            'verified' => true,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
