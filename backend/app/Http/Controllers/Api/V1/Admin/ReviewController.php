<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin: read, hide/show and delete customer reviews. */
class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductReview::query()->with(['product:id,name,slug', 'user:id,name,email']);

        $status = $request->query('status');
        if (in_array($status, ['published', 'hidden'], true)) {
            $query->where('status', $status);
        }
        $rating = (int) $request->query('rating');
        if ($rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }
        if (($search = Search::term($request)) !== '') {
            $query->where(function ($q) use ($search) {
                Search::where($q, ['product_reviews.title', 'product_reviews.body'], $search);
                $q->orWhereHas('product', fn ($p) => Search::where($p, ['products.name'], $search));
            });
        }

        $page = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return response()->json([
            'data' => collect($page->items())->map(fn ($r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'title' => (string) $r->title,
                'body' => $r->body,
                'status' => $r->status,
                'product' => $r->product ? ['name' => $r->product->name, 'slug' => $r->product->slug] : null,
                'customer' => $r->user ? ['name' => $r->user->name, 'email' => $r->user->email] : null,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
            'counts' => [
                'all' => ProductReview::count(),
                'published' => ProductReview::where('status', 'published')->count(),
                'hidden' => ProductReview::where('status', 'hidden')->count(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['published', 'hidden'])]]);
        $review = ProductReview::findOrFail($id);
        $review->update($data);

        return response()->json(['data' => ['id' => $review->id, 'status' => $review->status]]);
    }

    public function destroy(int $id): JsonResponse
    {
        ProductReview::findOrFail($id)->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
