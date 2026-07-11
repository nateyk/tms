<?php

namespace App\Services;

use App\Exceptions\TyreBusinessException;
use App\Models\Tyre;
use App\Models\TyreBaseline;

class TyreBaselineService
{
    public function createBaseline(array $data, int $userId): TyreBaseline
    {
        $tyre = Tyre::query()->findOrFail($data['tyre_id']);
        $this->validateBaselineCreation($tyre);

        return TyreBaseline::query()->create(array_merge($data, [
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
        $baseline->update($data);

        return $baseline->fresh();
    }

    public function deleteBaseline(TyreBaseline $baseline): bool
    {
        return $baseline->delete();
    }
}
