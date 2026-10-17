# Updating the live server (InfinityFree), October 2026

Two files in this folder:

- `2026-10-database-update.sql` adds the marketplace tables, the product ingredients / how-to-use columns, the seller role, and switches the 7 Kokango products to the new photos.
- `2026-10-backend-update.zip` contains the new backend code (`laravel/app`, `config`, `database`, `resources`, `routes`, `bootstrap/app.php`). It does **not** contain `public/`, `.env`, `vendor/` or `storage/`, so your server's settings, edited `index.php` and uploaded images are not touched.

Do the steps in this order.

## 1. Back up the database
InfinityFree control panel > MySQL Databases > phpMyAdmin > select your Kokango database > **Export** > Quick > SQL > **Go**. Keep the downloaded file.

## 2. Import the database update
In phpMyAdmin, with the same database selected: **Import** > choose `2026-10-database-update.sql` > **Go**.
You should see a green "Import has been successfully finished". Import it **once only**: a second import stops with "Table 'vendors' already exists" (that is harmless, nothing changes).

## 3. Upload the backend code
1. Open the File Manager (or FileZilla) and go to `htdocs`.
2. Upload `2026-10-backend-update.zip` into `htdocs`.
3. Extract it there. It contains one folder, `laravel/`, that merges into your existing `htdocs/laravel/` folder; allow it to **overwrite** files.
   (If the File Manager cannot extract, use the same `unzip.php` helper as last time, then delete `unzip.php` again.)
4. Delete the zip from `htdocs` afterwards.

## 4. Clear the cached roles
In the File Manager open `htdocs/laravel/storage/framework/cache/data/` and delete everything inside it (keep the `data` folder itself). This makes Laravel see the new seller role straight away.

## 5. Check
- `https://kokango.wuaze.com/api/v1/products` shows the products with `"ingredients"` and `/images/products/...jpg` photos.
- On the website: open a product's quick view (eye button) and check Ingredients / How to use; open `/vendor/register` and create a test seller.
- If you see a server error, set `APP_DEBUG=true` in `htdocs/laravel/.env` for one minute to read the message, then set it back to `false`.

Nothing has to change in the server `.env`. Optional settings: `COD_ENABLED`, `COD_MAX_PAISE` (see `docs/COD_AND_EMAILS.md`) and `API_RATE_LIMIT` (default 120).

## If something goes wrong
Import the backup from step 1 (phpMyAdmin > Import) and re-upload your previous `laravel/` files.
