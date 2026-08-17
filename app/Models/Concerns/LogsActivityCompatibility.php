<?php

namespace App\Models\Concerns;

if (trait_exists(\Spatie\Activitylog\Traits\LogsActivity::class)) {
    trait LogsActivityCompatibility
    {
        use \Spatie\Activitylog\Traits\LogsActivity;

        public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
        {
            return \Spatie\Activitylog\LogOptions::defaults()
                ->logFillable()
                ->logOnlyDirty();
        }
    }
} elseif (trait_exists(\Spatie\Activitylog\Models\Concerns\LogsActivity::class)) {
    trait LogsActivityCompatibility
    {
        use \Spatie\Activitylog\Models\Concerns\LogsActivity;

        public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
        {
            return \Spatie\Activitylog\Support\LogOptions::defaults()
                ->logFillable()
                ->logOnlyDirty();
        }
    }
} else {
    throw new \LogicException('A supported version of spatie/laravel-activitylog is required.');
}
