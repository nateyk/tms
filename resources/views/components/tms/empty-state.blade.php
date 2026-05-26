@props([
    'title' => 'Nothing here yet',
    'description' => null,
])

<div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50/80 px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-900/50">
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
        <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6 text-gray-400" />
    </div>
    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
