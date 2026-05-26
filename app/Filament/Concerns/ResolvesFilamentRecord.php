<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait ResolvesFilamentRecord
{
    /**
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @return T
     */
    protected function filamentRecord(string $modelClass): Model
    {
        $record = $this->record;

        if (! $record instanceof $modelClass) {
            throw new RuntimeException(
                'Expected record of type '.$modelClass.', got '.($record ? $record::class : 'null')
            );
        }

        return $record;
    }

    protected function userCan(string $permission): bool
    {
        return tms_user()?->can($permission) ?? false;
    }
}
