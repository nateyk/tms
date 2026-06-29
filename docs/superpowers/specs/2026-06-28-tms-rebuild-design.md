# TMS Rebuild Design Spec

**Date:** 2026-06-28  
**Project:** Menkem Tyre Management System (TMS)  
**Location:** `c:\laragon\www\tms-pro\new`  
**Legacy source:** `c:\laragon\www\tms-pro\tms`

## Goal

Full functional clone of the legacy Filament TMS as a Laravel + Inertia + React + shadcn/ui application. All business rules, workflows, RBAC, PDF vouchers, QR scan, and API endpoints must be preserved.

## Architecture

- **Backend:** Laravel 12 monolith with domain services ported unchanged from legacy TMS
- **Frontend:** Inertia.js + React + TypeScript + stock shadcn/ui components (no custom UI primitives)
- **Auth:** Laravel Breeze session auth + Spatie Permission RBAC
- **Tyre map:** React Konva port (Phase 3) — same data contract as legacy Konva/Livewire map

## Module Map

| Group | Modules |
|-------|---------|
| Fleet Tyre Operations | Fleet (dashboard), Vehicle Types, Stores, Vehicles, Trailer Transfers |
| Tyre Operations | Tyres, Tyre Movements, Tyre Maintenances, Tyre Disposals |
| Approvals & Reports | Pending Approvals, Reports, Audit Logs |
| Administration | Users, Roles, Settings |

## Non-Negotiable Business Rules

1. One active location per tyre
2. One active assignment per tyre and per vehicle position
3. Tyre location updates only on movement completion
4. Trailer transfer changes power–trailer combination only
5. Disposed tyres cannot move
6. Pending movements block new movements for same tyre

## Workflows

**Vouchers** (movements, transfers, disposals): draft → submitted → checked → approved → completed  
**Maintenance:** draft → submitted → approved → in_progress → completed  
**Tyre registration:** pending_approval → available

## UI Standards

- Use shadcn/ui components via `npx shadcn@latest add` — never fork base components
- Reusable patterns: DataTable for lists, Dialog/Sheet for forms, Badge for status, Sonner for toasts
- Permission-gated nav and actions via `usePermission()` hook

## Phase Plan

| Phase | Scope |
|-------|-------|
| 0 | Foundation — backend port, RBAC, nav shell ✅ |
| 1 | Administration — Users, Roles, Settings |
| 2 | Fleet master data — Vehicle Types, Stores |
| 3 | Vehicles + dashboard stats/charts + Konva map |
| 4 | Tyres — registration, QR, media |
| 5 | Tyre Movements — 10 types + voucher workflow |
| 6 | Trailer Transfers |
| 7 | Maintenance + Disposals |
| 8 | Pending Approvals, Reports, Audit Logs |
| 9 | API, artisan commands, test port, polish |

## Out of Scope (Legacy Schema Only)

- `approval_requests` / `approval_steps` tables (unused — status columns used instead)
- `tyre_inspections` UI (schema only in legacy)
