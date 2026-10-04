# Kokango

Konkan snacks, sweets and herbal powders: e-commerce site.

    KOKANGO/
    ├── backend/      Laravel 12 API (code here; Laravel itself is installed by npm run setup:backend)
    ├── frontend/     Vue 3 app (customer site + admin panel)
    ├── storage/      uploaded files (product images)
    ├── docs/         API.md, DATABASE.md, SETUP.md
    └── package.json  root runner

## Quick start (full guide: docs/SETUP.md)

    npm install                 # root tools + frontend
    npm run setup:backend       # one time: creates the Laravel 12 backend, migrates and seeds
                                # (use npm run setup:backend:sqlite if you do not have MySQL)
    npm run dev                 # API on :8000 and website on :5173

Needs Node 18+, PHP 8.2+, Composer 2 and MySQL (or SQLite).
Demo logins: admin `admin@kokango.test` / `Admin@12345`, customer `rohan@example.com` / `Password@123`.

## Where things are
- Website: `/` (home), `/products`, `/product/:slug`, `/cart`, `/checkout`, `/order-confirmation`, `/login`, `/forgot-password`, `/reset-password`, `/account`, `/orders`, `/track-order`, `/about`, `/faq`, `/policy/{shipping,refund,terms,privacy}`
- Admin panel (admin role required, login at `/admin/login`): `/admin`, `/admin/products` (+ `/new`, `/:id`), `/admin/categories`, `/admin/inventory`, `/admin/orders`, `/admin/payments`, `/admin/shipments`, `/admin/customers`, `/admin/users`, `/admin/settings`
- Screens: `frontend/src/views/{public,auth,customer,admin}`; state: `frontend/src/stores`; API calls: `frontend/src/services`
- Backend code: `backend/app` (Controllers/Api/V1/{Auth,Customer,Admin}, Services, Models, Http/Resources), `backend/database`, `backend/routes/api.php`

## How it fits together
The Vue app talks only to the Laravel API (`docs/API.md`). The cart, orders, payments and shipments live on the server; prices and totals are always calculated by the backend. Payment and shipping run in fake mode until you add Razorpay / Shiprocket keys in `backend/.env`.

## Rules
- The approved design is the source of truth. Do not redesign screens.
- The backend calculates all prices and totals; the frontend never decides them.
- Vendor features are future work and are not part of the MVP.
