<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'is_active' => true,
        ]);
        // findOrCreate keeps registration working even if the seeders have not run yet.
        Role::findOrCreate('customer', 'web');
        $user->assignRole('customer');
        $user->unsetRelation('roles'); // make sure UserResource reads the fresh roles

        return $this->authResponse($user, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim((string) $request->validated('login'));

        $query = User::query();
        if (str_contains($login, '@')) {
            $query->whereRaw('LOWER(email) = ?', [mb_strtolower($login)]);
        } else {
            // "+91 98765 43210" and "09876543210" both mean the 10-digit mobile number.
            $digits = preg_replace('/\D+/', '', $login);
            $query->where('phone', strlen($digits) > 10 ? substr($digits, -10) : $digits);
        }
        $user = $query->first();

        if (! $user || ! Hash::check((string) $request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'This account has been deactivated. Please contact support.'], 403);
        }

        return $this->authResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // Never reveal whether the address exists or whether mail failed.
            Log::warning('Password reset link failed: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'If that email address is registered, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $user->save();
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Your password has been reset. You can now log in.']);
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return new UserResource($user->refresh());
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check((string) $request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill(['password' => $request->validated('password')])->save();

        // Sign out every other device.
        $current = $user->currentAccessToken();
        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($q) => $q->where('id', '!=', $current->id))
            ->delete();

        return response()->json(['message' => 'Password updated.']);
    }

    private function authResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
                'token' => $token,
            ],
        ], $status);
    }
}
