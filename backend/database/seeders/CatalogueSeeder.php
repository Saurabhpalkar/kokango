<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogueSeeder extends Seeder
{
    /** Ingredients and how-to-use text per product (keyed by image / slug). */
    private const DETAILS = [
        'jackfruit-chips' => ['ingredients' => 'Raw jackfruit (85%), coconut oil, salt.', 'how_to_use' => "Enjoy straight from the pack with tea or coffee.
Reseal the pouch after opening to keep the chips crisp."],
        'jamun-vadi' => ['ingredients' => 'Jamun pulp (70%), sugar, black salt, cumin.', 'how_to_use' => "Eat a piece or two after meals as a digestive treat.
Store in a cool, dry place and keep the pack sealed."],
        'moringa-powder' => ['ingredients' => '100% shade-dried moringa (drumstick) leaves.', 'how_to_use' => "Mix 1 teaspoon into smoothies, juice or warm water.
Sprinkle over soups, dal or add to roti dough.
Use once a day."],
        'curry-leaf-powder' => ['ingredients' => 'Curry leaves (80%), roasted urad dal, dry red chilli, salt.', 'how_to_use' => "Mix with hot rice and a spoon of ghee.
Sprinkle over idli, dosa, upma or buttermilk."],
        'tulsi-powder' => ['ingredients' => '100% dried holy basil (tulsi) leaves.', 'how_to_use' => "Stir half a teaspoon into warm water or herbal tea.
Add a little honey if you like."],
        'neem-powder' => ['ingredients' => '100% sun-dried neem leaves.', 'how_to_use' => "Mix a pinch with warm water, as advised by your doctor.
For external use, make a paste with water or rose water."],
        'beetroot-powder' => ['ingredients' => '100% dried beetroot.', 'how_to_use' => "Add 1 teaspoon to juices, smoothies or lassi.
Use as a natural colour in batters, rotis and baking."],
    ];

    public function run(): void
    {
        $categories = [];
        foreach ([['Herbal Powders', 'powders', 1], ['Snacks', 'snacks', 2], ['Sweets', 'sweets', 3]] as [$name, $slug, $order]) {
            $categories[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true, 'sort_order' => $order]
            );
        }

        // [name, category, hindi, image, badge, code, description, variants: [size, price Rs, mrp Rs|null, stock]]
        $products = [
            ['Jackfruit Chips', 'snacks', 'फणस चिप्स', 'jackfruit-chips', 'HOT', 'JC',
                'Crisp raw jackfruit slices, lightly salted and fried in coconut oil. A Konkan tea-time classic.',
                [['100g', 139, 175, 40], ['200g', 249, 310, 64]]],
            ['Jamun Vadi', 'sweets', 'जांभूळ वडी', 'jamun-vadi', 'SWEET', 'JV',
                'Chewy sun-dried jamun pulp squares with a sweet-tart finish. Made in small batches.',
                [['250g', 299, 370, 6], ['500g', 569, 690, 20]]],
            ['Moringa Powder', 'powders', 'मोरिंगा पावडर', 'moringa-powder', 'TOP', 'MP',
                'Fine moringa leaf powder, dried and ground fresh. Mix into smoothies, rotis or soups.',
                [['50g', 119, null, 80], ['100g', 199, 249, 120], ['250g', 449, 560, 35]]],
            ['Curry Leaf Powder', 'powders', 'कढीपत्ता पावडर', 'curry-leaf-powder', 'FRESH', 'CL',
                'Shade-dried curry leaves ground to a fragrant powder. Sprinkle over rice, chutneys and dals.',
                [['100g', 179, null, 58], ['250g', 399, null, 25]]],
            ['Tulsi Powder', 'powders', 'तुळस पावडर', 'tulsi-powder', 'POPULAR', 'TP',
                'Pure holy basil leaf powder. Stir into warm water, honey or herbal tea.',
                [['100g', 189, null, 47], ['250g', 429, null, 22]]],
            ['Neem Powder', 'powders', 'कडुनिंब पावडर', 'neem-powder', 'NEW', 'NP',
                'Bitter, potent neem leaf powder, sun-dried and finely ground. Traditionally used in home remedies.',
                [['100g', 169, null, 4], ['250g', 379, null, 18]]],
            ['Beetroot Powder', 'powders', 'बीट रूट पावडर', 'beetroot-powder', 'NEW', 'BP',
                'Vibrant beetroot powder with a naturally sweet, earthy taste. Great in juices, batters and baking.',
                [['100g', 219, null, 8], ['250g', 489, null, 15]]],
        ];

        foreach ($products as [$name, $cat, $hindi, $image, $badge, $code, $description, $variants]) {
            $product = Product::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $categories[$cat]->id,
                    'name' => $name,
                    'hindi_name' => $hindi,
                    'description' => $description,
                    'image' => '/images/products/'.$image.'.jpg',
                    'badge' => $badge,
                    'is_active' => true,
                ] + (self::DETAILS[$image] ?? [])
            );

            // Older seeded rows: move to the new photos and fill the details, but never overwrite an admin's edits.
            if ($product->image === '/images/'.$image.'.png') {
                $product->image = '/images/products/'.$image.'.jpg';
            }
            foreach (self::DETAILS[$image] ?? [] as $field => $text) {
                if ($product->{$field} === null) {
                    $product->{$field} = $text;
                }
            }
            $product->save();

            foreach ($variants as [$size, $price, $mrp, $stock]) {
                ProductVariant::firstOrCreate(
                    ['sku' => 'KKG-'.$code.'-'.preg_replace('/\D/', '', $size)],
                    [
                        'product_id' => $product->id,
                        'size_label' => $size,
                        'price_paise' => $price * 100,
                        'mrp_paise' => $mrp === null ? null : $mrp * 100,
                        'stock' => $stock,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
