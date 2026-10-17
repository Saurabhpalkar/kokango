<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\RegisterVendorRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/** Seller sign-up and sign-in. A seller is a User (role "vendor") with a Vendor store. */
class VendorAuthController extends Controller
{
    public function __construct(private MarketplaceService $marketplace)
    {
    }

    public function register(RegisterVendorRequest $request): JsonResponse
    {
        $d = $request->validated();

        $vendor = DB::transaction(function () use ($d) {
            $user = User::create([
                'name' => $d['owner_name'],
                'email' => $d['email'],
                'password' => $d['password'],
                'is_active' => true,
            ]);
            Role::findOrCreate('vendor', 'web');
            $user->assignRole('vendor');

            return Vendor::create([
                'user_id' => $user->id,
                'store_name' => $d['store_name'],
                'owner_name' => $d['owner_name'],
                'email' => $d['email'],
                'phone' => $d['phone'],
                'city' => $d['city'],
                'gst_no' => isset($d['gst_no']) ? strtoupper($d['gst_no']) : null,
                'description' => $d['description'] ?? null,
                'status' => 'pending',
            ]);
        });

        return $this->authResponse($vendor, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['login']))])->first();
        $vendor = $user ? Vendor::where('user_id', $user->id)->first() : null;

        if (! $vendor || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Wrong email or password.']]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'This account has been deactivated. Please contact Kokango support.'], 403);
        }

        if (in_array($vendor->status, ['suspended', 'rejected'], true)) {
            $what = $vendor->status === 'suspended' ? 'Your store is suspended' : 'Your application was not approved';

            return response()->json([
                'message' => $what.($vendor->status_reason ? ': '.$vendor->status_reason : '.').' Please contact Kokango support.',
            ], 403);
        }

        return $this->authResponse($vendor);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->vendorPayload($request->attributes->get('vendor'), true)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function authResponse(Vendor $vendor, int $status = 200): JsonResponse
    {
        $token = $vendor->user->createToken('vendor')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'vendor' => $this->marketplace->vendorPayload($vendor, true),
            ],
        ], $status);
    }
}
