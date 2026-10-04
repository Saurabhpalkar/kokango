# Database (MySQL)

All money columns are unsigned big integers in **paise** and end with `_paise`. All tables have `id` (bigint PK) and `created_at`/`updated_at` unless noted. Use Eloquent models in `backend/app/Models` (namespace `App\Models`).

| Table / Model | Columns |
|---|---|
| `users` / `User` | Laravel defaults (`name`, `email` unique, `password`, `email_verified_at`, `remember_token`) + `phone` (string 15, nullable, indexed), `is_active` (bool, default true). Traits: `HasApiTokens`, `HasRoles` (Spatie). Roles: `admin`, `customer`. |
| `categories` / `Category` | `name`, `slug` (unique), `description` (text, null), `is_active` (bool, default true), `sort_order` (int, default 0). hasMany Product. |
| `products` / `Product` | `category_id` (FK), `name`, `slug` (unique), `hindi_name` (null), `description` (text, null), `image` (string, null; absolute URL, `/images/...` path or storage path), `badge` (string 20, null), `is_active` (bool, default true). belongsTo Category; hasMany ProductVariant (`variants`). |
| `product_variants` / `ProductVariant` | `product_id` (FK, cascade), `sku` (unique), `size_label` (e.g. "100g"), `price_paise`, `mrp_paise` (null), `stock` (unsigned int, default 0), `is_active` (bool). belongsTo Product; hasMany StockMovement. |
| `stock_movements` / `StockMovement` | `product_variant_id` (FK), `change` (int, signed), `stock_after` (int), `reason` (string, null), `user_id` (null). |
| `carts` / `Cart` | `user_id` (FK null, unique per user), `token` (string 64, unique, null for user carts is allowed). hasMany CartItem (`items`). |
| `cart_items` / `CartItem` | `cart_id` (FK cascade), `product_variant_id` (FK), `qty` (unsigned smallint). unique(cart_id, product_variant_id). belongsTo ProductVariant (`variant`). |
| `addresses` / `Address` | `user_id` (FK cascade), `name`, `phone`, `line1`, `line2` (null), `city`, `state`, `pincode` (string 6), `is_default` (bool). |
| `orders` / `Order` | `order_no` (unique, `KKG-{year}-{id padded 6}`, set after insert), `public_token` (string 40, unique), `user_id` (null), `customer_name`, `customer_email`, `customer_phone`, `ship_name`, `ship_phone`, `ship_line1`, `ship_line2` (null), `ship_city`, `ship_state`, `ship_pincode`, `shipping_method` (standard/express), `subtotal_paise`, `shipping_paise`, `tax_paise`, `discount_paise` (default 0), `total_paise`, `status` (pending, confirmed, processing, shipped, delivered, cancelled; default pending), `payment_status` (pending, paid, failed, refunded; default pending), `notes` (text, null), `placed_at`. hasMany OrderItem (`items`), hasOne Payment (`payment`, latest), hasMany Shipment (`shipments`), hasOne latest shipment (`shipment`). belongsTo User. |
| `order_items` / `OrderItem` | `order_id` (FK cascade), `product_id`, `product_variant_id`, `product_slug`, `name`, `size_label`, `image` (null), `unit_price_paise`, `qty`, `total_paise`. |
| `payments` / `Payment` | `order_id` (FK), `gateway` (razorpay/fake), `gateway_order_id` (null, indexed), `gateway_payment_id` (null), `signature` (null), `method` (null: upi, card, netbanking...), `amount_paise`, `currency` (default INR), `status` (pending, paid, failed, refunded), `payload` (json, null), `paid_at` (null). |
| `shipments` / `Shipment` | `order_id` (FK), `provider` (fake/shiprocket), `provider_order_id` (null), `courier` (null), `awb` (null), `status` (pending, ready_to_ship, pickup_scheduled, picked_up, in_transit, out_for_delivery, delivered, returned, cancelled; default pending), `tracking_url` (null), `shipped_at` (null), `delivered_at` (null), `payload` (json, null). |
| `settings` / `Setting` | `key` (unique string), `value` (text, null). Helper `Setting::get($key, $default)` / `Setting::set($key, $value)` with caching optional. Keys: store_name, store_phone, store_email, store_address, whatsapp_number, shipping_standard_paise (6000), shipping_express_paise (12000), free_shipping_above_paise (0 = off), free_shipping_note, gst_percent (5), notification_email. |

Laravel's default `jobs`, `cache`, `sessions`, `personal_access_tokens` (Sanctum) and Spatie permission tables come from their own migrations.

## Rules
- Order, payment and shipment are separate records (never infer "shipped" from "paid").
- Stock is tracked per **variant**; it is decremented inside a DB transaction with `lockForUpdate()` when the order is created and restored when payment fails or the order is cancelled.
- Totals: `subtotal = sum(unit_price_paise * qty)`; `shipping` from settings (standard/express, free standard when `free_shipping_above_paise > 0` and subtotal is at least that); `tax = round(subtotal * gst_percent / 100)`; `total = subtotal + shipping + tax - discount`.
