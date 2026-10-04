# Kokango API contract (v1)

This file is the single source of truth between `backend/` (Laravel 12) and `frontend/` (Vue 3).
Base URL: `{APP_URL}/api/v1` (local: `http://localhost:8000/api/v1`). All bodies and responses are JSON.

## Conventions
- **Money** is always integer **paise** in fields ending `_paise` (₹249 = `24900`). The frontend converts for display. The backend never trusts amounts from the frontend; it recalculates everything.
- **Auth**: Laravel Sanctum personal access token. Send `Authorization: Bearer <token>`. Roles (Spatie): `admin`, `customer`. A deactivated account's token is revoked and answers `401` on any route (the SPA then drops it). Emails are stored and matched in lower case.
- **Guest cart**: the server creates a cart and returns its `token`; the frontend sends it back as header `X-Cart-Token`. A logged-in user's cart belongs to the user (the header is ignored, except for `POST /cart/merge`).
- **Always send** `Accept: application/json`.
- **Success**: single object `{ "data": {...} }`; lists `{ "data": [...] }`; paginated lists also have `"meta": { "current_page", "last_page", "per_page", "total" }`. Message-only responses: `{ "message": "..." }`.
- **Errors**: `422 { "message": "...", "errors": { "field": ["..."] } }`, `401/403/404/409 { "message": "..." }`.
- Dates are ISO-8601 strings. Pagination query: `page`, `per_page` (default 20, max 100).
- Query parameters must be plain scalars: an array such as `?search[]=x` is treated as missing. `search` is a case-insensitive contains match (max 100 chars, `%` and `_` are literal); unknown `status`/`sort` values are ignored.
- Limits: cart qty per request 1 to 1000 (and at most 20 per item in the cart), `price_paise`/`mrp_paise`/shipping settings up to 100000000, `stock` up to 1000000, 20 saved addresses per user.
- Images: `image_url` is either an absolute URL or a path starting with `/` served by the frontend (`/images/moringa-powder.png`).

## Resources (response shapes)
**User**: `{ id, name, email, phone, is_active, roles: ["customer"], is_admin: false }`

**Product**: `{ id, slug, name, hindi_name, description, image_url, images: [url], badge, is_active, category: { id, slug, name }, min_price_paise, variants: [Variant] }`
**Variant**: `{ id, product_id, sku, size_label, price_paise, mrp_paise, stock, in_stock, is_active }`

**Cart**: `{ token, count, items: [ { id, variant_id, product_id, slug, name, size_label, image_url, unit_price_paise, qty, line_total_paise, stock } ], subtotal_paise, shipping_paise, tax_paise, total_paise }`
(cart shipping = standard shipping estimate, 0 when cart is empty)

**Address**: `{ id, name, phone, line1, line2, city, state, pincode, is_default }`

**Order**: `{ order_no, token, status, payment_status, shipping_method, placed_at, customer: { name, email, phone }, address: { name, phone, line1, line2, city, state, pincode }, items: [ { id, product_id, variant_id, product_slug, name, size_label, image_url, unit_price_paise, qty, total_paise } ], subtotal_paise, shipping_paise, tax_paise, discount_paise, total_paise, payment: { gateway, status, gateway_payment_id, method } | null, shipment: Shipment | null, timeline: [ { key, label, done, at } ] }`
- `token` (the order's `public_token`) is only included in the response of `POST /checkout`; used for guest access.
- `status`: pending | confirmed | processing | shipped | delivered | cancelled
- `payment_status`: pending | paid | failed | refunded
- `timeline` keys in order: `confirmed` ("Order Confirmed"), `processing` ("Processing"), `shipped` ("Shipped"), `in_transit` ("In Transit"), `out_for_delivery` ("Out for Delivery"), `delivered` ("Delivered"); `done` is derived from order/shipment status; `at` is the timestamp when known, else null.

**Shipment**: `{ id, order_no, provider, courier, awb, status, tracking_url, shipped_at, delivered_at, created_at }`
- `status`: pending | ready_to_ship | pickup_scheduled | picked_up | in_transit | out_for_delivery | delivered | returned | cancelled

## Public / customer endpoints

### Auth
| Method | Path | Body | Response |
|---|---|---|---|
| POST | `/auth/register` | name, email, phone?, password, password_confirmation | 201 `{data:{user, token}}` |
| POST | `/auth/login` | login (email or 10-digit mobile), password | `{data:{user, token}}` (422 on bad credentials, 403 if inactive) |
| POST | `/auth/logout` (auth) | | `{message}` |
| GET | `/auth/me` (auth) | | `{data: User}` |
| POST | `/auth/forgot-password` | email | `{message}` (always 200) |
| POST | `/auth/reset-password` | token, email, password, password_confirmation | `{message}` |
| PUT | `/auth/profile` (auth) | name, email, phone? | `{data: User}` |
| PUT | `/auth/password` (auth) | current_password, password, password_confirmation | `{message}` |

### Catalogue
- `GET /categories` -> `{data:[{id, slug, name, products_count}]}` (active categories only)
- `GET /products?category=<slug>&search=&min_price=<paise>&max_price=<paise>&sort=newest|price_asc|price_desc&page&per_page` -> paginated Product list (only active products). `price` filters/sort use the product's lowest active variant price.
- `GET /products/{slug}` -> `{data: Product}` (404 if missing/inactive)
- `GET /settings/public` -> `{data:{ store_name, gst_percent, shipping_standard_paise, shipping_express_paise, free_shipping_above_paise, free_shipping_note, whatsapp_number }}`

### Cart (guest header `X-Cart-Token` or auth)
- `GET /cart` -> `{data: Cart}` (creates an empty cart if none; the returned `token` must be stored by the frontend)
- `POST /cart/items` `{variant_id, qty}` -> Cart (adds or increments; 422 if qty exceeds stock)
- `PATCH /cart/items/{id}` `{qty}` -> Cart (qty 0 removes; max 20 and stock)
- `DELETE /cart/items/{id}` -> Cart
- `DELETE /cart` -> empty Cart
- `POST /cart/merge` (auth) `{guest_token}` -> Cart (moves guest items into the user's cart, then discards the guest cart)

### Addresses (auth)
`GET /addresses`, `POST /addresses`, `PUT /addresses/{id}`, `DELETE /addresses/{id}`, `POST /addresses/{id}/default`. Body: name, phone, line1, line2?, city, state, pincode, is_default?. Returns Address / list of Address.

### Shipping
- `POST /shipping/serviceability` `{pincode}` -> `{data:{pincode, serviceable, city, state, methods:[{code, label, price_paise, days}]}}`. `methods` are for the current cart (standard `3 to 5` days, express `1 to 2` days), prices from settings (free standard shipping when subtotal >= free_shipping_above_paise > 0). Invalid pincode -> 422.
- `POST /shipping/rates` `{pincode}` -> same shape (alias).

### Checkout (guest header `X-Cart-Token` or auth)
- `POST /checkout/quote` `{shipping_method: "standard"|"express"}` -> `{data:{ items:[...Cart items], subtotal_paise, shipping_paise, tax_paise, discount_paise, total_paise }}`
- `POST /checkout` `{ customer?: {name, email, phone} (required for guests), address: {name, phone, line1, line2?, city, state, pincode} | address_id (auth), shipping_method, notes? }` -> 201 `{data:{ order: Order (with token), payment:{ gateway: "razorpay"|"fake", gateway_order_id, key_id, amount_paise, currency: "INR", prefill:{name,email,phone} } }}`
  - validates stock with row locks inside a transaction, snapshots items, creates the order (`pending`/`pending`), decrements stock, creates a pending payment and the gateway order.
  - 409 if the cart is empty or an item is out of stock / pincode not serviceable.

### Payments
- `POST /payments/verify` `{order_no, token?, gateway_order_id, gateway_payment_id, signature}` -> `{data: Order}`. Verifies the signature (Razorpay HMAC-SHA256 of `order_id|payment_id` with the secret; the `fake` driver accepts `signature: "fake"` except when `APP_ENV=production`), marks payment `paid`, order `confirmed`, creates a `pending` shipment, clears the cart. Guests pass the order `token`.
- `POST /payments/failed` `{order_no, token?, reason?}` -> `{message}` (payment `failed`, order stays `pending`/cancelled, stock restored).
- `POST /payments/razorpay/webhook` (public, header `X-Razorpay-Signature`, HMAC of the raw body) -> 200; 400 on a bad signature; 500 if handling threw (Razorpay retries; handling is idempotent). Handles `payment.captured` (ignored when the captured amount differs from the payment amount) and `payment.failed`.
- Unpaid orders are not held forever: the scheduled command `php artisan kokango:expire-pending-orders` (every 15 minutes) marks `pending`/`pending` orders older than 60 minutes as payment `failed` and restores their stock. A payment that still arrives later re-reserves the stock when it is available.

### Orders & tracking
- `GET /orders` (auth) -> paginated Order list (newest first)
- `GET /orders/{order_no}` -> `{data: Order}`; auth owner, or guest with `?token=<order token>`
- `POST /orders/{order_no}/cancel` (auth) -> Order (only when not shipped)
- `POST /orders/track` `{order_no, email}` -> `{data: Order}` (guest lookup by order number + email; email compared case-insensitively; limited to 10 requests per minute per IP and email)
- `GET /shipments/{order_no}/track` -> `{data:{ shipment: Shipment|null, timeline: [...] }}` (same access rules as `GET /orders/{order_no}`)

## Admin endpoints (auth + role `admin`; prefix `/admin`)
- `GET /admin/dashboard` -> `{data:{ stats:{ total_orders, pending_orders, processing_orders, delivered_orders, total_sales_paise, low_stock_count, pending_shipments, customers }, recent_orders:[{order_no, customer_name, total_paise, status}], low_stock:[{variant_id, product_name, size_label, stock, image_url}] }}` (low stock = stock < 10)
- **Products**: `GET /admin/products?search=&category_id=&page=` (Product incl. inactive, plus `stock_total`), `POST /admin/products`, `GET /admin/products/{id}`, `PUT /admin/products/{id}`, `DELETE /admin/products/{id}`, `POST /admin/products/{id}/image` (multipart field `image`, jpg/png/webp, max 2 MB) -> Product.
  Body: `{ name, hindi_name?, category_id, description?, is_active, image_url?, badge?, variants:[{ id?, sku, size_label, price_paise, mrp_paise?, stock, is_active }] }` (`image_url` must be an http(s) URL, a path starting with a single `/`, or a stored path such as `products/x.png`; our own storage URL is stored as the relative path; SKUs are unique case-insensitively); variants missing from a PUT are deleted when they have no order items, else deactivated.
- **Categories**: `GET /admin/categories` (with `products_count`, incl. inactive), `POST`, `PUT /admin/categories/{id}`, `DELETE` (409 if it has products). Body: `{name, description?, is_active?}`.
- **Inventory**: `GET /admin/inventory?search=` -> `{data:[{variant_id, product_id, product_name, size_label, sku, stock, low, image_url}]}`; `POST /admin/inventory/{variant_id}/adjust` `{stock}` or `{change, reason?}` -> same row (writes a stock movement).
- **Orders**: `GET /admin/orders?status=&search=&page=` (search by order no, customer name or email), `GET /admin/orders/{order_no}` -> Order, `PUT /admin/orders/{order_no}/status` `{status}` -> Order (allowed: pending, confirmed, processing, shipped, delivered, cancelled; cancelling restores stock and refunds a paid payment; any status other than `pending` or `cancelled` needs `payment_status` = `paid`, else 409; a cancelled order cannot change).
- **Payments**: `GET /admin/payments?status=&page=` -> `{data:[{id, order_no, customer_name, amount_paise, gateway, gateway_payment_id, method, status, created_at}], meta:{..., summary:{ collected_paise, failed_paise, refunded_paise, transactions }}}`; `POST /admin/payments/{id}/refund` -> payment row (marks refunded; calls the gateway refund when configured).
- **Shipments**: `GET /admin/shipments?status=&page=` -> Shipment list with `customer_name`; `POST /admin/orders/{order_no}/shipment` -> Shipment (creates the shipment with the provider, stores AWB/courier; 409 if the order is not paid or already has an active shipment); `PUT /admin/shipments/{id}/status` `{status}` -> Shipment (keeps order status in step: picked_up/in_transit -> shipped, delivered -> delivered); `POST /admin/shipments/{id}/sync` -> Shipment (pull latest from the provider).
- **Customers**: `GET /admin/customers?search=&page=` -> `{data:[{id, name, email, phone, city, orders_count, total_spent_paise, is_active, created_at}]}`; `PUT /admin/customers/{id}` `{is_active}`.
- **Settings**: `GET /admin/settings`, `PUT /admin/settings` -> `{data:{ store_name, store_phone, store_email, store_address, whatsapp_number, shipping_standard_paise, shipping_express_paise, free_shipping_above_paise, free_shipping_note, gst_percent, notification_email }}`
- **Users & roles**: `GET /admin/users`, `POST /admin/users` `{name,email,password,role}`, `PUT /admin/users/{id}` `{name?, role?, is_active?}`, `GET /admin/roles` -> `{data:["admin","customer"]}`.

## Seed data (for local use)
- Admin: `admin@kokango.test` / `Admin@12345`. Customer: `rohan@example.com` / `Password@123`.
- 3 categories, 7 products with variants, demo orders KKG-2026-000112 .. 000123 with payments and shipments.

## Environment (backend/.env)
`PAYMENT_DRIVER=fake|razorpay` (+ `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`), `SHIPPING_DRIVER=fake|shiprocket` (+ `SHIPROCKET_EMAIL`, `SHIPROCKET_PASSWORD`, `SHIPROCKET_PICKUP_PINCODE`), `FRONTEND_URL=http://localhost:5173`. Secrets never go to the frontend.
