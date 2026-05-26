@props([
    'title' => null,
    'description' => null,
])

<div
    {{ $attributes->merge([
        'class' => 'overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900',
    ]) }}
>
    @if ($title)
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $title }}
            </h3>
            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div @class(['px-6 py-5' => $title, 'p-6' => ! $title])>
        {{ $slot }}
    </div>
</div>
