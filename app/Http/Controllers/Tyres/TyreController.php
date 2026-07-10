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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TyreController extends Controller
{
    public function __construct(
        private readonly TyreRegistrationService $registrationService,
        private readonly TyreQrCodeService $qrCodeService,
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
            'usage_summary' => $tyre->calculateUsageSummary(),
            'usage_history' => $tyre->getUsageHistory(),
            'created_at' => $tyre->created_at?->toDateTimeString(),
            'updated_at' => $tyre->updated_at?->toDateTimeString(),
            'recent_movements' => $tyre->movements->map(fn ($m) => [
                'movement_no' => $m->movement_no,
                'movement_type' => $m->movement_type->label(),
                'status' => $m->status->label(),
            ]),
        ];
    }
}
