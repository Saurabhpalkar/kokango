# Kokango on InfinityFree (fresh install, website + API on one domain)

Domain: **kokango-api.site.je** · Database: **if0_43198742_kokango** on **sql103.infinityfree.com**

The whole shop runs on InfinityFree: the website and the Laravel API share one domain. That avoids InfinityFree's
"browser check", which blocks a website on another domain (like Netlify) from calling the API.

## Update an existing install (October 2026: coupons, reviews, WebP photos, new About page)
Use this when the shop is already running. Your `laravel/.env` (MySQL password) and `.htaccess` are **not** touched.

| File | What it is |
| --- | --- |
| `kokango-update-database.sql` | adds the `coupons` and `product_reviews` tables and `orders.coupon_code` |
| `kokango-update1.zip` | new website code, product photos, the updated Laravel code |
| `kokango-update2.zip` | site photos (new About photos + WebP copies) |
| `kokango-update-unzip.php` | unpacks the two zips over the existing files |

1. **Database:** phpMyAdmin > `if0_43198742_kokango` > **Import** > `kokango-update-database.sql` > **Go**. Run it only once.
2. **Upload:** File Manager > `htdocs` > upload `kokango-update1.zip`, `kokango-update2.zip` and `kokango-update-unzip.php`.
3. **Unpack:** open `https://kokango-api.site.je/kokango-update-unzip.php?key=YOUR_KEY` (the key is in the first lines of
   `kokango-update-unzip.php`) and wait for **Done**.
4. **Clean up:** delete `kokango-update-unzip.php` and both zips. Optionally delete the old files in `htdocs/assets` that
   are not dated today (the new `index.html` no longer uses them; leaving them does no harm).
5. **Check:** open the shop with a hard refresh (Ctrl+F5): the About page shows the photo cards; admin shows **Coupons** and
   **Reviews**; a product page shows **Reviews**; checkout shows the coupon box.

## Fresh install

Files in this folder (keep them private; they are not uploaded to GitHub):

| File | What it is |
| --- | --- |
| `kokango-database.sql` | all tables, settings, the 7 Kokango products and your admin account |
| `kokango-part1.zip`, `part2`, `part3` | the website + the Laravel app with its libraries (each under 10 MB) |
| `kokango-unzip.php` | unpacks the zips on the server in small steps |

## 1. Turn on HTTPS (free SSL) first
The domain must open in a browser first (new domains can take a few hours, see InfinityFree's notice).
InfinityFree dashboard > your account > **SSL Certificates** (or *Free SSL*) > choose **kokango-api.site.je** > request the free certificate > follow the verification step > **Install**.
Wait until `https://kokango-api.site.je` opens with the padlock.

## 2. Check the PHP version
Dashboard > account > **Settings / Advanced > PHP version**: choose **8.2 or newer** (8.3 is fine).

## 3. Import the database
Dashboard > **MySQL Databases** > **phpMyAdmin** next to `if0_43198742_kokango` > **Import** > choose `kokango-database.sql` > **Go**.
You should see "Import has been successfully finished".

## 4. Upload the files
Dashboard > **Files > Online File Manager** > open **htdocs**.
1. Delete the default files in `htdocs` (for example `index2.html`).
2. Upload `kokango-part1.zip`, `kokango-part2.zip`, `kokango-part3.zip` and `kokango-unzip.php` into `htdocs`.

## 5. Unpack
Open in your browser (the key is in the first lines of `kokango-unzip.php`):
```
https://kokango-api.site.je/kokango-unzip.php?key=YOUR_KEY
```
It shows a percentage and reloads by itself; after a minute or two it says **Done**.
Then **delete `kokango-unzip.php` and the three zip files** in the File Manager.

## 6. Add the MySQL password
In the File Manager open **htdocs/laravel/.env** (edit) and replace `PASTE_YOUR_MYSQL_PASSWORD_HERE` with your MySQL password
(InfinityFree > MySQL Databases > MySQL password > copy button). Save.

## 7. Check
- `https://kokango-api.site.je/api/v1/products` shows the products as text (JSON).
- `https://kokango-api.site.je` shows the shop.
- `https://kokango-api.site.je/admin/login`: log in with **saurabh@thedesignhut.com** and the admin password you were given. Change it after logging in if you like (admin > Users).
- `https://kokango-api.site.je/laravel/.env` must show **Forbidden** (your settings are protected).

## Limits of the free plan
- **Online payment:** refused until you add Razorpay keys in `laravel/.env` (`PAYMENT_DRIVER=razorpay` + keys). Cash on Delivery works.
- **Photo uploads** in the admin or seller panel are saved but cannot be shown (InfinityFree allows no storage link). Use photo paths like `/images/products/moringa-powder.jpg` or `https://` image links instead.
- **No scheduled jobs:** the job that frees stock from abandoned *online* payments does not run. Cash on Delivery orders are not affected.
- **Netlify:** the old Netlify site cannot use this API (browser check). Use `https://kokango-api.site.je` as the shop address.

## If something goes wrong
- A blank page or "500 error": set `APP_DEBUG=true` in `laravel/.env`, reload to read the message, then set it back to `false`.
- "Access denied for user": the MySQL password in `laravel/.env` is wrong.
- Pages other than the home page show "404": check that `htdocs/.htaccess` exists (the File Manager may hide files starting with a dot; it is written as the very last step of the unpacking).
