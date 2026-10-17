<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo marketplace sellers (local only). Login: vendor@kokango.test / Vendor@12345 (approved store).
 * Safe to run again: existing sellers and products are left as they are.
 */
class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Category::pluck('id', 'slug');
        if ($cat->isEmpty()) {
            return;
        }

        $bank = fn (string $name) => [
            'bank_account_name' => $name, 'bank_account_no' => '00012345678'.strlen($name), 'bank_ifsc' => 'HDFC0001234', 'bank_name' => 'HDFC Bank',
        ];

        $sahyadri = $this->vendor('vendor@kokango.test', [
            'store_name' => 'Sahyadri Farms', 'owner_name' => 'Saurabh Patil', 'phone' => '9870111111', 'city' => 'Nashik', 'pincode' => '422001',
            'pickup_address' => '11 Market Road, Nashik', 'gst_no' => '27ABCDE1001F1Z5', 'pan_no' => 'ABCDE1001F', 'fssai_no' => '11522001001001',
            'description' => 'Konkan-style snacks and herbal powders.', 'status' => 'approved',
        ] + $bank('Sahyadri Farms'));

        $konkan = $this->vendor('konkan@kokango.test', [
            'store_name' => 'Konkan Kitchen', 'owner_name' => 'Meena Sawant', 'phone' => '9870222222', 'city' => 'Ratnagiri', 'pincode' => '415612',
            'pickup_address' => '12 Market Road, Ratnagiri', 'gst_no' => '27ABCDE1002F1Z5', 'pan_no' => 'ABCDE1002F', 'fssai_no' => '11522001001002',
            'description' => 'Traditional sweets and snacks from Ratnagiri.', 'status' => 'approved', 'commission_percent' => 8,
        ] + $bank('Konkan Kitchen'));

        $this->vendor('greenroots@kokango.test', [
            'store_name' => 'Green Roots Organics', 'owner_name' => 'Anil Kale', 'phone' => '9870333333', 'city' => 'Kolhapur',
            'description' => 'Organic herbal powders.', 'status' => 'pending',
        ]);

        $this->product($sahyadri, $cat['sweets'] ?? $cat->first(), 'Kokum Sarbat Mix', '/images/products/kokum-sarbat-mix.jpg', 'active', [['500ml', 24900, 27900, 30]]);
        $this->product($sahyadri, $cat['snacks'] ?? $cat->first(), 'Banana Chips', '/images/products/banana-chips.jpg', 'active', [['200g', 13900, 15900, 60]]);
        $this->product($sahyadri, $cat['sweets'] ?? $cat->first(), 'Amba Poli', '/images/products/amba-poli.jpg', 'pending_review', [['200g', 18900, 19900, 40]]);
        $this->product($konkan, $cat['sweets'] ?? $cat->first(), 'Modak Mix', '/images/products/modak-mix.jpg', 'active', [['250g', 21900, null, 25]]);
        $this->product($konkan, $cat['powders'] ?? $cat->first(), 'Turmeric Powder', '/images/products/turmeric-powder.jpg', 'pending_review', [['200g', 15900, 17900, 55]]);

        $market = app(MarketplaceService::class);
        foreach (Vendor::all() as $vendor) {
            $market->refreshVendorVisibility($vendor);
        }
    }

    private function vendor(string $email, array $fields): Vendor
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $fields['owner_name'],
            'password' => 'Vendor@12345',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('vendor');

        return Vendor::firstOrCreate(['user_id' => $user->id], $fields + ['email' => $email]);
    }

    /** @param  array<int, array{0:string,1:int,2:?int,3:int}>  $variants  [size, price paise, mrp paise, stock] */
    private function product(Vendor $vendor, int $categoryId, string $name, string $image, string $status, array $variants): void
    {
        $slug = Str::slug($name);
        $existing = Product::where('slug', $slug)->first();
        if ($existing) {
            // Older demo rows used shared pack photos: move them to their own photo.
            if (str_starts_with((string) $existing->image, '/images/') && ! str_starts_with((string) $existing->image, '/images/products/')) {
                $existing->forceFill(['image' => $image])->save();
            }

            return;
        }

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Small-batch product prepared with care by '.$vendor->store_name.'.',
            'image' => $image,
            'is_active' => false,
            'status' => $status,
            'submitted_at' => now()->subDay(),
        ]);

        foreach ($variants as $i => [$size, $price, $mrp, $stock]) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'V'.$vendor->id.'-'.strtoupper(Str::slug($name, '')).'-'.($i + 1),
                'size_label' => $size,
                'price_paise' => $price,
                'mrp_paise' => $mrp,
                'stock' => $stock,
                'is_active' => true,
            ]);
        }
    }
}
