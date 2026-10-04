<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminCustomerResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    use ResolvesPerPage;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->baseQuery();

        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['users.name', 'users.email', 'users.phone'], $search);
        }

        return AdminCustomerResource::collection(
            $query->orderByDesc('users.id')->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function update(Request $request, int $id): AdminCustomerResource
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $customer = User::role('customer')->findOrFail($id);
        $customer->update(['is_active' => (bool) $data['is_active']]);

        if (! $customer->is_active) {
            $customer->tokens()->delete();
        }

        return new AdminCustomerResource($this->baseQuery()->findOrFail($id));
    }

    private function baseQuery(): Builder
    {
        return User::query()
            ->role('customer')
            ->withCount('orders')
            ->withSum(['orders as total_spent_paise' => fn ($q) => $q->where('payment_status', 'paid')], 'total_paise')
            ->addSelect([
                'order_city' => Order::query()->select('ship_city')
                    ->whereColumn('orders.user_id', 'users.id')->orderByDesc('id')->limit(1),
                'address_city' => Address::query()->select('city')
                    ->whereColumn('addresses.user_id', 'users.id')->orderByDesc('is_default')->orderByDesc('id')->limit(1),
            ]);
    }
}
