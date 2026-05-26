<x-filament-panels::page>
    <div class="tms-page max-w-3xl">
        <p class="tms-page-intro">
            Company and fleet rules used across vouchers and trailer transfers.
        </p>

        <x-tms.card title="General settings">
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end border-t border-gray-100 pt-4 dark:border-gray-800">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Save settings
                    </x-filament::button>
                </div>
            </form>
        </x-tms.card>
    </div>
</x-filament-panels::page>
