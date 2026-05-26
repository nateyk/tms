<x-filament-panels::page>
    <div class="tms-page">
        <p class="tms-page-intro">
            Export CSV reports for inventory, operations, and audit. Date filters apply to operational exports.
        </p>

        <x-tms.card title="Filters" description="Narrow exports by period and vehicle where supported.">
            <form wire:submit.prevent>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {{ $this->form }}
                </div>
            </form>
        </x-tms.card>

        <div class="grid w-full gap-6 lg:grid-cols-2 xl:grid-cols-3">
            <x-tms.card title="Inventory" description="Stock and lifecycle exports">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                    <x-tms.export-button wire:click="exportStock" variant="primary">
                        Tyre Stock
                    </x-tms.export-button>
                    <x-tms.export-button wire:click="exportTyresByVehicle">
                        Tyres by Vehicle
                    </x-tms.export-button>
                    <x-tms.export-button wire:click="exportLifecycle">
                        Tyre Lifecycle
                    </x-tms.export-button>
                    <x-tms.export-button wire:click="exportKmPerformance">
                        KM &amp; Cost per KM
                    </x-tms.export-button>
                </div>
            </x-tms.card>

            <x-tms.card title="Operations" description="Uses the date range above">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                    <x-tms.export-button wire:click="exportMovements" variant="primary">
                        Tyre Movements
                    </x-tms.export-button>
                    <x-tms.export-button wire:click="exportMaintenance">
                        Maintenance Cost
                    </x-tms.export-button>
                    <x-tms.export-button wire:click="exportDisposals">
                        Tyre Disposals
                    </x-tms.export-button>
                    <x-tms.export-button wire:click="exportTrailerTransfers">
                        Trailer Transfers
                    </x-tms.export-button>
                </div>
            </x-tms.card>

            <x-tms.card title="Audit" description="Activity and compliance">
                <x-tms.export-button wire:click="exportAuditTrail" variant="primary">
                    Audit Trail
                </x-tms.export-button>
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    Exports respect your role permissions.
                </p>
            </x-tms.card>
        </div>
    </div>
</x-filament-panels::page>
