# Real Fleet Audit Dataset

`real-fleet-audits.json` is the canonical operational seed manifest generated from
`source/Tyre Audit (8).xlsx`.

Validated source totals:

- 18 power vehicle and attached trailer combinations
- 36 vehicle assets
- 419 unique tyre serial numbers
- 13 explicitly empty A-X positions
- No duplicate vehicle plates or tyre serial numbers

Regenerate the manifest after replacing the source workbook:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\extract-tyre-audit-workbook.ps1 `
  -InputPath '.\database\data\source\Tyre Audit (8).xlsx' `
  -OutputPath '.\database\data\real-fleet-audits.json'
```

The extractor fails before writing the manifest when it finds duplicate plates,
duplicate serials, invalid percentages, missing dates/KM, or an unaccounted A-X
position.

To replace operational data while preserving users, roles, permissions, and
system settings:

```bash
php artisan tms:clean-operational-data --seed-real-audits --force
```
