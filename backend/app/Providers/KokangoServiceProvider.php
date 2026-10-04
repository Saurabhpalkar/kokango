<?php

namespace App\Providers;

use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\RazorpayGateway;
use App\Services\Shipping\FakeShippingProvider;
use App\Services\Shipping\ShippingProviderInterface;
use App\Services\Shipping\ShiprocketProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class KokangoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function (Application $app) {
            return match (config('kokango.payment.driver')) {
                'razorpay' => $app->make(RazorpayGateway::class),
                default => $app->make(FakeGateway::class),
            };
        });

        $this->app->bind(ShippingProviderInterface::class, function (Application $app) {
            return match (config('kokango.shipping.driver')) {
                'shiprocket' => $app->make(ShiprocketProvider::class),
                default => $app->make(FakeShippingProvider::class),
            };
        });
    }
}
