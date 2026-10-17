# Kokango: AI Handoff Document

**Purpose of this file.** Upload or paste this file at the start of a chat with any AI assistant (ChatGPT, Gemini, Claude, Copilot, etc.). It explains the whole project so the assistant can help without re-discovering everything. Keep it up to date when the project changes.

Last updated: 2026-10-04. Status: **works fully on the owner's PC; free online deployment is half done** (see section 12).

---

## 0. First message to paste with this file

> I am the owner of the Kokango e-commerce project. Read the attached AI_HANDOFF.md fully. Then act as my senior full-stack developer. I am not an expert: explain in simple steps, say exactly which file to edit and what to type, and give Windows (PowerShell) commands. Do not redesign the website. Ask me for a file or an error message when you need it instead of guessing. My question is: ...

## 1. What Kokango is

An online shop for Konkan food products: snacks (jackfruit chips), sweets (jamun vadi, halim ladoo) and herbal powders (moringa, curry leaf, tulsi, neem, beetroot). Low budget, India only, prices in rupees (INR), small catalogue (about 8 products, each with 1 to 3 sizes).

Two parts:

| Part | Tech | Folder |
| --- | --- | --- |
| Website (customers and admin panel) | Vue 3 (JavaScript, no TypeScript), Vite 6, Vue Router 4, Pinia, Axios, plain CSS (no UI framework) | `frontend/` |
| API | Laravel 12 (PHP 8.2+), MySQL 8 (SQLite possible), Laravel Sanctum (API tokens), Spatie laravel-permission (roles) | `backend/` |

The owner uses **Windows**, project path `C:\projects\kokango`, local MySQL (Laragon/XAMPP style), PowerShell. Use `npm.cmd` if `npm` is blocked by PowerShell policy.

## 2. Folder structure

```
KOKANGO/
  backend/      Laravel code (only OUR files; Laravel itself is created by the setup script)
  frontend/     Vue app
  storage/      uploaded product images
  docs/         API.md, DATABASE.md, SETUP.md, PROJECT_MANUAL.md, AI_HANDOFF.md (this file)
  scripts/      setup-backend.mjs (builds the backend), e2e-api.mjs (automatic API test)
  package.json  root scripts
```

Important: `backend/` in the repo is an **overlay**. It contains `app/`, `routes/`, `database/`, `config/cors.php`, `config/kokango.php`, `bootstrap/app.php`, `tests/` and so on, but not Laravel's `vendor/` or framework files. `node scripts/setup-backend.mjs` (or `npm run setup:backend`) creates a fresh Laravel 12 skeleton, installs `spatie/laravel-permission` and `laravel/sanctum`, copies our files over it, creates `.env`, runs migrations and seeders.

## 3. How to run locally

```
npm install                    # root tools + frontend packages
npm run setup:backend          # one time (or :sqlite if no MySQL)
npm run dev                    # API on :8000, site on :5173
```

Or two terminals: `cd backend` then `php artisan serve`, and `cd frontend` then `npm run dev`.

Reset the demo data: `cd backend` then `php artisan migrate:fresh --seed` (deletes everything in the database named in `backend/.env`, so only use on local).

Demo logins (local only): admin `admin@kokango.test` / `Admin@12345`; customer `rohan@example.com` / `Password@123`.

Local `backend/.env` must use `DB_HOST=127.0.0.1`, `FRONTEND_URL=http://localhost:5173`. Frontend `.env` uses `VITE_API_BASE_URL=http://localhost:8000/api/v1`. Vite only reads `.env` at start: restart `npm run dev` after changes.

## 4. Architecture in one picture

```
Browser (Vue SPA)  --Axios, JSON, Bearer token + X-Cart-Token-->  Laravel API (/api/v1)  -->  MySQL
                                                                      |
                                              interfaces: PaymentGateway (fake | razorpay)
                                                          ShippingProvider (fake | shiprocket)
```

Rules that must not be broken:

1. **The backend calculates all prices and totals.** The frontend never decides a price. Checkout re-prices the cart from the database.
2. **Money is stored and sent as integer paise** (`price_paise`, `total_paise`). The frontend converts for display (`frontend/src/utils/format.js`: `inrp(paise)`).
3. **Order, payment and shipment are separate records.** Never infer "shipped" from "paid".
4. **Stock is per variant** (a size of a product). It is reserved when the order is created (inside a DB transaction with `lockForUpdate`), restored if payment fails or the order is cancelled. An artisan command `kokango:expire-pending-orders` (scheduled every 15 minutes) releases stock of abandoned unpaid orders.
5. **Payment and shipping go through interfaces** with fake drivers, chosen by `.env` (`PAYMENT_DRIVER=fake|razorpay`, `SHIPPING_DRIVER=fake|shiprocket`). Real keys are not set yet. The fake payment verifies with signature `fake`, which `FakeGateway` refuses when `APP_ENV=production`: on a production server use `PAYMENT_DRIVER=razorpay` (Razorpay test keys are fine) or checkout fails with "Payment verification failed".
6. **Do not redesign the UI.** The visual design (light theme) was approved. Change behavior, fix bugs, add features in the same style.
7. Vendor/marketplace features are out of scope for the MVP.

## 5. Backend map (`backend/app`)

| Area | Files |
| --- | --- |
| Routes | `routes/api.php`, prefix `/api/v1` set in `bootstrap/app.php` |
| Auth | `Http/Controllers/Api/V1/Auth/AuthController.php` (register, login, logout, me, profile, password, forgot/reset) |
| Customer API | `Customer/` Catalogue, Cart, Checkout, Payment, Order, Shipment, Shipping, Address controllers |
| Admin API | `Admin/` Dashboard, Product, Category, Inventory, Order, Payment, Shipment, Customer, Setting, User controllers (all behind `auth:sanctum` + `role:admin`) |
| Business logic | `Services/`: `CartService`, `CheckoutService`, `OrderService`, `PricingService`, `Payment/*`, `Shipping/*` |
| Validation | `Http/Requests/**` (Form Requests) |
| Output shape | `Http/Resources/**` (JSON resources) |
| Models | User, Category, Product, ProductVariant, StockMovement, Cart, CartItem, Address, Order, OrderItem, Payment, Shipment, Setting |
| Middleware | `ForceJsonResponse`, `EnsureUserIsActive` (alias `active`) |
| Config | `config/kokango.php` (drivers, `low_stock_threshold`=10, `max_cart_qty`=20), `config/cors.php` (allowed origin from `FRONTEND_URL`, plus localhost:5173) |
| Seeders | Role, Admin, Setting, Catalogue (7 products), DemoOrder |

API conventions: success responses are `{ "data": ... }` (lists add pagination `links`/`meta`); errors are `{ "message": ..., "errors": {field: [..]} }` with HTTP 401/403/404/409/422. Guest carts use header `X-Cart-Token`. Guest orders are accessed with the order's `token`. Order numbers look like `KKG-2026-000001`. The cart returns HTTP 201 when it creates a new cart and 200 otherwise.

Full endpoint list: `docs/API.md`. Full table list: `docs/DATABASE.md`.

### Status values

- Order: `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled`
- Payment: `pending`, `paid`, `failed`, `refunded`
- Shipment: `pending`, `ready_to_ship`, `pickup_scheduled`, `picked_up`, `in_transit`, `out_for_delivery`, `delivered`, `returned`, `cancelled`

### Pricing formula

`subtotal = sum(unit_price_paise x qty)`; shipping from settings (standard 6000, express 12000 paise; free standard above a set amount); `tax = round(subtotal x gst_percent / 100)` (GST default 5%); `total = subtotal + shipping + tax - discount`.

## 6. Frontend map (`frontend/src`)

| Area | Where |
| --- | --- |
| Entry | `main.js`, `App.vue`, `router/index.js` (guards: `/admin/*` needs admin; `/account`, `/orders` need login) |
| Screens | `views/public` (Home, ProductList, ProductDetail, About, Faq, Policy, NotFound), `views/auth` (Login, ForgotPassword, ResetPassword), `views/customer` (Cart, Checkout, OrderConfirmation, OrderTracking, TrackOrder, Profile), `views/admin` (Login, Dashboard, ProductList, ProductForm, Categories, Inventory, OrderList, Payments, Shipments, Customers, Users, Settings) |
| State | `stores/auth.js`, `stores/cart.js` (mirrors the server cart), `stores/toast.js` |
| API calls | `services/api.js` (Axios client, token interceptors, `apiError()` helper), plus `catalogueService`, `checkoutService`, `orderService`, `addressService`, `shippingService`, `adminService` |
| Shared | `components/` (SiteHeader, SiteFooter, AnnouncementBar, ToastHost, WhatsAppFab, OrderTracker), `layouts/AdminLayout.vue`, `utils/format.js`, `utils/links.js`, `utils/orderStatus.js`, `content/` (about, faq, policy text) |
| Styles | `style.css` (CSS variables such as `--mango`, `--ink`, `--muted`; brand green about `#0B5D1E`), mostly inline and scoped styles |
| Static files | `public/images/*.png` (product images, logo), `public/videos`, `public/_redirects` |

Browser storage keys: `kokango_token` (login), `kokango_cart_token` (guest cart).

Environment variables (build time, `VITE_` prefix): `VITE_API_BASE_URL`, `VITE_WHATSAPP_URL`, `VITE_FACEBOOK_URL`, `VITE_INSTAGRAM_URL`, `VITE_COURIER_TRACK_URL`.

The checkout page already contains the Razorpay Checkout flow (loads `checkout.razorpay.com`, then calls `/payments/verify` or `/payments/failed`). With `PAYMENT_DRIVER=fake` the backend accepts signature `fake`.

Responsive: customer and public pages were audited for mobile; no horizontal overflow at phone width.

## 7. Testing

- **Automatic API test (36 checks, all passing locally):** start the backend, then `npm.cmd run test:e2e` from the project root. It walks shop, accounts, cart, checkout, fake payment, tracking, cancel, admin security and admin tools. Options: `API_URL`, `WEB_ORIGIN` (also tests CORS), `ADMIN_LOGIN`, `ADMIN_PASSWORD`. It creates "E2E" data, so use a test database. If stock runs out after many runs, reset with `php artisan migrate:fresh --seed`.
- **PHP tests:** `cd backend` then `php artisan test` (Feature: AuthAndCatalogueTest, CheckoutFlowTest; Unit: PricingServiceTest).
- No automated browser (UI) tests exist. Layout and clicks were checked with scripted browser audits during development, not kept in the repo.

## 8. Documentation in the repo

`docs/SETUP.md` (install), `docs/API.md` (endpoints), `docs/DATABASE.md` (tables and rules), `docs/PROJECT_MANUAL.md` (customer and admin manual, SDLC, troubleshooting), this file. A separate "Kokango deployment guide" (Claude Doc) describes the free hosting steps.

## 9. Deployment plan (free tier)

| Part | Service | URL |
| --- | --- | --- |
| Website | Netlify (from GitHub repo `kokango`, branch `main`; base dir `frontend`, build `npm run build`, publish `frontend/dist`, `NODE_VERSION=20`, `VITE_*` set in Netlify) | https://kokango.netlify.app |
| API + MySQL | InfinityFree free hosting, folder `htdocs` | https://kokango.wuaze.com/api/v1 |

How the Laravel app is laid out on the shared host (no SSH, no Composer, no artisan): the whole app (with `vendor/`) in `htdocs/laravel/`; the contents of `backend/public` in `htdocs/`; `htdocs/index.php` edited so every `/../` becomes `/laravel/`; `htdocs/laravel/.htaccess` containing `Require all denied`; the database loaded by importing a SQL dump in phpMyAdmin. Files were sent as one zip and unpacked with a helper `unzip.php` (deleted after use). Server `.env` differs from local: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `FRONTEND_URL=https://kokango.netlify.app`, fake shipping driver; payment must be `razorpay` (test keys) because the fake payment is refused in production.

## 10. Known limits of the free host

No terminal, 10 second script limit, no cron or queue worker (so the expire-pending-orders schedule does not run), no `storage:link` (admin image uploads may not display; use `/images/...` paths or `https://` image links), possible JavaScript "browser check" cookie on first visit that can block cross-site API calls, inactivity suspension. Fallback if cross-site calls fail: serve the built site from the same InfinityFree host.

## 11. Security notes (important)

- Demo passwords above are public. Change the admin password before going live.
- The database password was once shared in a chat: it must be changed (InfinityFree account password) and then updated in the server `.env`.
- Never commit `.env`; never paste real secrets into an AI chat. Use placeholders like `xxxx`.
- `APP_DEBUG` must be `false` on any public server. `https://<site>/laravel/.env` must return Forbidden.
- Delete helper files (`unzip.php`, `laravel.zip`, `check.php`) from the server after use.

## 12. Current status and open work

Done and working locally: shop, cart, checkout with fake payment, orders, tracking, cancel with refund and stock restore, accounts and addresses, admin (products, categories, inventory, orders, payments, shipments, customers, users, settings), responsive pages, documentation, 36 automatic API tests.

Open items, in priority order:

1. **Netlify**: opening `/products` directly showed "Page not found" because `frontend/public/_redirects` was a folder (`_redirects/index.html`) instead of a file. Fixed 2026-10-15: it is now a plain file with `/*  /index.html  200`; commit, push and redeploy.
2. **InfinityFree API**: `https://kokango.wuaze.com/api/v1/products` returns InfinityFree's generic 404. Likely the `public` files or `.htaccess` are not directly in `htdocs`, or the app folder is misplaced. A diagnostic script `check.php` (lists files, shows `index.php` paths, tries to boot Laravel) was prepared but not yet run.
3. Set the new database password and `FRONTEND_URL` in the server `.env`, then run the API test against the live URL.
4. Cash on Delivery and order emails are built (see `docs/API.md` section "Cash on Delivery"): `payment_method: cod` at checkout, `COD_ENABLED`/`COD_MAX_PAISE` in `.env`, `OrderNotifier` + `OrderMail`. Needs a real SMTP account in `MAIL_*` to send real emails; tests in `backend/tests/Feature/CodAndNotificationsTest.php`.
5. Add Halim Ladoo to `CatalogueSeeder` (image `public/images/halim-ladoo.png` exists; not yet a product).
6. Before real customers: real domain, stable paid host with SSH, Razorpay and Shiprocket keys (`PAYMENT_DRIVER=razorpay`, `SHIPPING_DRIVER=shiprocket`), working email (`MAIL_*`) for password reset, real policy text, backups.

Ideas not started: coupons/discounts (the `discount_paise` column exists), product reviews, wishlist, order e-mails/WhatsApp notifications, invoice PDF, reports export, multi-image products.

## 13. How an AI assistant should work on this project

- Read the relevant files before changing them; ask the owner to paste a file if you cannot see it. Give complete file contents or exact search and replace edits, with the file path.
- Keep the design and all existing behavior; make the smallest change that solves the problem; keep Laravel conventions (Form Requests, Resources, Services) and Vue conventions (Pinia stores, service files, no UI framework).
- Money in paise on the backend; never trust the client for prices; keep transactions and `lockForUpdate` around stock.
- After a backend change, tell the owner to run `npm.cmd run test:e2e` and `php artisan test`; add or adjust tests when behavior changes.
- Explain Windows steps simply (which folder, which command, what output to expect). Remind the owner to use `npm.cmd` when PowerShell blocks `npm`.
- When something fails, ask for the exact error text, the URL, and the browser console (F12) output.
