<?php

namespace App\Livewire;

use App\Models\Vehicle;
use Livewire\Component;

class CombinedVehicleTrailerMap extends Component
{
    public int $powerVehicleId;

    public function mount(int $powerVehicleId): void
    {
        $this->powerVehicleId = $powerVehicleId;
    }

    public function render()
    {
        $power = Vehicle::query()
            ->with(['vehicleType', 'activeCombinationAsPower.trailer.vehicleType'])
            ->findOrFail($this->powerVehicleId);

        $trailer = $power->activeCombinationAsPower?->trailer;

        return view('livewire.combined-vehicle-trailer-map', [
            'power' => $power,
            'trailer' => $trailer,
        ]);
    }
}
