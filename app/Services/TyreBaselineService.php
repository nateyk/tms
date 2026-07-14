<?php

namespace App\Services;

use App\Enums\TyreLocationType;
use App\Exceptions\TyreBusinessException;
use App\Models\Tyre;
use App\Models\TyreBaseline;

class TyreBaselineService
{
    public function createBaseline(array $data, int $userId): TyreBaseline
    {
        $tyre = Tyre::query()->findOrFail($data['tyre_id']);
        $this->validateBaselineCreation($tyre);

        return TyreBaseline::query()->create(array_merge($this->baselineDataFromTyre($tyre, $data), [
            'created_by' => $userId,
        ]));
    }

    public function validateBaselineCreation(Tyre $tyre): void
    {
        $existing = TyreBaseline::query()->where('tyre_id', $tyre->id)->exists();

        if ($existing) {
            throw new TyreBusinessException('Tyre already has a baseline.');
        }
    }

    public function getBaselineForTyre(Tyre $tyre): ?TyreBaseline
    {
        return TyreBaseline::query()->forTyre($tyre->id)->first();
    }

    public function updateBaseline(TyreBaseline $baseline, array $data): TyreBaseline
    {
        $baseline->loadMissing('tyre');
        $baseline->update($this->baselineDataFromTyre($baseline->tyre, $data));

        return $baseline->fresh();
    }

    public function deleteBaseline(TyreBaseline $baseline): bool
    {
        return $baseline->delete();
    }

    private function baselineDataFromTyre(Tyre $tyre, array $data): array
    {
        $locationType = $data['baseline_location_type']
            ?? $tyre->current_location_type?->value
            ?? TyreLocationType::Store->value;

        return array_merge($data, [
            'baseline_location_type' => $locationType,
            'baseline_location_id' => $data['baseline_location_id'] ?? $tyre->current_location_id,
            'baseline_position_code' => $data['baseline_position_code'] ?? $tyre->current_position_code,
        ]);
    }
}
