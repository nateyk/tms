@php
    $filled = $mapData->whereNotNull('tyre_code')->count();
    $total = $mapData->count();
    $empty = $total - $filled;
    $selectedSlot = $selectedPosition ? $mapData->firstWhere('code', $selectedPosition) : null;
    $isTrailer = $vehicle->asset_type?->value === 'trailer';
    $guideGroups = $isTrailer
        ? $mapData
            ->groupBy(fn (array $slot) => (int) ($slot['axle'] ?? 0))
            ->map(fn ($slots, int $axle) => [
                'title' => 'Trailer axle '.$axle,
                'tone' => ['blue', 'green', 'orange', 'teal', 'red'][$axle - 1] ?? 'blue',
                'slots' => $slots->values(),
            ])
            ->values()
        : collect([
            ['title' => 'Front axle', 'tone' => 'blue', 'codes' => ['A', 'B']],
            ['title' => '1st drive axle', 'tone' => 'green', 'codes' => ['C', 'D', 'E', 'F']],
            ['title' => '2nd drive axle', 'tone' => 'orange', 'codes' => ['G', 'H', 'I', 'J']],
            ['title' => 'Spare wheel', 'subtitle' => 'Between 1st and 2nd group', 'tone' => 'purple', 'codes' => ['W']],
            ['title' => 'Tag axle', 'tone' => 'teal', 'codes' => ['K', 'L', 'M', 'N']],
            ['title' => 'Spare wheel', 'subtitle' => 'Between tag and rear group', 'tone' => 'purple', 'codes' => ['X']],
            ['title' => 'Rear axle', 'tone' => 'red', 'codes' => ['O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V']],
        ])->map(function (array $group) use ($mapData) {
            $slots = collect($group['codes'])
                ->map(fn (string $code) => $mapData->firstWhere('display_code', $code))
                ->filter()
                ->values();

            return array_merge($group, ['slots' => $slots]);
        })->filter(fn (array $group) => $group['slots']->isNotEmpty())->values();
@endphp

<div class="tms-tyre-map-shell">
    @if ($mapData->isEmpty())
        <x-tms.empty-state
            title="No tyre positions configured"
            description="Run php artisan tms:refresh-tyre-layouts to generate axle layouts."
        />
    @else
        <section class="tms-vehicle-tyre-panel">
            <header class="tms-vehicle-tyre-header">
                <div>
                    <p class="tms-vehicle-tyre-kicker">Vehicle tyre map</p>
                    <h3>{{ $vehicle->vehicle_code }}</h3>
                    <p>
                        {{ $vehicle->vehicleType?->name ?: 'Vehicle type not configured' }}
                        @if ($vehicle->plate_number)
                            <span> | {{ $vehicle->plate_number }}</span>
                        @endif
                        @if ($vehicle->odometer)
                            <span> | {{ number_format((int) $vehicle->odometer) }} km</span>
                        @endif
                    </p>
                </div>

                <div class="tms-vehicle-tyre-counts">
                    <div>
                        <span>Mounted</span>
                        <strong>{{ $filled }}/{{ $total }}</strong>
                    </div>
                    <div>
                        <span>Open</span>
                        <strong>{{ $empty }}</strong>
                    </div>
                </div>
            </header>

            <div class="tms-vehicle-tyre-layout">
                <div class="tms-vehicle-tyre-diagram">
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

                <aside class="tms-vehicle-tyre-side">
                    @if ($selectedSlot)
                        <div class="tms-vehicle-tyre-selected">
                            <p>Selected position</p>
                            <div class="tms-selected-position-line">
                                <strong>{{ $selectedSlot['display_code'] ?? $selectedPosition }}</strong>
                                <span>{{ $selectedSlot['label'] ?? 'Tyre position' }}</span>
                            </div>
                            <small>Internal code {{ $selectedPosition }}</small>

                            @if ($selectedTyreId)
                                <dl>
                                    <div>
                                        <dt>Tyre</dt>
                                        <dd>
                                            <a href="{{ url('/admin/tyres/'.$selectedTyreId) }}">
                                                {{ $selectedSlot['tyre_code'] }}
                                            </a>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Brand</dt>
                                        <dd>{{ $selectedSlot['brand'] ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Serial</dt>
                                        <dd>{{ $selectedSlot['serial_number'] ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Status</dt>
                                        <dd>{{ $selectedSlot['status'] ?? 'Empty' }}</dd>
                                    </div>
                                </dl>
                            @else
                                <div class="tms-selected-empty">
                                    <span>Open position</span>
                                    @if ($selectedSlot['install_url'] ?? null)
                                        <a href="{{ $selectedSlot['install_url'] }}">
                                            Fill {{ $selectedSlot['display_code'] ?? $selectedPosition }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="tms-vehicle-tyre-selected is-idle">
                            <p>Select a tyre position</p>
                            <span>Click a labelled tyre position on the diagram.</span>
                        </div>
                    @endif

                    <div class="tms-vehicle-tyre-guide">
                        <h4>Tyre position guide</h4>
                        @foreach ($guideGroups as $group)
                            <section class="tms-guide-group tone-{{ $group['tone'] }}">
                                <header>
                                    {{ $group['title'] }}
                                    @if ($group['subtitle'] ?? null)
                                        <span>{{ $group['subtitle'] }}</span>
                                    @endif
                                </header>
                                <div>
                                    @foreach ($group['slots'] as $slot)
                                        <button
                                            type="button"
                                            wire:key="tyre-guide-position-{{ $vehicle->id }}-{{ $slot['code'] }}"
                                            wire:click="selectPosition('{{ $slot['code'] }}')"
                                            @class([
                                                'tms-guide-row',
                                                'is-selected' => $selectedPosition === $slot['code'],
                                                'is-empty' => ! $slot['tyre_code'],
                                                'is-spare' => in_array($slot['display_code'] ?? $slot['code'], ['W', 'X'], true),
                                            ])
                                        >
                                            <strong>{{ $slot['display_code'] ?? $slot['code'] }}</strong>
                                            <span>
                                                <b>{{ $slot['label'] }}</b>
                                                <small>
                                                    @if ($slot['tyre_code'])
                                                        {{ $slot['tyre_code'] }}
                                                    @elseif ($slot['install_url'] ?? null)
                                                        Open position - fill tyre
                                                    @else
                                                        Open position
                                                    @endif
                                                </small>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                        @unless ($isTrailer)
                            <div class="tms-guide-note">
                                <strong>i</strong>
                                <span>Standard tyre position guide. Spare wheel positions appear only when configured for this vehicle type.</span>
                            </div>
                        @endunless
                    </div>
                </aside>
            </div>
        </section>

        @once
            @vite(['resources/js/tyre-map-konva.js', 'resources/css/tyre-map.css'])
        @endonce

    @endif
</div>
