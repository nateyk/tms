@props([
    'title' => 'Nothing here yet',
    'description' => null,
])

<div class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-white px-6 py-10 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 ring-1 ring-amber-100 dark:bg-amber-950/30 dark:ring-amber-900/60">
        <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6 text-amber-600 dark:text-amber-300" />
    </div>
    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
