<?php

namespace App\Services;

use App\Models\Vehicle;

class VehicleAssetImportValidator
{
    public function __construct(
        protected VehicleAssetIdentityService $identity,
    ) {}

    /**
     * @param  list<array{row: int, asset_type?: string|null, vehicle_code?: string|null, plate_number?: string|null, chassis_number?: string|null, engine_number?: string|null}>  $rows
     * @return list<array{row: int, field: string, message: string}>
     */
    public function validateRows(array $rows): array
    {
        $errors = [];

        foreach ($this->identity->uniqueIdentityFields() as $field => $label) {
            $seen = [];

            foreach ($rows as $row) {
                $value = $this->identity->normalize($row[$field] ?? null);

                if ($value === null) {
                    continue;
                }

                if (isset($seen[$value])) {
                    $errors[] = [
                        'row' => (int) $row['row'],
                        'field' => $field,
                        'message' => "{$label} duplicates import row {$seen[$value]}.",
                    ];
                } else {
                    $seen[$value] = (int) $row['row'];
                }

                $existing = Vehicle::query()
                    ->withTrashed()
                    ->where($field, $value)
                    ->first();

                if ($existing) {
                    $errors[] = [
                        'row' => (int) $row['row'],
                        'field' => $field,
                        'message' => "{$label} already exists in {$this->identity->assetTypeLabel($existing)}.",
                    ];
                }
            }
        }

        return $errors;
    }
}
