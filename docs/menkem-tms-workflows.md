# Menkem TMS Workflow Guide

This document explains the main modules in Menkem Tyre Management System and the normal work flow for each one.

## Core Rule

The system separates three things:

- Vehicle KM is recorded on the vehicle odometer page.
- Tyre baseline is the tyre starting condition: percentage, expected life, date, and notes.
- Tyre usage is calculated from vehicle KM, tyre assignment, baseline, and audits.

When a tyre is already mounted on a truck or trailer, the baseline form uses the latest vehicle KM automatically. Operators normally do not need to type truck KM again unless that vehicle has no KM recorded.

## 1. Login And Dashboard

Route:

- `/login`
- `/dashboard`

Workflow:

1. User logs in.
2. System checks role and permissions.
3. User lands on dashboard.
4. Sidebar shows only modules allowed by the user's role.

Important notes:

- Admin should have full access after running `RolesAndPermissionsSeeder`.
- If menu items are missing after deployment, clear cache and reseed roles.

## 2. Fleet Module

Routes:

- `/fleet/vehicle-types`
- `/fleet/stores`
- `/fleet/vehicles`
- `/fleet/vehicles/{vehicle}/odometer`
- `/fleet/trailer-transfers`

### Vehicle Types

Purpose:

- Defines vehicle/trailer tyre layout and tyre positions.
- The tyre map depends on the vehicle type layout.

Workflow:

1. Create vehicle type.
2. Set asset type: power vehicle or trailer.
3. Configure tyre positions/layout.
4. Vehicles using that type inherit the tyre position map.

### Stores

Purpose:

- Stores hold tyres that are not mounted on vehicles.

Workflow:

1. Create store.
2. Tyres can be registered into store or moved into store.
3. Stored tyres do not accumulate running KM.

### Vehicles

Purpose:

- Creates trucks and trailers used for tyre assignments.

Workflow:

1. Create vehicle.
2. Select vehicle type.
3. Enter plate/code/chassis/engine details.
4. Save vehicle.
5. Open vehicle detail to view tyre map and related information.

### Vehicle Odometer

Purpose:

- Records current KM for a truck or trailer.
- This KM is used by tyre usage calculation, baseline fallback, movement completion, and audit context.

Workflow:

1. Open `/fleet/vehicles/{vehicle}/odometer`.
2. If first baseline KM is not set, record the first baseline odometer.
3. Enter current odometer KM when updated.
4. Save.
5. History table records baseline/manual/movement readings.

Rules:

- New KM cannot be lower than latest recorded KM.
- Tyre baseline can use this vehicle KM automatically.

## 3. Tyres Module

Routes:

- `/tyres`
- `/tyres/create`
- `/tyres/{tyre}`
- `/tyres/{tyre}/edit`
- `/tyres/{tyre}/approve`
- `/tyres/{tyre}/regenerate-qr`

Purpose:

- Register tyres, manage identity, approve registration, view full tyre history.

Workflow:

1. Register tyre from `/tyres/create`.
2. Enter tyre code, serial, brand, size, supplier, source, purchase data, and tread values.
3. Save tyre.
4. Tyre is pending approval if approval workflow is active.
5. Approver approves tyre.
6. QR code/voucher can be generated.
7. Tyre can then be moved or mounted.

Tyre detail page:

- Shows identity.
- Shows current assignment/location.
- Shows usage summary.
- Shows baseline.
- Shows movement and audit history.
- Provides actions like edit, movement, baseline, audit, QR/voucher.

## 4. Tyre Baselines

Routes:

- `/tyres/baselines`
- `/tyres/baselines/create`
- `/tyres/baselines/{baseline}`
- `/tyres/baselines/{baseline}/edit`

Purpose:

- Sets the starting condition for tyre usage calculation.

Main fields:

- Tyre
- Baseline percentage
- Expected life KM
- Baseline date
- Notes
- Truck KM only appears when needed

Normal workflow:

1. Select tyre.
2. Enter baseline percentage.
3. Enter expected life KM.
4. Confirm baseline date.
5. Save.

Mounted tyre workflow:

1. Tyre is mounted on a running position.
2. System checks vehicle latest KM.
3. If vehicle KM exists, baseline odometer is filled automatically in backend.
4. User only sets tyre condition values.
5. If vehicle KM does not exist, form asks for Truck KM once.

Spare tyre workflow:

1. Tyre is mounted on spare position like W/X.
2. Baseline can be saved without running KM.
3. Spare tyres do not accumulate active running KM.

Important rules:

- A tyre can only have one active baseline.
- Running mounted tyres need either vehicle KM or entered Truck KM.
- Store tyres can be baselined without odometer.

## 5. Reading Monitoring

Routes:

- `/tyres/reading-monitoring`
- `/tyres/reading-monitoring/{vehicle}`

Purpose:

- Main operational screen for tyre health overview by vehicle.

Vehicle list workflow:

1. Open Reading Monitoring.
2. Select vehicle.
3. System opens vehicle tyre health page.

Vehicle reading page workflow:

1. Review summary cards: total tyres, good, warning, critical, audited, average effective.
2. Use tyre map as visual health overview.
3. Click tyre position.
4. Selected position panel shows quick decision information.
5. If tyre has no baseline, set baseline inline.
6. If tyre needs audit, record condition audit.
7. Use report table for full reading/report view.

Map meaning:

- Tyre map = visual health overview.
- Selected tyre panel = quick decision panel.
- Tyre detail page = full history and audit record.
- Reading Monitoring table = report view.

## 6. Tyre Condition Audit

Routes:

- `/tyres/{tyre}/condition-audits/create`

Purpose:

- Records manual inspected tyre condition.

Workflow:

1. Open from tyre detail or Reading Monitoring selected panel.
2. Review tyre, vehicle KM, calculated remaining, and last audit.
3. Enter audited remaining percentage.
4. Enter audit date.
5. Enter audit odometer if needed.
6. Enter tread depth, condition, reason, and notes.
7. Save audit.

Rules:

- Audit does not change baseline.
- Audit creates a manual checkpoint.
- Effective remaining can use latest audit where available.
- Large variance shows warning/information message.

## 7. Tyre Movements

Routes:

- `/tyres/movements`
- `/tyres/movements/create`
- `/tyres/movements/{movement}`
- `/tyres/movements/{movement}/edit`
- `/tyres/movements/{movement}/submit`
- `/tyres/movements/{movement}/check`
- `/tyres/movements/{movement}/approve`
- `/tyres/movements/{movement}/reject`
- `/tyres/movements/{movement}/complete`
- `/tyres/movements/{movement}/cancel`

Purpose:

- Controls tyre movement between store, vehicle positions, maintenance, disposal, and other destinations.

Draft workflow:

1. Create movement.
2. Select tyre.
3. Confirm source location.
4. Select destination type.
5. Select destination vehicle/store.
6. Select destination position if vehicle/trailer.
7. Enter odometer out/in when relevant.
8. Add reason and notes.
9. Save draft.

Approval workflow:

1. Submit movement.
2. Checker reviews.
3. Approver approves or rejects.
4. Approved movement can be completed.

Completion workflow:

1. Open movement detail.
2. Complete movement.
3. Enter odometer values in completion dialog when needed.
4. System closes old assignment.
5. System creates new active assignment.
6. System stores installed odometer.
7. System updates vehicle odometer history when vehicle KM is captured.

Rules:

- Running vehicle mount should capture destination odometer.
- Assignment installed KM should match completion `to_odometer`.
- Old assignment removed KM should match `from_odometer`.

## 8. Tyre Disposals

Route:

- `/tyres/disposals`

Current state:

- Placeholder module.

Expected workflow:

1. Select tyre for disposal.
2. Enter disposal reason and condition.
3. Submit for approval if required.
4. Approved disposal marks tyre as disposed.
5. Disposed tyre no longer accumulates active KM.

## 9. Trailer Transfers

Route:

- `/fleet/trailer-transfers`

Current state:

- Placeholder module.

Expected workflow:

1. Select power vehicle.
2. Select trailer.
3. Record attach/detach information.
4. Capture odometer values.
5. Submit/approve transfer voucher.
6. Generate trailer transfer PDF.

## 10. Approvals

Route:

- `/approvals/pending`

Purpose:

- Central queue for approval actions.

Workflow:

1. User opens pending approvals.
2. System lists approval items the user can act on.
3. User checks/approves/rejects depending on role.
4. Approved item continues to next status.

Approval examples:

- Tyre registration approval.
- Tyre movement check/approval.
- Future trailer transfer approval.
- Future disposal approval.

## 11. Reports

Route:

- `/reports`

Purpose:

- Report hub for fleet and tyre operation reports.

Expected reports:

- Vehicle tyre status report.
- Tyre history report.
- Movement voucher report.
- Registration voucher report.
- Audit/reading monitoring report.

PDF voucher routes:

- `/vouchers/movement/{movement}`
- `/vouchers/trailer-transfer/{transfer}`
- `/vouchers/tyre/{tyre}/registration`
- `/vouchers/tyre/{tyre}/history`
- `/vouchers/vehicle/{vehicle}/tyre-status`

## 12. Audit Logs

Route:

- `/audit-logs`

Purpose:

- Tracks important actions for accountability.

Workflow:

1. Admin opens audit logs.
2. Filter/review user actions.
3. Use logs to trace who created, updated, approved, or changed records.

## 13. Admin Module

Routes:

- `/admin/users`
- `/admin/roles`
- `/admin/settings`

### Users

Workflow:

1. Create user.
2. Assign role.
3. User receives access based on permissions.
4. Edit user when role/access changes.

### Roles

Purpose:

- Defines what each account can view or change.

Important roles:

- Super/admin role should have full access.
- Operational roles can be limited to fleet/tyre tasks.
- Approval roles can check/approve movement workflows.

### Settings

Purpose:

- System-level settings.

Workflow:

1. Admin opens settings.
2. Updates allowed configuration.
3. Saves settings.

## 14. Demo Data

Seeders:

- `RolesAndPermissionsSeeder`
- `SystemSettingsSeeder`
- `TmsSampleDataSeeder`
- `ExistingFleetTyreFitmentSeeder`

Purpose:

- Roles seeder sets access permissions.
- Settings seeder sets defaults.
- Sample data creates demo trucks, tyres, baselines, odometer history, movements, and audits.
- Existing fitment seeder loads real/demo fitment data from `database/seeders/data/existing_fleet_tyre_fitments.php`.

Common command:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

## 15. cPanel Deployment Workflow

Use this when pulling latest GitHub code to cPanel:

```bash
cd /home/menkemgi/tms-app
git fetch origin
git reset --hard origin/main
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Notes:

- `git reset --hard origin/main` makes cPanel match GitHub.
- Run migrations after pulling.
- Reseed roles if admin sidebar/permissions look incomplete.
- Hard refresh browser after deployment because frontend build files are hashed.

## 16. Normal Daily Operation

Recommended daily flow:

1. Record vehicle odometer when KM changes.
2. Register new tyres.
3. Approve tyre registration.
4. Mount tyres using tyre movement.
5. Set tyre baseline from Reading Monitoring or Baselines page.
6. Use Reading Monitoring map for quick health overview.
7. Record condition audit when physical inspection is done.
8. Move tyres when rotated, stored, repaired, or mounted elsewhere.
9. Use reports/vouchers for records.
10. Review approvals and audit logs.

## 17. Troubleshooting

### Baseline asks for Truck KM

Cause:

- Tyre is mounted on running position, but vehicle has no latest KM.

Fix:

1. Go to vehicle odometer page.
2. Record vehicle KM.
3. Return to baseline form.
4. Save baseline with tyre percentage and expected life.

### Admin cannot see full sidebar

Cause:

- Permission cache or roles not seeded.

Fix:

```bash
php artisan optimize:clear
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Frontend looks old after deploy

Cause:

- Browser cached old build assets or cPanel has not pulled latest build.

Fix:

1. Pull latest `main`.
2. Clear Laravel caches.
3. Hard refresh browser.

### Movement completion does not update usage

Check:

- Movement is approved.
- Completion was done.
- Destination odometer was entered for running vehicle mount.
- Vehicle odometer history has latest reading.
- New assignment has installed odometer.

