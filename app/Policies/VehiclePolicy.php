<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vehicle.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.update');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.create');
    }
}
