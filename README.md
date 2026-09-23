# Black Rossy — Mobile-First Multi-Category E-commerce

Production-ready Laravel storefront for clothing, jewellery, footwear, bags, beauty, home and gifts.

**Guest checkout · Cash on Delivery only · AdSense-ready · SEO + blog · Admin panel**

## Stack

- Laravel 13 / PHP 8.3+
- MySQL
- Blade + Tailwind CSS 4 + vanilla JS (AJAX cart/search/filters)
- Intervention Image (WebP thumbnails / medium / large)

## Quick start (XAMPP / local)

```bash
cd "/Applications/XAMPP/xamppfiles/htdocs/new project"
composer install
npm install
cp .env.example .env   # if needed
# Set DB_CONNECTION=mysql, DB_DATABASE=shopora, DB_USERNAME=root
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8001
```

Open:

- Store: http://127.0.0.1:8001
- Admin: http://127.0.0.1:8001/admin/login

### Default admin

- Email: `admin@blackrossy.com`
- Password: `password`

### Demo coupon

- `WELCOME10` (10% off, min ₹799)

## Customer flow

Browse → Product → Add to cart / Buy now → Checkout (name, mobile, address) → COD → Order number → Track order (Order ID + mobile). **No customer account required.**

## Admin modules

Dashboard · Products (CRUD, CSV import/export, variants) · Categories · Brands · Orders (status timeline, print, export) · Banners · Homepage sections · AdSense placements · Blog · Legal pages · Coupons · Reviews · Reports · Settings · Mobile blocklist

## AdSense

Manage placements under **Admin → Ads**. Ads are disabled on cart, checkout and order confirmation. Enable globally via Settings → AdSense.

## OTP (optional)

Enable **OTP at checkout** in Settings. In `local`, the OTP is returned in the AJAX response for testing (`debug_otp`).

## Important commands

```bash
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
php artisan cache:clear
php artisan optimize   # production
```

## Production checklist

- `APP_ENV=production`
- `APP_DEBUG=false`
- Secure `APP_KEY`
- HTTPS + session secure cookies
- Queue/mail configuration for order emails
- Paste real AdSense units only after policy-compliant content is live
