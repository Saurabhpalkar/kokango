# Kokango backend (Laravel 12 API)

REST API under `/api/v1` for the Vue storefront and admin panel. The contract lives in
[`../docs/API.md`](../docs/API.md) and the schema in [`../docs/DATABASE.md`](../docs/DATABASE.md).

## How this folder is organised (overlay)

This folder holds **only the files we own**. It is not a complete Laravel project until you run the
setup script, which creates a fresh `laravel/laravel ^12.0` skeleton, copies every skeleton file that
is missing here (our files always win), then installs `spatie/laravel-permission` and `laravel/sanctum`.

```
bootstrap/app.php            routing (api prefix api/v1), middleware aliases, JSON error handling
routes/api.php               every endpoint of docs/API.md
app/Models                   Eloquent models (User, Product, Order, ...)
app/Http/Controllers/Api/V1  Auth/, Customer/ (public + shopper), Admin/
app/Http/Requests            FormRequest validation
app/Http/Resources           API resources (User, Product, Cart, Address, Admin/*)
app/Services                 CartService, PricingService, checkout/payment/shipping services
app/Helpers                  ImageUrl (image_url resolution), Slug
database/migrations          2026_10_03_* tables (Sanctum + Spatie migrations are published by the setup script)
database/seeders            roles, accounts, catalogue, settings, demo orders
config/cors.php             CORS for the Vue dev server
```

## Setup

From the project root (needs PHP 8.2+ and Composer):

```
node scripts/setup-backend.mjs
node scripts/setup-backend.mjs --sqlite   # no MySQL? use a local SQLite file
```

The script is re-runnable; finished steps are skipped. It creates `backend/.env` from `.env.example`,
generates the app key, links `public/storage`, then runs `php artisan migrate --seed --force`.

For MySQL create the database first: `CREATE DATABASE kokango CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
and check `DB_*` in `backend/.env`.

## Run

```
cd backend
php artisan serve              # http://localhost:8000  (API at /api/v1)
php artisan queue:work --stop-when-empty
php artisan schedule:work      # local: releases stock of abandoned unpaid orders (kokango:expire-pending-orders, every 15 min)
```

Run the test suite (in-memory SQLite, needs `pdo_sqlite`; run the setup script first so Spatie/Sanctum migrations exist):

```
cd backend
php artisan test
```

Production/shared hosting cron entries:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/backend && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

## Test accounts (seeded)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@kokango.test` | `Admin@12345` |
| Customer | `rohan@example.com` | `Password@123` |

Seed data also includes 3 categories, 7 products with variants, settings and demo orders
`KKG-2026-000112` to `KKG-2026-000123` (with payments and shipments). Seeders can be re-run safely.

## Environment keys (`backend/.env`)

| Key | Meaning |
|---|---|
| `APP_URL` | Public URL of the API (used for storage URLs of uploaded images) |
| `APP_TIMEZONE` | `Asia/Kolkata` (order timestamps and the order-number year) |
| `FRONTEND_URL` | Vue app origin: CORS allow-list and password-reset links (`/reset-password?token=...`) |
| `DB_*` | Database connection (`mysql`, or `sqlite` via `--sqlite`) |
| `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER` | `database` by default |
| `MAIL_MAILER` | `log` writes mails to `storage/logs/laravel.log` |
| `PAYMENT_DRIVER` | `fake` (default) or `razorpay`, with `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` |
| `SHIPPING_DRIVER` | `fake` (default) or `shiprocket`, with `SHIPROCKET_EMAIL`, `SHIPROCKET_PASSWORD`, `SHIPROCKET_PICKUP_PINCODE` |
| `SANCTUM_STATEFUL_DOMAINS` | Only used for cookie auth; the SPA uses bearer tokens |

Secrets stay in `backend/.env` and are never sent to the frontend.

## Conventions

- Money is integer paise (`*_paise`); the server recalculates every amount.
- Roles `admin` and `customer` (Spatie, guard `web`); Sanctum personal access tokens authenticate API calls.
- Optional-auth routes (cart, checkout, shipping, order lookup, payment verify, tracking) resolve the user with
  `Auth::guard('sanctum')->user()`, so guests and logged-in users share them.
- Guest carts are identified by the `X-Cart-Token` header.
