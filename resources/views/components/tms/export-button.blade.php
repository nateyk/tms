@props([
    'icon' => 'heroicon-o-arrow-down-tray',
    'variant' => 'primary',
])

@php
    $base = 'inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

    $variantClass = match ($variant) {
        'primary' => 'bg-amber-600 text-white shadow-sm hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600',
        default => 'border border-gray-200 bg-white text-gray-900 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800',
    };
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => $base.' '.$variantClass]) }}
>
    @if ($icon)
        <x-filament::icon :icon="$icon" class="h-4 w-4 shrink-0" />
    @endif
    {{ $slot }}
</button>
