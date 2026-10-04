<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ResolvesPerPage;

    private const STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with('order');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $paginator = $query->orderByDesc('id')->paginate($this->perPage($request));

        $sum = fn (string $status) => (int) Payment::where('status', $status)->sum('amount_paise');

        return response()->json([
            'data' => PaymentResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'summary' => [
                    'collected_paise' => $sum('paid'),
                    'failed_paise' => $sum('failed'),
                    'refunded_paise' => $sum('refunded'),
                    'transactions' => Payment::count(),
                ],
            ],
        ]);
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $payment = $this->orders->refundPayment($payment)->load('order');

        return response()->json(['data' => (new PaymentResource($payment))->resolve($request)]);
    }
}
