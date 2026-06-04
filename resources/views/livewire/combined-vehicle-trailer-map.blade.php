<div class="tms-combination-shell">
    <header class="tms-combination-header">
        <div>
            <p>Vehicle combination</p>
            <h2>{{ $power->vehicle_code }}</h2>
            <span>
                {{ $power->vehicleType?->name ?: 'Power unit' }}
                @if ($power->plate_number)
                    | {{ $power->plate_number }}
                @endif
            </span>
        </div>

        <div class="tms-combination-actions">
            <a href="{{ url('/admin/trailer-transfers/create') }}" class="is-primary">
                Attach / change trailer
            </a>
            <a href="{{ url('/admin/tyre-movements/create') }}">
                Move tyre
            </a>
            <a href="{{ route('vouchers.vehicle.tyre-status.pdf', $power) }}" target="_blank">
                PDF tyre status
            </a>
        </div>
    </header>

    <section class="tms-combination-unit">
        <div class="tms-combination-unit-head">
            <div>
                <p>Power unit tyre map</p>
                <h3>{{ $power->vehicle_code }}</h3>
            </div>
            <span>{{ $power->vehicleType?->tyre_count ?? 0 }} tyre positions</span>
        </div>

        @livewire(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => $power->id], key('power-map-'.$power->id))
    </section>

    <div class="tms-combination-coupler" aria-hidden="true">
        <span></span>
        <strong></strong>
        <span></span>
    </div>

    <section class="tms-combination-unit is-trailer">
        <div class="tms-combination-unit-head">
            <div>
                <p>Attached trailer</p>
                <h3>{{ $trailer?->vehicle_code ?? 'Trailer bay' }}</h3>
            </div>

            @if ($trailer)
                <span>{{ $trailer->plate_number ?: 'Plate not set' }}</span>
            @else
                <span class="is-warning">No trailer attached</span>
            @endif
        </div>

        @if ($trailer)
            @livewire(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => $trailer->id], key('trailer-map-'.$trailer->id))
        @else
            <div class="tms-trailer-empty-bay">
                <div class="tms-trailer-ghost" aria-hidden="true">
                    <span></span>
                    <i></i>
                    <i></i>
                    <b></b>
                </div>
                <div>
                    <p>No trailer on this power unit</p>
                    <span>Attach a trailer to view the trailer tyre map, axle groups, and open tyre positions.</span>
                </div>
                <a href="{{ url('/admin/trailer-transfers/create') }}">Attach trailer</a>
            </div>
        @endif
    </section>
</div>
