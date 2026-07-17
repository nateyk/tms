<?php

namespace App\Http\Controllers\Tyres;

use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\CompleteTyreMovementRequest;
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
use App\Services\VehicleOdometerService;
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
        private readonly VehicleOdometerService $odometerService,
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

    public function options(): JsonResponse
    {
        $this->authorize('create', TyreMovement::class);

        return response()->json($this->formOptions());
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
        abort(403, 'Movement vouchers cannot be deleted. Void the voucher instead.');
    }

    public function positionOptions(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('create', TyreMovement::class);

        $attachedPower = $vehicle->isTrailer() ? $vehicle->attachedPower() : null;
        $rootVehicle = $attachedPower ?? $vehicle;
        $rootOwnerType = $rootVehicle->isTrailer() ? 'trailer' : 'power_vehicle';

        $options = collect($this->ownerPositionOptions($rootVehicle, $rootOwnerType));
        $attachedTrailer = $rootVehicle->attachedTrailer();

        if ($attachedTrailer) {
            $options = $options->concat($this->ownerPositionOptions($attachedTrailer, 'trailer'));
        }

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

    public function complete(CompleteTyreMovementRequest $request, TyreMovement $movement): RedirectResponse
    {
        $this->authorize('complete', $movement);

        try {
            $movement->update($request->validated());
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
            ->with('success', 'Movement voucher voided.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        $tyres = Tyre::query()
            ->with(['brand:id,name', 'size:id,size_label', 'activeAssignment'])
            ->whereIn('status', [
                TyreStatus::Available,
                TyreStatus::Active,
                TyreStatus::Maintenance,
            ])
            ->orderBy('tyre_code')
            ->get(['id', 'tyre_code', 'serial_number', 'brand_id', 'size_id', 'status', 'current_location_type', 'current_location_id', 'current_position_code'])
            ->map(fn (Tyre $tyre) => [
                'id' => $tyre->id,
                'tyre_code' => $tyre->tyre_code,
                'serial_number' => $tyre->serial_number,
                'brand' => $tyre->brand?->name,
                'size' => $tyre->size?->size_label,
                'status_label' => $tyre->status->label(),
                'status' => $tyre->status->value,
                'current_location_type' => $tyre->current_location_type?->value,
                'current_location_id' => $tyre->current_location_id,
                'current_position_code' => $tyre->current_position_code,
                'source_label' => $this->tyreSourceLabel($tyre),
                'source_position_label' => $tyre->currentPositionDisplay(),
                'position_type' => $this->tyrePositionType($tyre),
                'current_vehicle_odometer' => $this->tyreVehicle($tyre)?->odometer,
                'installed_odometer' => $tyre->activeAssignment?->installed_odometer,
                'has_pending_movement' => $this->tyreHasPendingMovement($tyre),
            ]);

        return [
            'tyres' => $tyres,
            'stores' => Store::query()->orderBy('name')->get(['id', 'code', 'name'])->map(fn (Store $s) => [
                'id' => $s->id,
                'label' => collect([$s->code, $s->name])->filter()->implode(' - ') ?: "Store #{$s->id}",
            ]),
            'powerVehicles' => Vehicle::query()
                ->with([
                    'vehicleType:id,name,tyre_count,axle_count,layout_json',
                    'activeCombinationAsPower.trailer.vehicleType:id,name,tyre_count,axle_count,layout_json',
                ])
                ->whereIn('asset_type', ['power_vehicle', 'rigid_truck'])
                ->where('status', VehicleStatus::Active->value)
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code', 'plate_number', 'vehicle_type_id', 'asset_type', 'status', 'odometer'])
                ->map(fn (Vehicle $v) => $this->serializeDestinationVehicle($v)),
            'trailers' => Vehicle::query()
                ->with('vehicleType:id,name,tyre_count,axle_count,layout_json')
                ->where('asset_type', 'trailer')
                ->where('status', VehicleStatus::Active->value)
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code', 'plate_number', 'vehicle_type_id', 'asset_type', 'status', 'odometer'])
                ->map(fn (Vehicle $v) => $this->serializeDestinationVehicle($v)),
            'destinationTypes' => collect([
                TyreLocationType::Store,
                TyreLocationType::PowerVehicle,
                TyreLocationType::Trailer,
            ])->map(fn (TyreLocationType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'destinationTargets' => [
                ['value' => 'store', 'label' => 'Store'],
                ['value' => 'vehicle_unit', 'label' => 'Vehicle / Attached Unit'],
            ],
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
        $sourceVehicle = $this->movementVehicle($movement->from_location_type, $movement->from_location_id);
        $destinationVehicle = $this->movementVehicle($movement->to_location_type, $movement->to_location_id);

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
            'requires_source_odometer' => $sourceVehicle !== null,
            'requires_destination_odometer' => $destinationVehicle !== null,
            'source_odometer_label' => $movement->fromLocationDisplay(),
            'destination_odometer_label' => $movement->toLocationDisplay(),
            'source_vehicle_latest_odometer' => $sourceVehicle ? $this->odometerService->getLatestOdometer($sourceVehicle) : null,
            'destination_vehicle_latest_odometer' => $destinationVehicle ? $this->odometerService->getLatestOdometer($destinationVehicle) : null,
        ];
    }

    /** @return array<string, bool> */
    private function serializePermissions(TyreMovement $movement): array
    {
        $user = request()->user();

        return [
            'update' => $user?->can('update', $movement) ?? false,
            'delete' => false,
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

    /** @return array<string, mixed> */
    private function serializeDestinationVehicle(Vehicle $vehicle): array
    {
        $positions = collect($this->mapWorkflow->positionStatusForVehicle($vehicle));
        $available = $positions->where('is_empty', true)->count();
        $mounted = $positions->where('is_occupied', true)->count();
        $attachedTrailer = $vehicle->attachedTrailer();
        $trailerPositions = $attachedTrailer
            ? collect($this->mapWorkflow->positionStatusForVehicle($attachedTrailer))
            : collect();
        $trailerAvailable = $trailerPositions->where('is_empty', true)->count();

        return [
            'id' => $vehicle->id,
            'label' => sprintf(
                '%s - %s - %d positions available - Odo %s KM',
                $vehicle->displayCodeWithPlate(),
                $vehicle->vehicleType?->name ?? 'Vehicle',
                $available,
                $vehicle->odometer !== null ? number_format((int) $vehicle->odometer) : 'not set'
            ),
            'vehicle_code' => $vehicle->vehicle_code,
            'plate_number' => $vehicle->plate_number,
            'vehicle_type_name' => $vehicle->vehicleType?->name,
            'asset_type' => $vehicle->asset_type->value,
            'current_odometer' => $vehicle->odometer,
            'mounted_count' => $mounted,
            'available_position_count' => $available,
            'status' => $vehicle->status->value,
            'power_available_count' => $available,
            'trailer_available_count' => $trailerAvailable,
            'total_available_count' => $available + $trailerAvailable,
            'attached_trailer' => $attachedTrailer ? [
                'id' => $attachedTrailer->id,
                'vehicle_code' => $attachedTrailer->vehicle_code,
                'plate_number' => $attachedTrailer->plate_number,
                'label' => $attachedTrailer->displayCodeWithPlate(),
                'vehicle_type_name' => $attachedTrailer->vehicleType?->name,
                'current_odometer' => $attachedTrailer->odometer,
                'available_position_count' => $trailerAvailable,
            ] : null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function ownerPositionOptions(Vehicle $vehicle, string $ownerType): array
    {
        return collect($this->mapWorkflow->positionStatusForVehicle($vehicle))
            ->map(fn (array $position) => [
                'value' => sprintf('%s:%d:%s', $ownerType, $vehicle->id, $position['code']),
                'owner_type' => $ownerType,
                'owner_vehicle_id' => $vehicle->id,
                'owner_vehicle_code' => $vehicle->vehicle_code,
                'owner_label' => $vehicle->displayCodeWithPlate(),
                'owner_current_odometer' => $vehicle->odometer,
                'code' => $position['code'],
                'display_code' => $position['display_code'],
                'label' => $position['label'],
                'type' => $position['type'],
                'is_spare_position' => $position['type'] === 'spare',
                'is_empty' => $position['is_empty'],
                'is_occupied' => $position['is_occupied'],
                'mounted_tyre_id' => $position['mounted_tyre_id'],
                'mounted_tyre_code' => $position['mounted_tyre_code'],
                'disabled' => $position['is_occupied'],
                'disabled_reason' => $position['disabled_reason'],
            ])
            ->values()
            ->all();
    }

    private function tyreVehicle(Tyre $tyre): ?Vehicle
    {
        if (! $tyre->current_location_id || ! in_array($tyre->current_location_type, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)) {
            return null;
        }

        return Vehicle::query()->find($tyre->current_location_id);
    }

    private function tyrePositionType(Tyre $tyre): ?string
    {
        $vehicle = $this->tyreVehicle($tyre);
        if (! $vehicle || ! $tyre->current_position_code) {
            return null;
        }

        return $this->mapWorkflow->isSparePositionForVehicle($vehicle, $tyre->current_position_code)
            ? 'spare'
            : 'running';
    }

    private function tyreHasPendingMovement(Tyre $tyre): bool
    {
        return TyreMovement::query()
            ->where('tyre_id', $tyre->id)
            ->whereIn('status', [
                VoucherStatus::Draft,
                VoucherStatus::Submitted,
                VoucherStatus::Checked,
                VoucherStatus::Approved,
            ])
            ->exists();
    }

    private function movementVehicle(?TyreLocationType $locationType, ?int $locationId): ?Vehicle
    {
        if (! $locationId || ! in_array($locationType, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)) {
            return null;
        }

        return Vehicle::query()->find($locationId);
    }
}
