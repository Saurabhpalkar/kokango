<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store_name' => 'Kokango',
            'store_phone' => '+91 98765 43210',
            'store_email' => 'hello@kokango.test',
            'store_address' => 'Kokan, Maharashtra, India',
            'whatsapp_number' => '',
            'shipping_standard_paise' => '6000',
            'shipping_express_paise' => '12000',
            'free_shipping_above_paise' => '0',
            'free_shipping_note' => 'Standard delivery takes 3 to 5 working days.',
            'gst_percent' => '5',
            'notification_email' => 'admin@kokango.test',
        ];

        // Only fill missing keys so values edited in the admin panel survive a re-seed.
        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
