<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection(
            $request->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()
        );
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->addresses()->count() >= 20) {
            return response()->json(['message' => 'You can save up to 20 addresses. Delete one to add another.'], 422);
        }

        $address = DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            $isFirst = ! $user->addresses()->exists();
            $makeDefault = $isFirst || (bool) ($data['is_default'] ?? false);

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create(array_merge($data, ['is_default' => $makeDefault]));
        });

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function update(AddressRequest $request, int $id): AddressResource
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        DB::transaction(function () use ($request, $user, $address) {
            $data = $request->validated();
            $wantsDefault = array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $address->is_default;

            if ($wantsDefault && ! $address->is_default) {
                $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            // Never leave the user without a default while they still own an address.
            if (! $wantsDefault && $address->is_default) {
                $wantsDefault = true;
            }

            $address->update(array_merge($data, ['is_default' => $wantsDefault]));
        });

        return new AddressResource($address->refresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);
        $wasDefault = $address->is_default;

        DB::transaction(function () use ($user, $address, $wasDefault) {
            $address->delete();

            if ($wasDefault) {
                $next = $user->addresses()->orderByDesc('id')->first();
                $next?->update(['is_default' => true]);
            }
        });

        return response()->json(['message' => 'Address deleted.']);
    }

    public function setDefault(Request $request, int $id): AddressResource
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        DB::transaction(function () use ($user, $address) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return new AddressResource($address->refresh());
    }
}
