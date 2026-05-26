# Menkem TMS — End-to-End Manual Test Guide

**System:** Menkem Tyre Management System (TMS)  
**Version:** Laravel 13 + Filament 4  
**Purpose:** Step-by-step manual QA from fresh database to sign-off  
**Last updated:** 2026-05-24

---

## How to use this document

1. Work through sections **in order** the first time (setup → core flows → edge cases).
2. Mark each step **PASS** / **FAIL** / **SKIP** in the **Result** column.
3. On **FAIL**, note the screen, error message, and steps to reproduce in **Notes**.
4. Run automated tests in [Section 0](#0-automated-baseline) before and after manual testing.

**Legend**

| Symbol | Meaning |
|--------|---------|
| ✅ | Expected success |
| ⛔ | Expected block / validation error |
| 🔐 | Requires specific permission or role |

---

## 0. Automated baseline

Run from project root:

```bash
cd tms
composer test          # or: php artisan test
composer analyse       # PHPStan level 5
```

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 0.1 | `composer test` | **14 tests**, all pass | | |
| 0.2 | `composer analyse` | No errors (baseline OK) | | |

---

## 1. Environment setup

### 1.1 Fresh install

```bash
cd tms
cp .env.example .env
php artisan key:generate
# SQLite (default) — no extra DB setup
php artisan migrate:fresh --seed
php artisan storage:link
npm install && npm run build
php artisan serve
# Optional: php artisan serve --port=8001
```

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 1.1 | Open `http://127.0.0.1:8000/admin` | Login page loads | | |
| 1.2 | Login `admin@menkem.com` / `password` | Dashboard loads, sidebar visible | | |
| 1.3 | Toggle **dark mode** (user menu) | Page background, cards, and **Dashboard** title readable (no light band behind dark widgets) | | |
| 1.4 | Hard refresh after UI build | New theme CSS loads (not stale cache) | | |

### 1.2 Seeded reference data (use in tests)

| Entity | Code / value |
|--------|----------------|
| Default store | **Main Tyre Store** (`MAIN-STORE`) |
| Power units | **TRK-001** (trailer attached), **TRK-008** |
| Trailer | **TRL-045** (attached to TRK-001) |
| Tyres | **TYR-0001** … **TYR-0020** |
| On TRK-001 | 6 tyres mounted (power positions) |
| On TRL-045 | 8 tyres mounted (trailer positions) |
| In store | Remaining tyres **available** at Main Tyre Store |

---

## 2. Test users (roles) — optional but recommended

Only **Super Admin** is seeded. For permission testing, create users under **Administration → Users**:

| Email (suggested) | Role | Password | Use for |
|-------------------|------|----------|---------|
| `admin@menkem.com` | Super Admin | `password` | Full E2E (default) |
| `store@menkem.com` | Store Keeper | `password` | Create tyres/movements, no approve |
| `manager@menkem.com` | Store Manager | `password` | Check movements/disposals |
| `technic@menkem.com` | Technic Clerk | `password` | Maps, maintenance create |
| `head@menkem.com` | Technic and Maintenance Head | `password` | Approve movements/maintenance |
| `auditor@menkem.com` | Auditor | `password` | Audit logs, read-only ops |
| `viewer@menkem.com` | Management Viewer | `password` | Dashboard/reports view only |

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 2.1 | Create `store@menkem.com` + role Store Keeper | Can login; **no** Settings / Approve buttons | | |
| 2.2 | Create `viewer@menkem.com` + Management Viewer | Dashboard + Reports only; cannot create movement | | |

---

## 3. Dashboard

**Path:** `/admin` (home)

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 3.1 | Review stat cards | Total tyres ≈ 20; Active on fleet > 0; In store > 0; Pending counts shown | | |
| 3.2 | Click **Pending Approvals** stat | Opens Pending Approvals page | | |
| 3.3 | Charts render | Tyres by Status (doughnut); Completed Movements trend; location/utilization charts | | |
| 3.4 | Resize browser (mobile width) | Widgets stack; layout remains usable | | |

---

## 4. Fleet — Stores

**Navigation:** Fleet → Stores

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 4.1 | List stores | **Main Tyre Store** present, default flagged | | |
| 4.2 | Create store `TEST-STORE` | Saves; appears in list | | |
| 4.3 | Edit store address | Saves without error | | |

---

## 5. Fleet — Vehicles & tyre maps

**Navigation:** Fleet → Vehicles

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 5.1 | Open **TRK-001** (view) | Vehicle details + **combined** power + trailer tyre map | | |
| 5.2 | Click a **filled** position on map | Tyre details / link to tyre record | | |
| 5.3 | Click an **empty** position | Empty state or no tyre (no crash) | | |
| 5.4 | Open **TRL-045** | Single trailer tyre map (12 positions layout) | | |
| 5.5 | Header → **Tyre Status PDF** | PDF opens; company header; positions/tyres listed | | |
| 5.6 | Create vehicle `TRK-TEST` (power type) | Saves; appears in list | | |

---

## 6. Fleet — Trailer transfer (not tyre movement)

**Navigation:** Fleet → Trailer Transfers

**Business rule:** Moving trailer between power units does **not** move tyres off the trailer.

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 6.1 | Create transfer: **TRL-045** from **TRK-001** → **TRK-008** | Draft created with voucher number | | |
| 6.2 | Save; open edit screen | Workflow buttons: Submit, Check, Approve, Complete, Reject, Download PDF | | |
| 6.3 | **Submit** → **Check** → **Approve** → **Complete** | Status progresses; success notifications | | |
| 6.4 | View **TRK-001** / **TRK-008** | TRL-045 attached to TRK-008; tyres still on TRL-045 | | |
| 6.5 | **Download PDF** | Trailer Transfer Voucher PDF; voucher no, date, from/to power | | |
| 6.6 | **Pending Approvals** | Transfer no longer listed when completed | | |

---

## 7. Tyre registration & QR

**Navigation:** Tyre Operations → Tyres

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 7.1 | **Create** tyre `TYR-TEST01` | Serial unique; defaults to store; status **Pending Approval** | | |
| 7.2 | View tyre | **Approve Registration** button visible | | |
| 7.3 | **Approve Registration** | Status → **Available**; QR generated | | |
| 7.4 | **QR Profile** (new tab) | `/tyres/scan/TYR-TEST01` shows tyre info + QR image | | |
| 7.5 | **Registration PDF** | PDF opens with tyre code, brand, store | | |
| 7.6 | **History PDF** | PDF lists movement/history entries (may be empty for new tyre) | | |
| 7.7 | **Regenerate QR** | Success toast; scan page still works | | |

---

## 8. Tyre movement — Store to vehicle (full voucher workflow)

**Navigation:** Tyre Operations → Tyre Movements

Use an **available** store tyre (e.g. pick from list where status = Available and location = store).

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 8.1 | Create movement | Type: **Store to Vehicle**; tyre from store; **To:** TRK-008; position e.g. `P7` | | |
| 8.2 | Save as **Draft** | `movement_no` auto-generated | | |
| 8.3 | **Download PDF** (draft OK) | Movement voucher PDF; status shown | | |
| 8.4 | **Submit** | Status → Submitted | | |
| 8.5 | **Check** | Status → Checked | | |
| 8.6 | **Approve** | Status → Approved | | |
| 8.7 | **Complete** | Status → Completed; modal warns inventory will change | | |
| 8.8 | View tyre record | Location = power vehicle TRK-008; position `P7`; status **Active** | | |
| 8.9 | View TRK-008 map | Tyre appears at position `P7` | | |
| 8.10 | **Pending Approvals** | Movement removed from pending list | | |

**Compare to legacy voucher (reference):** Your sample **Tire Transfer Voucher (TTV)** includes company block, voucher no, date, store route, line items. TMS PDF is a simplified **Menkem TMS** layout (company header, voucher meta, from/to table, signatures). Confirm data is correct even if layout differs from legacy MATRIX printouts.

---

## 9. Tyre movement — Other types (spot check)

Repeat **Submit → Check → Approve → Complete** for at least one case each, or document SKIP with reason.

| # | Movement type | Tyre source | To | Expected after complete | Result | Notes |
|---|---------------|-------------|-----|-------------------------|--------|-------|
| 9.1 | **Vehicle to Store** | Tyre on TRK-008 | Main Tyre Store | Tyre in store; **Available** | | |
| 9.2 | **Position Change (Same Asset)** | Tyre on TRK-001 | Same vehicle, new position | Position code updated only | | |
| 9.3 | **Vehicle to Vehicle** | Tyre on TRK-001 | TRK-008 + position | Tyre on new vehicle | | |
| 9.4 | **Power to Trailer** | Tyre on power | TRL-045 + position | Tyre on trailer asset | | |

---

## 10. Tyre movement — Negative / business rules

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 10.1 | Create second **draft** movement for same tyre while first is pending | ⛔ Blocked: pending movement exists | | |
| 10.2 | Try to move a **disposed** tyre (after Section 12) | ⛔ Business rule error | | |
| 10.3 | Complete movement to position **already occupied** on same vehicle | ⛔ Cannot assign two tyres to one position | | |
| 10.4 | **Reject** a submitted movement with reason | Status → Rejected; tyre location unchanged | | |
| 10.5 | Delete movement | Only allowed in **Draft** | | |

---

## 11. Tyre maintenance

**Navigation:** Tyre Operations → Tyre Maintenances

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 11.1 | Create maintenance for active fleet tyre | Draft; problem type, dates, cost fields | | |
| 11.2 | **Submit** → **Approve** → **Start Work** → **Complete** | Status progresses through workflow | | |
| 11.3 | **Download PDF** | Maintenance voucher PDF | | |
| 11.4 | Tyre still trackable after complete | History/audit reflects maintenance | | |

---

## 12. Tyre disposal

**Navigation:** Tyre Operations → Tyre Disposals

Use a store **available** tyre (not fleet-mounted).

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 12.1 | Create disposal voucher | Draft with disposal reason | | |
| 12.2 | **Submit** → **Check** → **Approve** → **Complete** | Full voucher workflow | | |
| 12.3 | View tyre | Status **Disposed** | | |
| 12.4 | Try new movement for disposed tyre | ⛔ Blocked (see 10.2) | | |
| 12.5 | **Download PDF** | Disposal voucher PDF | | |

**Legacy reference:** Sample **Tire Store Return Voucher (TRV)** — verify TMS disposal/return PDF has correct tyre description and voucher number.

---

## 13. Pending approvals hub

**Navigation:** Approvals & Reports → Pending Approvals

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 13.1 | With draft/submitted items open | Lists movements, trailer transfers, disposals awaiting action | | |
| 13.2 | Click **Review** link | Opens correct edit screen | | |
| 13.3 | After all completed | Empty state message (no pending items) | | |

---

## 14. Reports (CSV exports)

**Navigation:** Approvals & Reports → Reports

Set date range: **last 30 days**.

| # | Export button | Expected file | Result | Notes |
|---|---------------|---------------|--------|-------|
| 14.1 | Tyre Stock | `tyre-stock.csv`; headers + tyre rows | | |
| 14.2 | Tyres by Vehicle | CSV with vehicle filter | | |
| 14.3 | Tyre Lifecycle | CSV downloads | | |
| 14.4 | KM & Cost per KM | CSV downloads | | |
| 14.5 | Tyre Movements | Rows within date range | | |
| 14.6 | Maintenance Cost | CSV downloads | | |
| 14.7 | Tyre Disposals | CSV downloads | | |
| 14.8 | Trailer Transfers | Includes transfer from Section 6 | | |
| 14.9 | Audit Trail | Activity rows (if `audit.view`) | | |

Login as **Management Viewer** → Reports: exports that need `report.export` should be denied or hidden.

---

## 15. Audit logs

**Navigation:** Approvals & Reports → Audit Logs  
🔐 Requires `audit.view` (Super Admin, Admin, Auditor)

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 15.1 | Open list | Recent activities from movements/tyres | | |
| 15.2 | Filter by log name / date | Table updates | | |
| 15.3 | View single log | Details show subject, causer, properties | | |

---

## 16. System settings

**Navigation:** Administration → Settings  
🔐 Requires `settings.manage`

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 16.1 | Change company name → Save | Success notification | | |
| 16.2 | Download any PDF | Company name in PDF header matches | | |
| 16.3 | Set max trailers per power = `1` | Saves | | |
| 16.4 | Try attaching 2 trailers to one power (if UI allows) | ⛔ Enforced at service level when rule violated | | |

---

## 17. Administration — Users & roles

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 17.1 | Users → list | Admin user with Super Admin role | | |
| 17.2 | Roles → list | 8 roles with permissions | | |
| 17.3 | Edit Store Keeper role | Permission list matches README matrix | | |

---

## 18. API — Sanctum tyre lookup

```bash
cd tms
php artisan tinker
>>> $u = \App\Models\User::where('email', 'admin@menkem.com')->first();
>>> $token = $u->createToken('e2e-test')->plainTextToken;
>>> echo $token;
```

```bash
curl -s -H "Authorization: Bearer YOUR_TOKEN" \
  http://127.0.0.1:8000/api/tyres/TYR-0001 | jq .
```

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 18.1 | GET `/api/tyres/TYR-0001` with token | JSON: tyre_code, status, location fields | | |
| 18.2 | GET without token | 401 Unauthorized | | |
| 18.3 | GET unknown code | 404 | | |

---

## 19. PDF voucher checklist (all types)

Open each PDF while logged in. Confirm readable print layout (A4).

| Voucher | How to open | Must contain | Result | Notes |
|---------|-------------|--------------|--------|-------|
| Tyre Movement | Movement edit → Download PDF | Voucher no, date, tyre code, from/to, status, signatures block | | |
| Trailer Transfer | Trailer transfer edit → Download PDF | Transfer no, power from/to, trailer | | |
| Maintenance | Maintenance edit → Download PDF | Maintenance ref, tyre, dates/cost | | |
| Disposal | Disposal edit → Download PDF | Disposal ref, tyre, reason | | |
| Tyre Registration | Tyre view → Registration PDF | Tyre code, brand, size, store | | |
| Tyre History | Tyre view → History PDF | Chronological events | | |
| Vehicle Tyre Status | Vehicle view → Tyre Status PDF | All positions for that asset | | |

**Note:** Legacy MATRIX PDFs (TTV, TRV with VAT lines, supplier blocks) are **reference designs**. TMS vouchers use the shared template in `resources/views/pdf/layout.blade.php`. Log gaps as enhancement requests, not failures, unless required data is missing.

---

## 20. UI / UX regression (quick)

| # | Step | Expected | Result | Notes |
|---|------|----------|--------|-------|
| 20.1 | **Reports** page | Full-width cards; export buttons work | | |
| 20.2 | **Pending Approvals** page | Cards list pending items | | |
| 20.3 | Sidebar collapse (desktop) | More horizontal space; charts resize | | |
| 20.4 | Logout → Login | Session cleared; redirect to login | | |

---

## 21. Sign-off summary

| Area | Tester | Date | Pass? | Blockers |
|------|--------|------|-------|----------|
| Setup & dashboard | | | | |
| Fleet (stores, vehicles, maps, trailer transfer) | | | | |
| Tyre registration & QR | | | | |
| Tyre movements & business rules | | | | |
| Maintenance & disposal | | | | |
| Approvals, reports, audit | | | | |
| PDF vouchers | | | | |
| API | | | | |
| UI / dark mode | | | | |
| Automated tests (14/14) | | | | |

**Tester signature:** _________________________  
**Approved by:** _________________________  

---

## Appendix A — Useful commands

```bash
# Reset DB and re-test from scratch
php artisan migrate:fresh --seed

# Clear caches after config changes
php artisan optimize:clear

# Rebuild admin theme
npm run build

# Run movement rule tests only
php artisan test --filter=TyreMovementBusinessRulesTest
```

## Appendix B — URL quick reference

| URL | Purpose |
|-----|---------|
| `/admin` | Filament dashboard |
| `/admin/tyres` | Tyre inventory |
| `/admin/tyre-movements` | Movements |
| `/admin/pending-approvals` | Approval hub |
| `/admin/reports` | CSV exports |
| `/tyres/scan/{code}` | Public QR profile |
| `/vouchers/movement/{id}` | Movement PDF (auth) |
| `/api/tyres/{code}` | Sanctum API |

## Appendix C — Known limitations (not test failures)

- **Excel export:** Not installed (PHP 8.5 / phpspreadsheet); use CSV on Reports page.
- **MATRIX sync:** Removed from this codebase; standalone TMS only.
- **PDF layout:** Simpler than legacy Menkem ERP vouchers; data accuracy is the acceptance criteria.
