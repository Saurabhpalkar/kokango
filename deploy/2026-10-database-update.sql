-- Kokango database update (October 2026): marketplace tables, product details, new photos.
-- Import ONCE in phpMyAdmin: select your Kokango database > Import > choose this file > Go.
-- Make a backup first: Export > Quick > SQL.

SET NAMES utf8mb4;

-- 1) New tables and columns
create table `vendors` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `store_name` varchar(255) not null, `owner_name` varchar(255) not null, `email` varchar(255) not null, `phone` varchar(15) null, `city` varchar(100) null, `pincode` varchar(6) null, `pickup_address` varchar(255) null, `gst_no` varchar(20) null, `pan_no` varchar(10) null, `fssai_no` varchar(20) null, `description` text null, `status` varchar(20) not null default 'pending', `status_reason` varchar(255) null, `commission_percent` decimal(5, 2) null, `bank_account_name` varchar(255) null, `bank_account_no` varchar(30) null, `bank_ifsc` varchar(11) null, `bank_name` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `vendors` add constraint `vendors_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `vendors` add index `vendors_status_index`(`status`);
alter table `vendors` add unique `vendors_user_id_unique`(`user_id`);
alter table `products` add `vendor_id` bigint unsigned null after `category_id`;
alter table `products` add constraint `products_vendor_id_foreign` foreign key (`vendor_id`) references `vendors` (`id`) on delete set null;
alter table `products` add `status` varchar(20) null after `is_active`;
alter table `products` add `review_note` varchar(255) null after `status`;
alter table `products` add `submitted_at` timestamp null after `review_note`;
alter table `order_items` add `vendor_id` bigint unsigned null after `product_variant_id`;
alter table `order_items` add index `order_items_vendor_id_index`(`vendor_id`);
create table `vendor_payouts` (`id` bigint unsigned not null auto_increment primary key, `vendor_id` bigint unsigned not null, `ref` varchar(32) null, `period` varchar(60) null, `amount_paise` bigint unsigned not null, `status` varchar(20) not null default 'scheduled', `utr` varchar(50) null, `paid_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `vendor_payouts` add constraint `vendor_payouts_vendor_id_foreign` foreign key (`vendor_id`) references `vendors` (`id`) on delete cascade;
alter table `vendor_payouts` add unique `vendor_payouts_ref_unique`(`ref`);
create table `vendor_orders` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `vendor_id` bigint unsigned not null, `status` varchar(20) not null default 'pending', `subtotal_paise` bigint unsigned not null, `commission_percent` decimal(5, 2) not null, `commission_paise` bigint unsigned not null, `earning_paise` bigint unsigned not null, `vendor_payout_id` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `vendor_orders` add constraint `vendor_orders_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;
alter table `vendor_orders` add constraint `vendor_orders_vendor_id_foreign` foreign key (`vendor_id`) references `vendors` (`id`) on delete cascade;
alter table `vendor_orders` add constraint `vendor_orders_vendor_payout_id_foreign` foreign key (`vendor_payout_id`) references `vendor_payouts` (`id`) on delete set null;
alter table `vendor_orders` add unique `vendor_orders_order_id_vendor_id_unique`(`order_id`, `vendor_id`);
alter table `vendor_orders` add index `vendor_orders_vendor_id_status_index`(`vendor_id`, `status`);
alter table `products` add `ingredients` text null after `description`;
alter table `products` add `how_to_use` text null after `ingredients`;

-- 2) Record the two migrations as done (so Laravel will not try to run them again)
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_10_15_000001_create_marketplace_tables', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_10_16_000001_add_ingredients_and_usage_to_products', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;

-- 3) Seller role (sellers sign up at /vendor/register)
INSERT IGNORE INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('vendor', 'web', NOW(), NOW());

-- 4) New product photos (only where the old pack photo is still set)
UPDATE `products` SET `image` = '/images/products/jackfruit-chips.jpg' WHERE `image` = '/images/jackfruit-chips.png';
UPDATE `products` SET `image` = '/images/products/jamun-vadi.jpg' WHERE `image` = '/images/jamun-vadi.png';
UPDATE `products` SET `image` = '/images/products/moringa-powder.jpg' WHERE `image` = '/images/moringa-powder.png';
UPDATE `products` SET `image` = '/images/products/curry-leaf-powder.jpg' WHERE `image` = '/images/curry-leaf-powder.png';
UPDATE `products` SET `image` = '/images/products/tulsi-powder.jpg' WHERE `image` = '/images/tulsi-powder.png';
UPDATE `products` SET `image` = '/images/products/neem-powder.jpg' WHERE `image` = '/images/neem-powder.png';
UPDATE `products` SET `image` = '/images/products/beetroot-powder.jpg' WHERE `image` = '/images/beetroot-powder.png';

-- 5) Ingredients and how to use (only where still empty)
UPDATE `products` SET `ingredients` = 'Raw jackfruit (85%), coconut oil, salt.' WHERE `slug` = 'jackfruit-chips' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Enjoy straight from the pack with tea or coffee.\nReseal the pouch after opening to keep the chips crisp.' WHERE `slug` = 'jackfruit-chips' AND `how_to_use` IS NULL;
UPDATE `products` SET `ingredients` = 'Jamun pulp (70%), sugar, black salt, cumin.' WHERE `slug` = 'jamun-vadi' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Eat a piece or two after meals as a digestive treat.\nStore in a cool, dry place and keep the pack sealed.' WHERE `slug` = 'jamun-vadi' AND `how_to_use` IS NULL;
UPDATE `products` SET `ingredients` = '100% shade-dried moringa (drumstick) leaves.' WHERE `slug` = 'moringa-powder' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Mix 1 teaspoon into smoothies, juice or warm water.\nSprinkle over soups, dal or add to roti dough.\nUse once a day.' WHERE `slug` = 'moringa-powder' AND `how_to_use` IS NULL;
UPDATE `products` SET `ingredients` = 'Curry leaves (80%), roasted urad dal, dry red chilli, salt.' WHERE `slug` = 'curry-leaf-powder' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Mix with hot rice and a spoon of ghee.\nSprinkle over idli, dosa, upma or buttermilk.' WHERE `slug` = 'curry-leaf-powder' AND `how_to_use` IS NULL;
UPDATE `products` SET `ingredients` = '100% dried holy basil (tulsi) leaves.' WHERE `slug` = 'tulsi-powder' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Stir half a teaspoon into warm water or herbal tea.\nAdd a little honey if you like.' WHERE `slug` = 'tulsi-powder' AND `how_to_use` IS NULL;
UPDATE `products` SET `ingredients` = '100% sun-dried neem leaves.' WHERE `slug` = 'neem-powder' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Mix a pinch with warm water, as advised by your doctor.\nFor external use, make a paste with water or rose water.' WHERE `slug` = 'neem-powder' AND `how_to_use` IS NULL;
UPDATE `products` SET `ingredients` = '100% dried beetroot.' WHERE `slug` = 'beetroot-powder' AND `ingredients` IS NULL;
UPDATE `products` SET `how_to_use` = 'Add 1 teaspoon to juices, smoothies or lassi.\nUse as a natural colour in batters, rotis and baking.' WHERE `slug` = 'beetroot-powder' AND `how_to_use` IS NULL;
