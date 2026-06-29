<?php

namespace App\Http\Controllers\Tyres;

use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\RejectVoucherRequest;
use App\Http\Requests\Tyres\StoreTyreMovementRequest;
use App\Http\Requests\Tyres\UpdateTyreMovementRequest;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use App\Services\ApprovalService;
use App\Services\TyreMapWorkflowService;
use App\Services\TyreMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TyreMovementController extends Controller
{
    public function __construct(
        private readonly TyreMovementService $movementService,
        private readonly ApprovalService $approvalService,
        private readonly TyreMapWorkflowService $mapWorkflow,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TyreMovement::class);

        $movements = TyreMovement::query()
            ->with(['tyre:id,tyre_code', 'preparedByUser:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TyreMovement $movement) => $this->serializeListRow($movement));

        return Inertia::render('tyres/movements/index', [
            'movements' => $movements,
            'filters' => [
                'status' => $request->query('status'),
            ],
            'statusOptions' => collect(VoucherStatus::cases())->map(fn (VoucherStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TyreMovement::class);

        $prefilled = $this->mapWorkflow->prefilledMovementFromRequest();

        return Inertia::render('tyres/movements/create', [
            ...$this->formOptions(),
            'prefilled' => $prefilled,
        ]);
    }

    public function store(StoreTyreMovementRequest $request): RedirectResponse
    {
        try {
            $movement = $this->movementService->createDraft(
                $request->validated(),
                (int) auth()->id(),
            );
        } catch (TyreBusinessException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.movements.show', $movement)
            ->with('success', 'Movement voucher saved as draft.');
    }

    public function show(TyreMovement $movement): Response
    {
        $this->authorize('view', $movement);

        $movement->load([
            'tyre.brand',
            'preparedByUser',
            'checkedByUser',
            'approvedByUser',
        ]);

        return Inertia::render('tyres/movements/show', [
            'movement' => $this->serializeDetail($movement),
            'can' => $this->serializePermissions($movement),
        ]);
    }

    public function edit(TyreMovement $movement): Response
    {
        $this->authorize('update', $movement);

        $movement->load('tyre');

        return Inertia::render('tyres/movements/edit', [
            ...$this->formOptions(),
            'movement' => $this->serializeForm($movement),
        ]);
    }

    public function update(UpdateTyreMovementRequest $request, TyreMovement $movement): RedirectResponse
    {
        $movement->update($request->validated());

        return redirect()
            ->route('tyres.movements.show', $movement)
            ->with('success', 'Movement voucher updated.');
    }

    public function destroy(TyreMovement $movement): RedirectResponse
    {
        $this->authorize('delete', $movement);

        $movement->delete();

        return redirect()
            ->route('tyres.movements.index')
            ->with('success', 'Draft movement deleted.');
    }

    public function positionOptions(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('create', TyreMovement::class);

        $options = collect($this->mapWorkflow->positionOptionsForVehicle($vehicle->id))
            ->map(fn (string $label, string $code) => [
                'value' => $code,
                'label' => $label,
            ])
            ->values();

        return response()->json($options);
    }

    public function submit(TyreMovement $movement): RedirectResponse
    {
        $this->authorize('submit', $movement);

        try {
            $this->approvalService->submit($movement);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movement submitted for checking.');
    }

    public function check(TyreMovement $movement): RedirectResponse
    {
        $this->authorize('check', $movement);

        try {
            $this->approvalService->check($movement);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movement checked.');
    }

    public function approve(TyreMovement $movement): RedirectResponse
    {
        $this->authorize('approve', $movement);

        try {
            $this->approvalService->approve($movement);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movement approved.');
    }

    public function reject(RejectVoucherRequest $request, TyreMovement $movement): RedirectResponse
    {
        $this->authorize('reject', $movement);

        try {
            $this->approvalService->reject($movement, $request->validated('reason'));
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.movements.show', $movement)
            ->with('success', 'Movement rejected.');
    }

    public function complete(TyreMovement $movement): RedirectResponse
    {
        $this->authorize('complete', $movement);

        try {
            $this->approvalService->completeMovement($movement);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.movements.show', $movement)
            ->with('success', 'Movement completed. Tyre location updated.');
    }

    public function cancel(TyreMovement $movement): RedirectResponse
    {
        $this->authorize('cancel', $movement);

        try {
            $this->approvalService->cancel($movement);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.movements.index')
            ->with('success', 'Movement cancelled.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        $tyres = Tyre::query()
            ->whereIn('status', [
                TyreStatus::Available,
                TyreStatus::Active,
                TyreStatus::Maintenance,
            ])
            ->orderBy('tyre_code')
            ->get(['id', 'tyre_code', 'serial_number', 'status', 'current_location_type', 'current_location_id', 'current_position_code'])
            ->map(fn (Tyre $tyre) => [
                'id' => $tyre->id,
                'tyre_code' => $tyre->tyre_code,
                'serial_number' => $tyre->serial_number,
                'status_label' => $tyre->status->label(),
                'current_location_type' => $tyre->current_location_type?->value,
                'current_location_id' => $tyre->current_location_id,
                'current_position_code' => $tyre->current_position_code,
                'source_label' => $this->tyreSourceLabel($tyre),
            ]);

        return [
            'tyres' => $tyres,
            'stores' => Store::query()->orderBy('name')->get(['id', 'code', 'name'])->map(fn (Store $s) => [
                'id' => $s->id,
                'label' => collect([$s->code, $s->name])->filter()->implode(' - ') ?: "Store #{$s->id}",
            ]),
            'powerVehicles' => Vehicle::query()
                ->whereIn('asset_type', ['power_vehicle', 'rigid_truck'])
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code', 'plate_number'])
                ->map(fn (Vehicle $v) => [
                    'id' => $v->id,
                    'label' => $v->displayCodeWithPlate(),
                ]),
            'trailers' => Vehicle::query()
                ->where('asset_type', 'trailer')
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code', 'plate_number'])
                ->map(fn (Vehicle $v) => [
                    'id' => $v->id,
                    'label' => $v->displayCodeWithPlate(),
                ]),
            'destinationTypes' => collect(TyreLocationType::cases())->map(fn (TyreLocationType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeListRow(TyreMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'movement_no' => $movement->movement_no,
            'display_number' => $movement->displayNumber(),
            'tyre_code' => $movement->tyre?->tyre_code,
            'movement_type' => $movement->movement_type->label(),
            'movement_date' => $movement->movement_date?->format('Y-m-d'),
            'to_location_label' => $movement->to_location_type?->label(),
            'status' => $movement->status->value,
            'status_label' => $movement->status->label(),
            'prepared_by' => $movement->preparedByUser?->name,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeForm(TyreMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'tyre_id' => $movement->tyre_id,
            'movement_type_label' => $movement->movement_type?->label(),
            'movement_date' => $movement->movement_date?->format('Y-m-d') ?? '',
            'to_location_type' => $movement->to_location_type?->value ?? '',
            'to_location_id' => $movement->to_location_id,
            'to_position_code' => $movement->to_position_code ?? '',
            'from_odometer' => $movement->from_odometer,
            'to_odometer' => $movement->to_odometer,
            'reason' => $movement->reason ?? '',
            'notes' => $movement->notes ?? '',
            'from_location_type' => $movement->from_location_type?->value,
            'from_location_id' => $movement->from_location_id,
            'from_position_code' => $movement->from_position_code,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeDetail(TyreMovement $movement): array
    {
        return [
            ...$this->serializeForm($movement),
            'movement_no' => $movement->movement_no,
            'display_number' => $movement->displayNumber(),
            'status' => $movement->status->value,
            'status_label' => $movement->status->label(),
            'tyre_code' => $movement->tyre?->tyre_code,
            'tyre_id' => $movement->tyre_id,
            'from_location_display' => $movement->fromLocationDisplay(),
            'from_position_display' => $movement->fromPositionDisplay(),
            'to_location_display' => $movement->toLocationDisplay(),
            'to_position_display' => $movement->toPositionDisplay(),
            'prepared_by' => $movement->preparedByUser?->name,
            'checked_by' => $movement->checkedByUser?->name,
            'approved_by' => $movement->approvedByUser?->name,
            'submitted_at' => $movement->submitted_at?->toDateTimeString(),
            'checked_at' => $movement->checked_at?->toDateTimeString(),
            'approved_at' => $movement->approved_at?->toDateTimeString(),
            'completed_at' => $movement->completed_at?->toDateTimeString(),
            'pdf_url' => route('vouchers.movement.pdf', $movement),
        ];
    }

    /** @return array<string, bool> */
    private function serializePermissions(TyreMovement $movement): array
    {
        $user = request()->user();

        return [
            'update' => $user?->can('update', $movement) ?? false,
            'delete' => $user?->can('delete', $movement) ?? false,
            'submit' => $user?->can('submit', $movement) ?? false,
            'check' => $user?->can('check', $movement) ?? false,
            'approve' => $user?->can('approve', $movement) ?? false,
            'reject' => $user?->can('reject', $movement) ?? false,
            'complete' => $user?->can('complete', $movement) ?? false,
            'cancel' => $user?->can('cancel', $movement) ?? false,
        ];
    }

    private function tyreSourceLabel(Tyre $tyre): string
    {
        if (! $tyre->current_location_type || ! $tyre->current_location_id) {
            return 'Unknown location';
        }

        return match ($tyre->current_location_type) {
            TyreLocationType::Store => Store::query()->find($tyre->current_location_id)?->name ?? "Store #{$tyre->current_location_id}",
            TyreLocationType::PowerVehicle, TyreLocationType::Trailer => Vehicle::query()->find($tyre->current_location_id)?->displayCodeWithPlate() ?? "Vehicle #{$tyre->current_location_id}",
            TyreLocationType::MaintenanceCenter => "Maintenance center #{$tyre->current_location_id}",
            TyreLocationType::DisposalYard => "Disposal yard #{$tyre->current_location_id}",
        };
    }
}
