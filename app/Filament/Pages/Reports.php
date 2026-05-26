<?php

namespace App\Filament\Pages;

use App\Models\TyreDisposal;
use App\Models\TyreMaintenance;
use App\Models\TyreMovement;
use App\Models\TrailerTransfer;
use App\Models\Vehicle;
use App\Services\TyreReportService;
use Spatie\Activitylog\Models\Activity;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property \Filament\Schemas\Schema $form
 */
class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = 'Approvals & Reports';

    protected string $view = 'filament.pages.reports';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return tms_user()?->can('report.view') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'dateFrom' => now()->subMonth()->toDateString(),
            'dateTo' => now()->toDateString(),
            'vehicleId' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                DatePicker::make('dateFrom')->label('From date')->required(),
                DatePicker::make('dateTo')->label('To date')->required(),
                Select::make('vehicleId')
                    ->label('Vehicle (tyre-by-vehicle report)')
                    ->options(fn () => Vehicle::query()->orderBy('vehicle_code')->pluck('vehicle_code', 'id'))
                    ->searchable()
                    ->placeholder('All vehicles'),
            ])
            ->columns(3);
    }

    /** @return array{0: ?string, 1: ?string, 2: ?int} */
    protected function filters(): array
    {
        $data = $this->form->getState();

        return [
            $data['dateFrom'] ?? null,
            $data['dateTo'] ?? null,
            isset($data['vehicleId']) ? (int) $data['vehicleId'] : null,
        ];
    }

    public function exportStock(): StreamedResponse
    {
        $this->authorizeExport();

        $rows = app(TyreReportService::class)->tyreStock();

        return $this->csvDownload('tyre-stock.csv', [
            ['Tyre Code', 'Serial', 'Brand', 'Size', 'Status', 'Location Type', 'Location ID', 'Position'],
            ...$rows->map(fn ($t) => [
                $t->tyre_code,
                $t->serial_number,
                $t->brand?->name,
                $t->size?->size_label,
                $t->status->value,
                $t->current_location_type->value,
                $t->current_location_id,
                $t->current_position_code,
            ]),
        ]);
    }

    public function exportMovements(): StreamedResponse
    {
        $this->authorizeExport();
        [$from, $to] = $this->filters();
        $rows = app(TyreReportService::class)->movementReport($from, $to)->get();

        return $this->csvDownload('tyre-movements.csv', [
            ['Movement No', 'Tyre', 'Type', 'Date', 'Status', 'From', 'To'],
            ...$rows->map(fn (TyreMovement $m) => [
                $m->movement_no,
                $m->tyre?->tyre_code,
                $m->movement_type->value,
                $m->movement_date?->format('Y-m-d'),
                $m->status->value,
                $m->from_location_type?->value,
                $m->to_location_type?->value,
            ]),
        ]);
    }

    public function exportMaintenance(): StreamedResponse
    {
        $this->authorizeExport();
        [$from, $to] = $this->filters();
        $rows = app(TyreReportService::class)->maintenanceCostReport($from, $to)->get();

        return $this->csvDownload('tyre-maintenance.csv', [
            ['No', 'Tyre', 'Problem', 'Date', 'Cost', 'Status'],
            ...$rows->map(fn (TyreMaintenance $r) => [
                $r->maintenance_no,
                $r->tyre?->tyre_code,
                $r->problem_type->value,
                $r->maintenance_date?->format('Y-m-d'),
                $r->cost,
                $r->status->value,
            ]),
        ]);
    }

    public function exportDisposals(): StreamedResponse
    {
        $this->authorizeExport();
        [$from, $to] = $this->filters();
        $rows = app(TyreReportService::class)->disposalReport($from, $to)->get();

        return $this->csvDownload('tyre-disposals.csv', [
            ['No', 'Tyre', 'Reason', 'Status', 'Scrap Value', 'Sold Amount'],
            ...$rows->map(fn (TyreDisposal $r) => [
                $r->disposal_no,
                $r->tyre?->tyre_code,
                $r->disposal_reason->value,
                $r->status->value,
                $r->estimated_scrap_value,
                $r->sold_amount,
            ]),
        ]);
    }

    public function exportTrailerTransfers(): StreamedResponse
    {
        $this->authorizeExport();
        [$from, $to] = $this->filters();
        $rows = app(TyreReportService::class)->trailerTransferHistory($from, $to)->get();

        return $this->csvDownload('trailer-transfers.csv', [
            ['Transfer No', 'Trailer', 'From Power', 'To Power', 'Date', 'Status'],
            ...$rows->map(fn (TrailerTransfer $r) => [
                $r->transfer_no,
                $r->trailer?->vehicle_code,
                $r->fromPowerVehicle?->vehicle_code,
                $r->toPowerVehicle?->vehicle_code,
                $r->transfer_date?->format('Y-m-d'),
                $r->status->value,
            ]),
        ]);
    }

    public function exportAuditTrail(): StreamedResponse
    {
        $this->authorizeExport();
        abort_unless(tms_user()?->can('audit.view'), 403);

        [$from, $to] = $this->filters();
        $rows = app(TyreReportService::class)->auditTrail($from, $to)->get();

        return $this->csvDownload('audit-trail.csv', [
            ['Date', 'Event', 'Description', 'Subject', 'Causer'],
            ...$rows->map(fn (Activity $r) => [
                $r->created_at?->format('Y-m-d H:i'),
                $r->event,
                $r->description,
                $r->subject_type ? class_basename($r->subject_type).' #'.$r->subject_id : '',
                $r->causer?->name ?? $r->causer_id,
            ]),
        ]);
    }

    public function exportTyresByVehicle(): StreamedResponse
    {
        $this->authorizeExport();
        [, , $vehicleId] = $this->filters();
        $rows = app(TyreReportService::class)->tyresByVehicleReport($vehicleId ?: null);

        return $this->csvDownload('tyres-by-vehicle.csv', [
            ['Vehicle ID', 'Position', 'Tyre Code', 'Serial', 'Brand', 'Status'],
            ...$rows->map(fn ($a) => [
                $a->asset_id,
                $a->position_code,
                $a->tyre?->tyre_code,
                $a->tyre?->serial_number,
                $a->tyre?->brand?->name,
                $a->tyre?->status?->value,
            ]),
        ]);
    }

    public function exportKmPerformance(): StreamedResponse
    {
        $this->authorizeExport();
        $rows = app(TyreReportService::class)->tyreKmPerformanceReport();

        return $this->csvDownload('tyre-km-performance.csv', [
            ['Tyre Code', 'Serial', 'Brand', 'Status', 'Total KM', 'Purchase Price', 'Cost Per KM'],
            ...$rows->map(fn ($r) => array_values($r)),
        ]);
    }

    public function exportLifecycle(): StreamedResponse
    {
        $this->authorizeExport();
        $rows = app(TyreReportService::class)->tyreLifecycleReport();

        return $this->csvDownload('tyre-lifecycle.csv', [
            ['Tyre Code', 'Status', 'Location', 'Assignments', 'Movements', 'Maintenance', 'Disposed', 'Total KM'],
            ...$rows->map(fn ($r) => array_values($r)),
        ]);
    }

    protected function authorizeExport(): void
    {
        abort_unless(tms_user()?->can('report.export'), 403);
    }

    protected function csvDownload(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
