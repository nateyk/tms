<x-filament-panels::page>
    @php
        $sections = [
            'movements' => ['label' => 'Tyre Movements', 'icon' => 'heroicon-o-arrows-right-left', 'edit' => 'tyre-movements'],
            'transfers' => ['label' => 'Trailer Transfers', 'icon' => 'heroicon-o-truck', 'edit' => 'trailer-transfers'],
            'disposals' => ['label' => 'Tyre Disposals', 'icon' => 'heroicon-o-trash', 'edit' => 'tyre-disposals'],
            'maintenance' => ['label' => 'Maintenance', 'icon' => 'heroicon-o-wrench-screwdriver', 'edit' => 'tyre-maintenances'],
        ];
        $data = $this->getViewData();
    @endphp

    <div class="tms-page">
        <p class="tms-page-intro">
            Vouchers waiting for check, approval, or completion. Open a row to continue the workflow.
        </p>

        <div class="grid w-full gap-6 lg:grid-cols-2">
            @foreach ($sections as $key => $meta)
                @php $items = $data[$key] ?? collect(); @endphp
                <x-tms.card :title="$meta['label']" :description="$items->count().' pending'">
                    @if ($items->isEmpty())
                        <x-tms.empty-state
                            title="No pending items"
                            description="Everything in this queue is up to date."
                        />
                    @else
                        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($items as $item)
                                <li class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                    <div class="flex min-w-0 gap-3">
                                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                            <x-filament::icon :icon="$meta['icon']" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                                @if ($key === 'movements')
                                                    {{ $item->movement_no }}
                                                @elseif ($key === 'transfers')
                                                    {{ $item->transfer_no }}
                                                @elseif ($key === 'disposals')
                                                    {{ $item->disposal_no }}
                                                @else
                                                    {{ $item->maintenance_no }}
                                                @endif
                                            </p>
                                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                                                @if ($key === 'transfers')
                                                    {{ $item->trailer?->vehicle_code ?? 'Trailer' }}
                                                @else
                                                    {{ $item->tyre?->tyre_code ?? 'Tyre' }}
                                                @endif
                                                · {{ $item->status->label() }}
                                            </p>
                                        </div>
                                    </div>
                                    <a
                                        href="{{ url('/admin/'.$meta['edit'].'/'.$item->id.'/edit') }}"
                                        class="shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800"
                                    >
                                        Open
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-tms.card>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
