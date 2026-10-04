<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ResolvesPerPage;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()->with('roles');

        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['users.name', 'users.email'], $search);
        }

        $role = $request->query('role');
        if (is_string($role) && in_array($role, ['admin', 'customer'], true)) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        return UserResource::collection(
            $query->orderBy('id')->paginate($this->perPage($request, 50))->withQueryString()
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);
        $user->assignRole($data['role']);

        return (new UserResource($user->load('roles')))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = User::findOrFail($id);
        $data = $request->validated();
        $isSelf = $request->user()->is($user);

        if ($isSelf && ((isset($data['role']) && $data['role'] !== 'admin') || (isset($data['is_active']) && ! $data['is_active']))) {
            throw ValidationException::withMessages([
                'role' => ['You cannot remove your own admin access or deactivate yourself.'],
            ]);
        }

        $fields = array_intersect_key($data, array_flip(['name', 'is_active']));
        if ($fields) {
            $user->update($fields);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if (isset($data['is_active']) && ! $data['is_active']) {
            $user->tokens()->delete();
        }

        return new UserResource($user->load('roles'));
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->values(),
        ]);
    }
}
