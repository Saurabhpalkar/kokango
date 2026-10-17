# Cash on Delivery and order emails: setup guide

## What was added
- **Cash on Delivery (COD)** at checkout, next to online payment.
- **Emails**: order confirmed (to the customer and to you), shipped, delivered.
- **Admin**: COD orders can be shipped before payment; the money is marked received when you set the order to Delivered. A "Message on WhatsApp" button on every order.

## Step 1: copy the files
Copy the files from the zip into `C:\projects\kokango` keeping the folders (replace when asked).

## Step 2: choose COD settings in `backend\.env`
```
COD_ENABLED=true
COD_MAX_PAISE=0
```
`COD_MAX_PAISE` is a limit in paise. `200000` means COD only up to Rs 2,000. `0` means no limit.
Run `php artisan config:clear` after any `.env` change.

## Step 3: set up email
Where mail goes is controlled by `MAIL_MAILER` in `backend\.env`.

**A. Try without sending anything (default, safest).**
```
MAIL_MAILER=log
```
Emails are written into `backend\storage\logs\laravel.log`. Place a COD order and open that file to see them.

**B. Send real emails with Brevo (free: about 300 emails a day).**
1. Sign up at brevo.com, verify your email address.
2. Open SMTP & API, then SMTP, and create an SMTP key.
3. Put in `backend\.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<your Brevo login email>
MAIL_PASSWORD=<your Brevo SMTP key>
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="orders@yourdomain.com"
MAIL_FROM_NAME="Kokango"
```
4. In Brevo, add and verify your sender address (Senders), and use that same address in `MAIL_FROM_ADDRESS`.

**C. Gmail (small volume only).** Turn on 2-step verification, create an App Password, then `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_USERNAME=<your gmail>`, `MAIL_PASSWORD=<app password>`, `MAIL_FROM_ADDRESS=<your gmail>`.

Run `php artisan config:clear` and restart `php artisan serve`.

## Step 4: tell the app where your own alerts go
Admin panel, Settings, Notification email: type the address that should receive "New order" emails. If it is empty, only the customer gets an email.

## Step 5: test
1. `cd backend` then `php artisan test` (the new tests are in `CodAndNotificationsTest`).
2. Open the shop, add a product, go to checkout, choose **Cash on Delivery**, place the order.
3. You land on the confirmation page showing "Cash On Delivery" and "Pay on delivery".
4. Check the email (or `laravel.log` with `MAIL_MAILER=log`).
5. In the admin: Orders, open the order, **Create shipment**, set the order to Shipped, then Delivered. Check that Payment becomes Paid and the customer gets the shipped and delivered emails.

## Good to know
- On the free InfinityFree host, sending mail by SMTP may be blocked. Test on your PC first. On a paid host it works normally.
- An email error never blocks an order; look for "Order email failed" in `laravel.log`.
- WhatsApp: the button opens WhatsApp with a ready message to the customer. Automatic WhatsApp messages need the paid WhatsApp Business API, which is not included.
- To switch COD off: `COD_ENABLED=false` (the option disappears from checkout).
