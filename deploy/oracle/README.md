# Kokango API on Oracle Cloud (Always Free)

The website stays on Netlify. Only the Laravel API moves to a free Oracle server. Plan about an hour the first time.

## 1. Create the Oracle account
1. Sign up at https://www.oracle.com/cloud/free/ . A card is needed for verification; the Always Free resources are not charged.
2. **Home region:** choose **India West (Mumbai)** or **India South (Hyderabad)**. This cannot be changed later, and free servers only run in the home region.

## 2. Create the server
Oracle console > **Compute > Instances > Create instance**:
- **Image:** Canonical **Ubuntu 24.04**.
- **Shape:** *Ampere* > **VM.Standard.A1.Flex**, 1 OCPU and 6 GB RAM is plenty (free limit: 2 OCPU / 12 GB in total).
  If it says "Out of capacity", try again later, or pick *Specialty and previous generation* > **VM.Standard.E2.1.Micro** (free, 1 GB RAM, also works).
- **Networking:** keep the defaults; make sure **Assign a public IPv4 address** is on.
- **SSH keys:** choose *Generate a key pair* and **download the private key** (keep it safe, you need it to log in).
- Click **Create** and note the **Public IP address**.

## 3. Open the web ports in Oracle's firewall
Instance page > the **Subnet** link > **Security Lists** > *Default Security List* > **Add Ingress Rules**:
- Source CIDR `0.0.0.0/0`, IP protocol TCP, destination port **80**
- the same again for port **443**

(The setup script also opens them in the server's own firewall.)

## 4. Get a free domain name
HTTPS needs a name, not a bare IP (the Netlify site is HTTPS and browsers refuse to call an HTTP API from it).
Free option: https://www.duckdns.org > sign in > create a subdomain such as `kokango` > put the server's public IP in *current ip* > **update ip**.
Your API name is then `kokango.duckdns.org`. (A domain you buy works the same: add an **A record** pointing to the IP.)

## 5. Log in to the server
In PowerShell on your PC (change the key file name and IP):
```
ssh -i C:\Users\saura\Downloads\ssh-key.key ubuntu@YOUR.PUBLIC.IP
```
If Windows complains that the key is "too open", run once: `icacls C:\Users\saura\Downloads\ssh-key.key /inheritance:r /grant:r "$env:USERNAME:R"`

## 6. Run the setup script
On the server:
```
git clone https://github.com/Saurabhpalkar/kokango.git ~/kokango
sudo DOMAIN=kokango.duckdns.org EMAIL=saurabh@thedesignhut.com bash ~/kokango/deploy/oracle/setup.sh
```
It takes 5 to 10 minutes. At the end it prints the API address and where the **admin password** is saved:
```
sudo cat /root/kokango-secrets.txt
```
The admin login uses your email and that password (no public demo passwords on the live server). The 7 Kokango products are added automatically.
**To keep your existing orders and customers** from InfinityFree instead: export them in phpMyAdmin (Export > Quick > SQL), copy the file to the server (`scp -i <key> export.sql ubuntu@YOUR.PUBLIC.IP:~`), then on the server replace the fresh database with it and bring it up to date:
```
sudo mysql -e "DROP DATABASE kokango; CREATE DATABASE kokango CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
sudo mysql kokango < ~/export.sql
cd /var/www/kokango/backend && sudo php artisan migrate --force && sudo -u www-data php artisan config:cache
```
You then log in with your old admin account (the one the script created is replaced). Do this on the new server only, never on the old one.

Check it works: open `https://kokango.duckdns.org/api/v1/products` in your browser.

## 7. Point the website at the new API
Netlify > your site > **Site configuration > Environment variables** > set
`VITE_API_BASE_URL` = `https://kokango.duckdns.org/api/v1` > **Deploys > Trigger deploy**.

## 8. Payments, email (when you are ready)
Edit the settings on the server: `sudo nano /var/www/kokango/backend/.env`
- Razorpay: `PAYMENT_DRIVER=razorpay`, `RAZORPAY_KEY_ID=...`, `RAZORPAY_KEY_SECRET=...`, `RAZORPAY_WEBHOOK_SECRET=...`
- Email: the `MAIL_...` lines, see `docs/COD_AND_EMAILS.md`
- Shipping: `SHIPPING_DRIVER=shiprocket` and the `SHIPROCKET_...` lines

Then apply them: `cd /var/www/kokango/backend && sudo -u www-data php artisan config:cache`
Note: with `PAYMENT_DRIVER=fake` online payment is refused on a production server; Cash on Delivery works.

## Updating later
After you push new code to GitHub, on the server:
```
sudo bash /var/www/kokango/deploy/oracle/update.sh
```

## Good habits
- Back up the database now and then: `sudo mysqldump kokango > ~/kokango-$(date +%F).sql` and download it (WinSCP or `scp`).
- Install security updates monthly: `sudo apt update && sudo apt upgrade -y`.
- Oracle may reclaim free servers that sit almost unused for a long time; a live shop with visitors is normally fine.
