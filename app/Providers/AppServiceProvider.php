<?php

namespace App\Providers;

use App\Models\Tyre;
use App\Models\TyreBaseline;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use App\Policies\TyreBaselinePolicy;
use App\Policies\TyreMovementPolicy;
use App\Policies\TyrePolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Gate::before(function ($user) {
            return $user->hasAnyRole(['Super Admin', 'Admin']) ? true : null;
        });

        Gate::policy(Tyre::class, TyrePolicy::class);
        Gate::policy(TyreBaseline::class, TyreBaselinePolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(TyreMovement::class, TyreMovementPolicy::class);
    }
}
