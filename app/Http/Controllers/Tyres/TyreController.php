<?php

namespace App\Http\Controllers\Tyres;

use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Exceptions\TyreBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\StoreTyreRequest;
use App\Http\Requests\Tyres\UpdateTyreRequest;
use App\Models\Tyre;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Services\TyreQrCodeService;
use App\Services\TyreRegistrationService;
use App\Services\TyreUsageTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TyreController extends Controller
{
    public function __construct(
        private readonly TyreRegistrationService $registrationService,
        private readonly TyreQrCodeService $qrCodeService,
        private readonly TyreUsageTrackingService $usageTrackingService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tyre::class);

        $tyres = Tyre::query()
            ->with(['brand:id,name', 'size:id,size_label', 'activeAssignment.vehicle:id,vehicle_code,plate_number'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('tyre_code')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tyre $tyre) => $this->serializeListRow($tyre));

        return Inertia::render('tyres/index', [
            'tyres' => $tyres,
            'filters' => [
                'status' => $request->query('status'),
            ],
            'statusOptions' => collect(TyreStatus::cases())->map(fn (TyreStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tyre::class);

        return Inertia::render('tyres/create', $this->formOptions());
    }

    public function store(StoreTyreRequest $request): RedirectResponse
    {
        $tyre = $this->registrationService->register($request->validated());

        return redirect()
            ->route('tyres.show', $tyre)
            ->with('success', 'Tyre registered and pending approval.');
    }

    public function show(Tyre $tyre): Response
    {
        $this->authorize('view', $tyre);

        $tyre->load([
            'brand',
            'size',
            'movements' => fn ($q) => $q->latest()->limit(10),
            'activeAssignment.vehicle',
            'baseline',
            'inspections' => fn ($q) => $q->with(['auditedBy', 'vehicle'])->latest('inspection_date')->latest('created_at')->limit(10),
        ]);

        return Inertia::render('tyres/show', [
            'tyre' => $this->serializeDetail($tyre),
            'can' => [
                'update' => request()->user()?->can('update', $tyre) ?? false,
                'delete' => request()->user()?->can('delete', $tyre) ?? false,
                'approve' => request()->user()?->can('approve', $tyre) ?? false,
            ],
        ]);
    }

    public function edit(Tyre $tyre): Response
    {
        $this->authorize('update', $tyre);

        return Inertia::render('tyres/edit', [
            ...$this->formOptions(),
            'tyre' => $this->serializeForm($tyre),
        ]);
    }

    public function update(UpdateTyreRequest $request, Tyre $tyre): RedirectResponse
    {
        $tyre->update($request->validated());

        return redirect()
            ->route('tyres.show', $tyre)
            ->with('success', 'Tyre updated successfully.');
    }

    public function destroy(Tyre $tyre): RedirectResponse
    {
        $this->authorize('delete', $tyre);

        $tyre->delete();

        return redirect()
            ->route('tyres.index')
            ->with('success', 'Tyre deleted successfully.');
    }

    public function approve(Tyre $tyre): RedirectResponse
    {
        $this->authorize('approve', $tyre);

        try {
            $this->registrationService->approve($tyre, (int) auth()->id());
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.show', $tyre)
            ->with('success', 'Tyre registration approved and QR code generated.');
    }

    public function regenerateQr(Tyre $tyre): RedirectResponse
    {
        $this->authorize('view', $tyre);

        if ($tyre->status === TyreStatus::PendingApproval) {
            return back()->with('error', 'Approve registration before generating a QR code.');
        }

        $this->qrCodeService->generateForTyre($tyre->fresh());

        return back()->with('success', 'QR code regenerated.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'brands' => TyreBrand::query()->orderBy('name')->get(['id', 'name']),
            'sizes' => TyreSize::query()->orderBy('size_label')->get(['id', 'size_label']),
            'sources' => collect(TyreSource::cases())->map(fn (TyreSource $source) => [
                'value' => $source->value,
                'label' => $source->label(),
            ])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeListRow(Tyre $tyre): array
    {
        return [
            'id' => $tyre->id,
            'tyre_code' => $tyre->tyre_code,
            'serial_number' => $tyre->serial_number,
            'brand_name' => $tyre->brand?->name,
            'current_tread_depth' => $tyre->current_tread_depth,
            'current_location_type' => $tyre->current_location_type->label(),
            'vehicle_plate' => $tyre->currentVehiclePlateDisplay(),
            'current_position_code' => $tyre->currentPositionDisplay(),
            'status' => $tyre->status->value,
            'status_label' => $tyre->status->label(),
            'status_color' => $tyre->status->mapColor(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeForm(Tyre $tyre): array
    {
        return [
            'id' => $tyre->id,
            'tyre_code' => $tyre->tyre_code,
            'serial_number' => $tyre->serial_number,
            'brand_id' => $tyre->brand_id,
            'size_id' => $tyre->size_id,
            'pattern' => $tyre->pattern ?? '',
            'supplier' => $tyre->supplier ?? '',
            'source' => $tyre->source->value,
            'purchase_date' => $tyre->purchase_date?->format('Y-m-d') ?? '',
            'purchase_price' => (float) $tyre->purchase_price,
            'invoice_number' => $tyre->invoice_number ?? '',
            'initial_tread_depth' => $tyre->initial_tread_depth,
            'current_tread_depth' => $tyre->current_tread_depth,
            'notes' => $tyre->notes ?? '',
        ];
    }

    /** @return array<string, mixed> */
    private function serializeDetail(Tyre $tyre): array
    {
        $usage = $this->usageTrackingService->calculateTyreUsage($tyre);
        $baseline = $tyre->baseline;
        $latestAudit = $tyre->inspections->first();

        return [
            ...$this->serializeForm($tyre),
            'brand_name' => $tyre->brand?->name,
            'size_label' => $tyre->size?->size_label,
            'status' => $tyre->status->value,
            'status_label' => $tyre->status->label(),
            'status_color' => $tyre->status->mapColor(),
            'current_location_type' => $tyre->current_location_type->label(),
            'current_location_id' => $tyre->current_location_id,
            'current_position_code' => $tyre->currentPositionDisplay(),
            'vehicle_plate' => $tyre->currentVehiclePlateDisplay(),
            'source_label' => $tyre->source->label(),
            'qr_public_url' => $this->qrCodeService->publicUrl($tyre),
            'qr_scan_url' => route('tyres.scan', $tyre->tyre_code),
            'total_km' => $tyre->totalKmUsed(),
            'cost_per_km' => $tyre->costPerKm(),
            'created_at' => $tyre->created_at?->toDateTimeString(),
            'updated_at' => $tyre->updated_at?->toDateTimeString(),
            'recent_movements' => $tyre->movements->map(fn ($m) => [
                'movement_no' => $m->movement_no,
                'movement_type' => $m->movement_type->label(),
                'status' => $m->status->label(),
            ]),
            'recent_maintenance' => [],
            'usage_summary' => $usage,
            'baseline' => $baseline ? [
                'id' => $baseline->id,
                'baseline_percentage' => (float) $baseline->baseline_percentage,
                'baseline_odometer' => $baseline->baseline_odometer,
                'expected_life_km' => $baseline->expected_life_km,
                'baseline_date' => $baseline->baseline_date?->format('Y-m-d'),
                'edit_url' => route('tyres.baselines.edit', $baseline->id),
                'view_url' => route('tyres.baselines.show', $baseline->id),
            ] : null,
            'latest_audit' => $latestAudit ? [
                'audited_remaining_percentage' => $latestAudit->audited_remaining_percentage !== null ? (float) $latestAudit->audited_remaining_percentage : null,
                'calculated_remaining_percentage' => $latestAudit->calculated_remaining_percentage_at_audit !== null ? (float) $latestAudit->calculated_remaining_percentage_at_audit : $usage['calculated_remaining_percentage'],
                'variance_percentage' => $latestAudit->audited_remaining_percentage !== null
                    ? round((float) $latestAudit->audited_remaining_percentage - (float) ($latestAudit->calculated_remaining_percentage_at_audit ?? $usage['calculated_remaining_percentage']), 2)
                    : null,
                'tread_depth_mm' => $latestAudit->tread_depth !== null ? (float) $latestAudit->tread_depth : null,
                'condition_status' => $latestAudit->condition,
                'audit_odometer' => $latestAudit->audit_odometer,
                'odometer_km' => $latestAudit->audit_odometer,
                'vehicle_code' => $latestAudit->vehicle?->displayCodeWithPlate(),
                'position_code' => $latestAudit->position_code,
                'audited_by' => $latestAudit->auditedBy?->name ?? $latestAudit->inspector,
                'recorded_at' => $latestAudit->created_at?->toDateTimeString(),
                'reason' => $latestAudit->reason,
                'audit_date' => $latestAudit->inspection_date?->format('Y-m-d'),
                'notes' => $latestAudit->notes,
            ] : null,
            'audit_history' => $tyre->inspections->sortByDesc(fn ($inspection) => $inspection->inspection_date?->timestamp ?? 0)->values()->map(fn ($inspection) => [
                'id' => $inspection->id,
                'date' => $inspection->inspection_date?->format('Y-m-d'),
                'recorded_at' => $inspection->created_at?->toDateTimeString(),
                'odometer' => $inspection->audit_odometer,
                'vehicle_code' => $inspection->vehicle?->displayCodeWithPlate(),
                'position_code' => $inspection->position_code,
                'calculated_remaining_percentage' => $inspection->calculated_remaining_percentage_at_audit !== null ? (float) $inspection->calculated_remaining_percentage_at_audit : null,
                'audited_remaining_percentage' => $inspection->audited_remaining_percentage !== null ? (float) $inspection->audited_remaining_percentage : null,
                'variance_percentage' => $inspection->audited_remaining_percentage !== null && $inspection->calculated_remaining_percentage_at_audit !== null
                    ? round((float) $inspection->audited_remaining_percentage - (float) $inspection->calculated_remaining_percentage_at_audit, 2)
                    : null,
                'tread_depth_mm' => $inspection->tread_depth !== null ? (float) $inspection->tread_depth : null,
                'status' => $inspection->condition,
                'audited_by' => $inspection->auditedBy?->name ?? $inspection->inspector,
                'reason' => $inspection->reason,
                'notes' => $inspection->notes,
            ])->values(),
            'action_urls' => [
                'record_audit' => route('tyres.condition-audits.create', $tyre->id),
                'create_movement' => route('tyres.movements.create', [
                    'tyre_id' => $tyre->id,
                    'source_location_type' => $tyre->current_location_type?->value,
                    'source_vehicle_id' => $tyre->current_location_id,
                    'source_position' => $tyre->current_position_code,
                ]),
                'set_baseline' => route('tyres.baselines.create', ['tyre_id' => $tyre->id]),
                'view_baseline' => $baseline ? route('tyres.baselines.show', $baseline->id) : null,
            ],
        ];
    }
}
