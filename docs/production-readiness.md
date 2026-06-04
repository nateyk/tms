# Menkem TMS Production Readiness

Use this checklist before running the tyre management system outside local development.

## Required Runtime

- PHP 8.4 or newer
- Composer 2
- Node.js 22 LTS or newer
- MySQL or MariaDB
- Web server with HTTPS
- Queue worker process manager

## Production Environment

Set these values in production `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
LOG_LEVEL=warning

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tms
DB_USERNAME=tms_user
DB_PASSWORD=change-this
```

Never use seeded demo passwords in production. Create real admin users and rotate any account that was created by demo seeders.

## Deployment Commands

Run from the project root:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run the queue worker continuously:

```powershell
php artisan queue:work --tries=3 --timeout=90
```

## Security Checks

- Admin panel access must require one of the operational roles.
- API tyre lookup routes must stay behind Sanctum authentication.
- Voucher PDF routes must stay behind authentication.
- `APP_DEBUG` must be `false` in production.
- HTTPS must be enabled before setting secure cookies.
- Change all seeded/demo credentials before handing the app to users.
- Keep `composer audit` and `npm audit --audit-level=moderate` clean before each release.

## Release Verification

Run these before every deployment:

```powershell
composer analyse
php artisan test
npm run build
composer audit
npm audit --audit-level=moderate
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

## Manual Acceptance Test

- Login as an admin role user and open the dashboard.
- Confirm a user without an operational role cannot open `/admin`.
- Open Vehicle Types and confirm axle count, tyre count, and spare tyre count display correctly.
- Open a 24 tyre + 2 spare power vehicle and confirm positions A-V plus W and X render.
- Create a store-to-vehicle tyre movement, submit, check, approve, and complete it.
- Confirm completed movement updates tyre status, location, and vehicle tyre map.
- Attach or change a trailer and confirm combined vehicle/trailer tyre maps render clearly.
- Generate a tyre status PDF for a vehicle.
- Search, filter, and view tyres from the admin panel.
