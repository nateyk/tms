# Menkem Tyre Management System (TMS)

Production-ready tyre lifecycle management for **Menkem International Business PLC**.

## Stack

- Laravel 12 (PHP 8.2+)
- Inertia.js + React + TypeScript
- shadcn/ui + Tailwind CSS
- MySQL/MariaDB (SQLite supported for local dev)
- Spatie: Permission, Activity Log, Media Library
- Laravel Sanctum (API-ready)
- DomPDF, Simple QR Code, Konva.js (vehicle tyre maps)

## Repository

GitHub: https://github.com/nateyk/tms

## Quick start

```bash
git clone https://github.com/nateyk/tms.git
cd tms
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
php artisan serve
```

In a second terminal: `npm run dev`

**App:** http://localhost:8000  
**Login:** `admin@menkem.com` / `password`  
**QR scan example:** http://localhost:8000/tyres/scan/TYR-0001

## Modules (Inertia rebuild)

| Status | Module |
|--------|--------|
| Done | Administration — Users, Roles, Settings |
| Done | Fleet — Vehicle Types, Stores, Vehicles, Dashboard |
| Done | Tyres — registration, QR, approval |
| Done | Tyre Movements — 10 types + voucher workflow |
| Planned | Trailer Transfers, Maintenance, Disposals |
| Planned | Pending Approvals, Reports, Audit Logs |

## Core business rules

1. One active location per tyre
2. One active assignment per tyre and per vehicle position
3. Tyre location updates only on movement completion
4. Trailer transfer changes power–trailer combination only
5. Disposed tyres cannot move
6. Pending movements block new movements for the same tyre

## Voucher workflow

Draft → Submitted → Checked → Approved → Completed (inventory updates on completion)

## Tests

```bash
composer test
php artisan test --filter=TyreMovementBusinessRulesTest
```

## License

Proprietary — Menkem International Business PLC.
