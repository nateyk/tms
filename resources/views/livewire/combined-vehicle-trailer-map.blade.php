<div class="space-y-8">
    <section class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="mb-4 text-lg font-semibold text-gray-950 dark:text-white">
            Power unit — {{ $power->vehicle_code }}
        </h3>
        @livewire(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => $power->id], key('power-map-'.$power->id))
    </section>

    <section class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Attached trailer</h3>
            @if ($trailer)
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    {{ $trailer->vehicle_code }} · {{ $trailer->plate_number }}
                </span>
            @else
                <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
                    No trailer attached
                </span>
            @endif
        </div>

        @if ($trailer)
            @livewire(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => $trailer->id], key('trailer-map-'.$trailer->id))
        @else
            <x-tms.empty-state
                title="No trailer on this power unit"
                description="Use Trailer Transfers to attach a trailer."
            />
        @endif
    </section>

    <div class="flex flex-wrap gap-2">
        <a
            href="{{ url('/admin/trailer-transfers/create') }}"
            class="inline-flex items-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700"
        >
            Attach / change trailer
        </a>
        <a
            href="{{ url('/admin/tyre-movements/create') }}"
            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >
            Move tyre
        </a>
        <a
            href="{{ route('vouchers.vehicle.tyre-status.pdf', $power) }}"
            target="_blank"
            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >
            PDF tyre status
        </a>
    </div>
</div>
