# Kokango API on Render (free) with an Aiven MySQL database (free)

- **Website:** stays on Netlify.
- **API (Laravel):** runs on Render as a Docker container (`backend/Dockerfile`, settings in `render.yaml`).
- **Database:** Aiven's free MySQL (Render's own free database is deleted after 30 days, so it is not used).

No credit card is needed for either. Plan about 30 minutes.

## 1. Create the free MySQL database (Aiven)
1. Sign up at https://aiven.io (you can use your Google or GitHub account).
2. **Create service > MySQL > Free plan**. Pick the region closest to India that the free plan offers, give it a name (e.g. `kokango-db`), click **Create**.
3. Wait until the status is **Running** (a few minutes), then open the service. On **Overview > Connection information** you need:
   - **Host** (like `kokango-db-yourname.e.aivencloud.com`)
   - **Port** (a number like `12345`)
   - **User** `avnadmin` and its **Password** (click the eye icon)
   - **CA certificate**: click *Show* and keep the text open (it starts with `-----BEGIN CERTIFICATE-----`).

## 2. Create the API on Render
1. Sign up at https://render.com with **GitHub** and allow access to the `kokango` repository.
2. Dashboard > **New > Blueprint** > choose `Saurabhpalkar/kokango` > Render finds `render.yaml` and shows the service **kokango-api**.
3. Fill in the values it asks for:

   | Setting | Value |
   | --- | --- |
   | `APP_URL` | `https://kokango-api.onrender.com` (Render shows the real address after creation; if it differs, correct it later under *Environment*) |
   | `DB_HOST` | Aiven **Host** |
   | `DB_PORT` | Aiven **Port** |
   | `DB_PASSWORD` | Aiven **Password** |
   | `DB_CA_CERT` | the whole Aiven **CA certificate** text, including the BEGIN/END lines |
   | `ADMIN_EMAIL` | your email (your admin login) |
   | `ADMIN_PASSWORD` | a strong password you choose (not a demo password) |

4. Click **Apply**. The first build takes 5 to 10 minutes. Follow it under the service's **Logs**; it ends with Apache starting.
5. Test it: open `https://kokango-api.onrender.com/api/v1/products` (your address). You should see the 7 Kokango products as JSON.

## 3. Point the website at the new API
Netlify > your site > **Site configuration > Environment variables** > set
`VITE_API_BASE_URL` = `https://kokango-api.onrender.com/api/v1` > **Deploys > Trigger deploy**.
Then log in to `https://kokango.netlify.app/admin/login` with your `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

## 4. Things to know about the free plans
- **Sleeping:** a free Render service sleeps after 15 minutes without visitors; the next visit then waits about a minute. To keep it awake, add a free monitor at https://uptimerobot.com (HTTP(s), URL `https://kokango-api.onrender.com/up`, every 5 minutes). Render's 750 free hours a month are enough for one service running all month.
- **Uploaded photos are not kept:** the container's disk is reset on every deploy and restart, so photos uploaded in the admin or seller panel disappear. Until you move to paid hosting or add cloud storage, use photo links instead of uploads (paths like `/images/products/moringa-powder.jpg`, or `https://...` image links).
- **Aiven idle power-off:** a free Aiven database that is not used for a long time is powered off; Aiven emails you first and you can switch it back on in the console. A shop with visitors keeps it active.
- **Online payment:** with `PAYMENT_DRIVER=fake` online payment is refused on a live server; Cash on Delivery works. Add Razorpay later (step 6).

## 5. Updating
Push to the `main` branch on GitHub. Render rebuilds the API automatically when files in `backend/` change (and Netlify rebuilds the website). Database changes (migrations) run automatically on start.

## 6. Payments, email, shipping (when ready)
Render > kokango-api > **Environment** > add or change, then **Save changes** (it redeploys):
- Razorpay: `PAYMENT_DRIVER=razorpay`, `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`
- Email: `MAIL_MAILER=smtp` and the `MAIL_...` settings from `docs/COD_AND_EMAILS.md`
- Shipping: `SHIPPING_DRIVER=shiprocket`, `SHIPROCKET_EMAIL`, `SHIPROCKET_PASSWORD`, `SHIPROCKET_PICKUP_PINCODE`

## Optional: bring your existing orders and customers from InfinityFree
Do this **before** step 2 (before the API first starts):
1. InfinityFree phpMyAdmin > Export > Quick > SQL, save as `export.sql`.
2. Save the Aiven CA certificate text as `ca.pem` next to it.
3. In PowerShell, in that folder, with the `mysql.exe` from your local MySQL (XAMPP: `C:\xampp\mysql\bin\mysql.exe`):
   ```
   & "C:\xampp\mysql\bin\mysql.exe" -h <Aiven host> -P <Aiven port> -u avnadmin -p --ssl-ca=ca.pem defaultdb -e "source export.sql"
   ```
   (it asks for the Aiven password)
4. Then do step 2. The API adds the new tables on its first start and keeps your data. Your old admin account works; `ADMIN_EMAIL` only creates a new admin if that email does not exist yet.

## If something goes wrong
- Render > kokango-api > **Logs** shows the error. "Access denied" or "SSL" errors mean a database value is wrong; check `DB_HOST`, `DB_PORT`, `DB_PASSWORD`, `DB_CA_CERT` under *Environment*.
- To read a detailed error once, set `APP_DEBUG=true`, save, look, then set it back to `false`.
