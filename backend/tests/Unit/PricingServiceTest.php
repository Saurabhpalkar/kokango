<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pricing(): PricingService
    {
        return app(PricingService::class);
    }

    public function test_default_totals(): void
    {
        $t = $this->pricing()->totals(83600);

        $this->assertSame([
            'subtotal_paise' => 83600,
            'shipping_paise' => 6000,
            'tax_paise' => 4180,
            'discount_paise' => 0,
            'total_paise' => 93780,
        ], $t);
    }

    public function test_express_shipping(): void
    {
        $t = $this->pricing()->totals(83600, 'express');

        $this->assertSame(12000, $t['shipping_paise']);
        $this->assertSame(83600 + 12000 + 4180, $t['total_paise']);
    }

    public function test_free_standard_shipping_threshold(): void
    {
        Setting::set('free_shipping_above_paise', 100000);

        $this->assertSame(6000, $this->pricing()->totals(99999)['shipping_paise']);
        $this->assertSame(0, $this->pricing()->totals(100000)['shipping_paise']);
        // express is never free
        $this->assertSame(12000, $this->pricing()->totals(100000, 'express')['shipping_paise']);
    }

    public function test_zero_subtotal_has_no_charges(): void
    {
        $this->assertSame([
            'subtotal_paise' => 0,
            'shipping_paise' => 0,
            'tax_paise' => 0,
            'discount_paise' => 0,
            'total_paise' => 0,
        ], $this->pricing()->totals(0));
    }

    public function test_settings_override_defaults_and_methods_listing(): void
    {
        Setting::set('shipping_standard_paise', 4900);
        Setting::set('gst_percent', 12);

        $t = $this->pricing()->totals(10000);
        $this->assertSame(4900, $t['shipping_paise']);
        $this->assertSame(1200, $t['tax_paise']);

        $methods = $this->pricing()->methods(0);
        $this->assertSame('standard', $methods[0]['code']);
        $this->assertSame(4900, $methods[0]['price_paise']);
        $this->assertSame('express', $methods[1]['code']);
        $this->assertSame(12000, $methods[1]['price_paise']);
    }
}
