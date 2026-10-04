<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user('sanctum')?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            // Inputs can be arrays in a hostile request; only scalars take part in the key.
            $who = $request->input('email', $request->input('login', $request->input('order_no', '')));

            return Limit::perMinute(10)->by($request->ip().'|'.strtolower(is_scalar($who) ? (string) $who : ''));
        });

        // The SPA hosts the "reset password" screen, so point the mail link there.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontend = rtrim((string) config('cors.frontend_url', 'http://localhost:5173'), '/');

            return $frontend.'/reset-password?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset());
        });
    }
}
