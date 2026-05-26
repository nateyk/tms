# Menkem Tyre Management System (TMS)

Production-ready tyre lifecycle management for **Menkem International Business PLC**.

## Stack

- Laravel 13 (PHP 8.3+)
- Filament 4 Admin Panel
- Livewire, Alpine.js, Tailwind CSS
- MySQL/MariaDB (SQLite supported for local dev)
- Spatie: Permission, Activity Log, Media Library
- Laravel Sanctum (API-ready)
- DomPDF, Simple QR Code

> **Note:** `maatwebsite/excel` is not installed yet because PHP 8.5 exceeds current `phpoffice/phpspreadsheet` constraints. Use CSV exports or install Excel when spreadsheet library supports PHP 8.5.

## IDE / static analysis (fix red squiggles in Cursor)

The app runs correctly; red marks are usually **Intelephense** not knowing Laravel/Filament types.

```bash
cd tms
composer ide-helper    # generates _ide_helper.php
```

Then in Cursor/VS Code: **Developer: Reload Window** (or restart the PHP language server).

- `composer analyse` — PHPStan (level 5, with baseline)
- `composer test` — PHPUnit

## Repository

GitHub: [https://github.com/nateyk/tms](https://github.com/nateyk/tms)

## Quick start

```bash
git clone https://github.com/nateyk/tms.git
cd tms
cp .env.example .env
php artisan key:generate
# Configure DB_CONNECTION (sqlite or mysql)
php artisan migrate:fresh --seed
php artisan serve
```

**Admin panel:** http://localhost:8000/admin  

**Login:** `admin@menkem.com` / `password`

**QR scan example:** http://localhost:8000/tyres/scan/TYR-0001

### Mobile / API tyre lookup (Sanctum)

Create a token for a user (e.g. warehouse staff with `tyre.view`):

```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'admin@menkem.com')->first();
>>> $user->createToken('mobile-scanner')->plainTextToken;
```

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/tyres/TYR-0001
```

## Seeded data

- Roles & permissions (8 roles)
- Main Tyre Store
- Vehicle types: Power Unit 10 Tyres, Trailer 12 Tyres, Rigid Truck 6 Tyres
- Vehicles: TRK-001, TRK-008, TRL-045 (trailer attached to TRK-001)
- Tyres: TYR-0001 … TYR-0020
- Sample assignments on TRK-001 and TRL-045

## Core business rules (enforced in services)

1. One active location per tyre
2. One active assignment per tyre / per vehicle position
3. Tyre location updates only on movement **completion**
4. Trailer transfer changes power–trailer combination only (not tyre assignments)
5. Disposed tyres cannot move
6. Pending movements block new movements

## Project structure

```
app/
  Enums/           # Domain enums
  Models/          # Eloquent models
  Services/        # Business logic (movements, transfers, reports, etc.)
  Livewire/        # VehicleTyreMap UI
  Filament/        # Admin resources
database/
  migrations/      # Full TMS schema
  seeders/         # Roles + sample fleet data
tests/Feature/     # Critical movement rule tests
```

## Tests

```bash
php artisan test --filter=TyreMovementBusinessRulesTest
```

## Approval workflow (Filament)

On movement, trailer transfer, and disposal edit screens:

1. **Submit** (draft → submitted)
2. **Check** (submitted → checked)
3. **Approve** (checked → approved)
4. **Complete** (approved → completed; applies inventory changes)

Also: **Reject**, **Download PDF**, and **Delete** (draft only).

**Pending Approvals** page: Admin → Pending Approvals

## PDF vouchers

Authenticated routes under `/vouchers/...` (open from Filament actions):

- Movement, trailer transfer, maintenance, disposal
- Tyre registration & history card
- Vehicle tyre status report

## Reports

**Reports** page (date range + optional vehicle filter) exports CSV:

- Tyre stock, by vehicle, lifecycle, KM performance
- Movements, maintenance, disposals, trailer transfers
- Audit trail

## Audit logs

**Audit Logs** in admin (requires `audit.view`) — browse Spatie activity log with filters.

## System settings

**Settings** (requires `settings.manage`) — company name, max trailers per power unit.

## Dashboard

- **Stats** — fleet counts, store stock, pending approvals (links to workflows)
- **Charts** — tyres by status, location, completed movements trend, fleet position fill
- **UI** — full-width layout, Inter font, shadcn-style cards on Reports / Pending Approvals

After pulling UI changes, run:

```bash
npm install && npm run build
php artisan filament:assets
```

## Vehicle maps (Konva.js)

Interactive **2D axle tyre diagram** (Konva.js) on each vehicle view — steer + dual axles like a real truck layout:

- Click a tyre to see code, brand, tread depth, and link to the tyre record
- Power units: combined power + attached trailer maps
- Color legend matches tyre status (active, empty, maintenance, etc.)

After pulling map changes, run `npm run build` and refresh layouts:

```bash
php artisan db:seed --class=TmsSampleDataSeeder
# or: php artisan migrate:fresh --seed
```

## Tyre registration & QR

1. **Tyres → Create** — saves as `pending_approval` in default store  
2. **View tyre → Approve Registration** — sets `available`, generates QR SVG in `storage/app/public/tyres/qr/`  
3. **QR Profile** link / scan page shows QR image  

Run once: `php artisan storage:link`

## Maintenance workflow

Submit → Approve → Start Work → Complete (on maintenance edit screen)

## MariaDB (optional)

See `.env.mysql.example` — create DB, update `.env`, then `php artisan migrate:fresh --seed`

## Next phases (planned)

- Excel export when PHP 8.5 supported by phpspreadsheet
- MariaDB production deployment (see `.env.mysql.example`)

## License

Proprietary — Menkem International Business PLC.
