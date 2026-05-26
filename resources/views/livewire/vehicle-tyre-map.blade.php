@php
    $filled = $mapData->whereNotNull('tyre_code')->count();
    $total = $mapData->count();
    $empty = $total - $filled;
@endphp

<div class="tms-tyre-map-shell space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400">
                Interactive layout
            </p>
            <h3 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $vehicle->vehicle_code }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $vehicle->vehicleType?->name }}
                @if ($vehicle->plate_number)
                    <span class="text-gray-300 dark:text-gray-600">·</span> {{ $vehicle->plate_number }}
                @endif
                @if ($vehicle->odometer)
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    {{ number_format((int) $vehicle->odometer) }} km
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="tms-tyre-map-stat">
                <span class="text-[10px] font-medium uppercase tracking-wide text-gray-500">Mounted</span>
                <strong>{{ $filled }}/{{ $total }}</strong>
            </div>
            <div class="tms-tyre-map-stat">
                <span class="text-[10px] font-medium uppercase tracking-wide text-gray-500">Open slots</span>
                <strong>{{ $empty }}</strong>
            </div>
            <div class="hidden flex-wrap gap-1.5 sm:flex">
                @foreach ($legend as $color => $label)
                    <span class="tms-tyre-map-legend-pill">
                        <span @class([
                            'h-2.5 w-2.5 rounded-full',
                            'bg-green-500' => $color === 'green',
                            'bg-blue-500' => $color === 'blue',
                            'bg-orange-500' => $color === 'orange',
                            'bg-red-500' => $color === 'red',
                            'bg-yellow-500' => $color === 'yellow',
                            'bg-gray-700' => $color === 'black',
                            'bg-gray-300 ring-1 ring-gray-400' => $color === 'gray',
                        ])></span>
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    @if ($mapData->isEmpty())
        <x-tms.empty-state
            title="No tyre positions configured"
            description="Run php artisan tms:refresh-tyre-layouts to generate axle layouts."
        />
    @else
        <div class="tms-tyre-map-card">
            <div class="tms-tyre-map-canvas-wrap">
                <div class="tms-tyre-map-canvas-host">
                    <div
                        wire:ignore
                        data-tyre-map-konva
                        data-map-id="tyre-map-{{ $vehicle->id }}"
                        data-config='@json($konvaConfig)'
                        class="h-full w-full"
                        role="application"
                        aria-label="Tyre map for {{ $vehicle->vehicle_code }}"
                    ></div>
                </div>
            </div>
            <p class="relative z-[1] px-4 pb-3 text-center text-[11px] text-gray-500 dark:text-gray-400">
                Click a tyre to inspect · Steer axle singles · Drive axles dual pairs
            </p>
        </div>
    @endif

    @once
        @vite(['resources/js/tyre-map-konva.js', 'resources/css/tyre-map.css'])
    @endonce

    @if ($selectedPosition)
        @php
            $slot = $mapData->firstWhere('code', $selectedPosition);
        @endphp
        <div class="tms-tyre-map-detail p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">
                        Selected position
                    </p>
                    <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">
                        {{ $slot['label'] ?? $selectedPosition }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $selectedPosition }}</p>
                </div>
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                    'bg-green-50 text-green-800 ring-green-600/20 dark:bg-green-950/40 dark:text-green-300' => ($slot['color'] ?? '') === 'green',
                    'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300' => ($slot['color'] ?? 'gray') === 'gray',
                    'bg-blue-50 text-blue-800 ring-blue-600/20' => ($slot['color'] ?? '') === 'blue',
                    'bg-orange-50 text-orange-800 ring-orange-600/20' => ($slot['color'] ?? '') === 'orange',
                    'bg-red-50 text-red-800 ring-red-600/20' => ($slot['color'] ?? '') === 'red',
                    'bg-yellow-50 text-yellow-800 ring-yellow-600/20' => ($slot['color'] ?? '') === 'yellow',
                    'bg-gray-900 text-gray-100 ring-gray-700' => ($slot['color'] ?? '') === 'black',
                ])>
                    {{ $slot['status'] ?? 'Empty' }}
                </span>
            </div>

            @if ($selectedTyreId)
                <dl class="mt-5 grid gap-4 border-t border-amber-200/50 pt-5 dark:border-amber-900/40 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Tyre code</dt>
                        <dd class="mt-0.5 text-base font-semibold">
                            <a href="{{ url('/admin/tyres/'.$selectedTyreId) }}" class="text-amber-600 hover:underline dark:text-amber-400">
                                {{ $slot['tyre_code'] }}
                            </a>
                        </dd>
                    </div>
                    @if ($slot['serial_number'] ?? null)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Serial</dt>
                            <dd class="mt-0.5 font-medium text-gray-950 dark:text-white">{{ $slot['serial_number'] }}</dd>
                        </div>
                    @endif
                    @if ($slot['brand'] ?? null)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Brand</dt>
                            <dd class="mt-0.5 font-medium text-gray-950 dark:text-white">{{ $slot['brand'] }}</dd>
                        </div>
                    @endif
                    @if ($slot['tread_depth'] ?? null)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Tread depth</dt>
                            <dd class="mt-0.5 font-medium text-gray-950 dark:text-white">{{ $slot['tread_depth'] }} mm</dd>
                        </div>
                    @endif
                </dl>
            @else
                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-dashed border-gray-200 pt-5 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Open slot — start a <strong>Store → Vehicle</strong> movement to mount a tyre here.
                    </p>
                    @if ($slot['install_url'] ?? null)
                        <a
                            href="{{ $slot['install_url'] }}"
                            class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-500"
                        >
                            Fill position {{ $selectedPosition }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if ($emptySlots->isNotEmpty())
        <div id="tyre-map-gaps-{{ $vehicle->id }}" class="rounded-xl border border-dashed border-amber-300/60 bg-amber-50/50 p-5 dark:border-amber-800/50 dark:bg-amber-950/20">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Open positions ({{ $emptySlots->count() }})
                    </h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Each gap links to a pre-filled movement: store tyre → this vehicle at the chosen position.
                        Complete the voucher to mount the tyre.
                    </p>
                </div>
                <a
                    href="{{ $movementsIndexUrl }}"
                    class="text-xs font-medium text-amber-700 hover:underline dark:text-amber-400"
                >
                    All movements →
                </a>
            </div>

            <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($emptySlots as $gap)
                    <li
                        class="flex items-center justify-between gap-2 rounded-lg border border-white/80 bg-white px-3 py-2.5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $gap['code'] }}
                            </p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $gap['label'] }}
                                @if ($gap['dual'] && $gap['dual'] !== 'single')
                                    · {{ ucfirst($gap['dual']) }}
                                @endif
                            </p>
                        </div>
                        <a
                            href="{{ $gap['install_url'] }}"
                            class="shrink-0 rounded-md bg-amber-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-amber-500"
                        >
                            Fill gap
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($total > 0)
        <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/30 dark:text-green-200">
            All {{ $total }} positions are filled on this vehicle.
        </p>
    @endif
</div>
