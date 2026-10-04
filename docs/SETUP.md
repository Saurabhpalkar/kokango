# Setup (local)

You need: **Node 18+**, **PHP 8.2+** (with pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, fileinfo, curl), **Composer 2**, and **MySQL** (or use SQLite for a quick try).

## 1. Install the frontend and root tools
    npm install

## 2. Create the backend (one time)
    # MySQL: first create an empty database called kokango, then:
    npm run setup:backend
    # No MySQL? Use SQLite instead:
    npm run setup:backend:sqlite

This downloads Laravel 12 into `backend/` (our code is kept), installs Spatie Permission and Sanctum, creates `backend/.env`, runs the migrations and seeds the demo data.
If it stops at the database step, open `backend/.env`, fix `DB_USERNAME` / `DB_PASSWORD`, and run `npm run setup:backend` again.

## 3. Run everything
    npm run dev
- Website: http://localhost:5173
- Admin panel: http://localhost:5173/admin (login below)
- API: http://localhost:8000/api/v1

## Demo logins (from the seeder)
| Who | Email | Password |
|---|---|---|
| Admin | admin@kokango.test | Admin@12345 |
| Customer | rohan@example.com | Password@123 |

Change or delete these accounts before going live.

## Payments and shipping
Both run in **fake** mode by default (`PAYMENT_DRIVER=fake`, `SHIPPING_DRIVER=fake` in `backend/.env`): checkout works end to end with no real money and no courier account.
For real use set `PAYMENT_DRIVER=razorpay` with `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` (webhook URL: `https://your-domain/api/v1/payments/razorpay/webhook`), and `SHIPPING_DRIVER=shiprocket` with `SHIPROCKET_EMAIL`, `SHIPROCKET_PASSWORD`, `SHIPROCKET_PICKUP_PINCODE`. Secrets stay in `backend/.env` only.

## Tests
    npm run test:backend

## Hosting on a budget
Shared hosting or a small VPS is enough. Point the web root at `backend/public`, build the website with `npm run build` and upload `frontend/dist`, and set `VITE_API_BASE_URL` in `frontend/.env` to your API address before building. Add one cron line for queued jobs and the scheduler:
    * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
and run `php artisan queue:work --stop-when-empty` from the scheduler or a second cron entry.
