# TMS Phase 0 — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Port legacy TMS backend domain layer into `new/` and establish Inertia + shadcn navigation shell.

**Architecture:** Copy models, migrations, services, policies from `tms/`; wire Spatie RBAC; replace Filament sidebar with grouped shadcn sidebar.

**Tech Stack:** Laravel 12, Inertia React, shadcn/ui, Spatie Permission/Activitylog/Medialibrary, DomPDF, Simple QR Code

---

## Phase 0 Checklist

- [x] Install backend packages (Spatie, DomPDF, QR)
- [x] Copy 25 migrations, 19 models, 15 enums, 16 services
- [x] Copy seeders + fitment data
- [x] Configure RBAC middleware aliases
- [x] Share roles/permissions via HandleInertiaRequests
- [x] TMS sidebar navigation (4 groups, 15 items)
- [x] Placeholder routes for all modules
- [x] Voucher PDF + QR scan routes
- [x] API routes registered
- [ ] Fix TypeScript build (profile form null user)
- [ ] `php artisan storage:link`
- [ ] Port feature tests from legacy TMS

## Phase 1 — Administration (Next)

### Task 1: Users CRUD
- List with shadcn Table + role badges
- Create/Edit with role Select
- Delete with AlertDialog

### Task 2: Roles & Permissions
- Read-only permission matrix per role
- Create role (settings.manage)

### Task 3: System Settings
- Key/value form for company name, max trailers per power unit
- Uses SystemSetting model + seeder defaults

## Login Credentials (Seeded)

| Email | Password | Role |
|-------|----------|------|
| admin@menkem.com | password | Super Admin |
| store@menkem.com | password | Store Manager |
| manager@menkem.com | password | Company Manager |

## Dev Commands

```bash
cd c:\laragon\www\tms-pro\new
composer install
npm install
php artisan migrate:fresh --seed
php artisan storage:link
npm run dev
php artisan serve
```

Login: http://localhost:8000/login
