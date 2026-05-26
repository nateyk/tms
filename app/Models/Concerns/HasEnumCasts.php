<?php

namespace App\Models\Concerns;

trait HasEnumCasts
{
    protected function enumCasts(array $map): array
    {
        return array_merge(parent::casts(), $map);
    }
}
