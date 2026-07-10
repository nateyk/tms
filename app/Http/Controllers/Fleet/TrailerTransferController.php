<?php

namespace App\Http\Controllers\Fleet;

use App\Enums\AssetType;
use App\Enums\CombinationStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTrailerTransferRequest;
use App\Http\Requests\Fleet\UpdateTrailerTransferRequest;
use App\Http\Requests\Tyres\RejectVoucherRequest;
use App\Models\Location;
use App\Models\TrailerTransfer;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Services\ApprovalService;
use App\Services\TrailerTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrailerTransferController extends Controller
{
    public function __construct(
        private readonly TrailerTransferService $transferService,
        private readonly ApprovalService $approvalService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrailerTransfer::class);

        $transfers = TrailerTransfer::query()
            ->with([
                'trailer:id,vehicle_code,plate_number',
                'fromPowerVehicle:id,vehicle_code,plate_number',
                'toPowerVehicle:id,vehicle_code,plate_number',
                'preparedByUser:id,name',
            ])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TrailerTransfer $transfer) => $this->serializeListRow($transfer));

        return Inertia::render('fleet/trailer-transfers/index', [
            'transfers' => $transfers,
            'filters' => [
                'status' => $request->query('status'),
            ],
            'statusOptions' => collect(VoucherStatus::cases())->map(fn (VoucherStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TrailerTransfer::class);

        return Inertia::render('fleet/trailer-transfers/create', $this->formOptions());
    }

    public function store(StoreTrailerTransferRequest $request): RedirectResponse
    {
        $transfer = $this->transferService->createDraft(
            $request->validated(),
            (int) auth()->id(),
        );

        return redirect()
            ->route('fleet.trailer-transfers.show', $transfer)
            ->with('success', 'Trailer transfer saved as draft.');
    }

    public function show(TrailerTransfer $transfer): Response
    {
        $this->authorize('view', $transfer);

        $transfer->load([
            'trailer',
            'fromPowerVehicle',
            'toPowerVehicle',
            'location',
            'preparedByUser',
            'checkedByUser',
            'approvedByUser',
        ]);

        return Inertia::render('fleet/trailer-transfers/show', [
            'transfer' => $this->serializeDetail($transfer),
            'can' => $this->serializePermissions($transfer),
        ]);
    }

    public function edit(TrailerTransfer $transfer): Response
    {
        $this->authorize('update', $transfer);

        return Inertia::render('fleet/trailer-transfers/edit', [
            ...$this->formOptions(),
            'transfer' => $this->serializeForm($transfer),
        ]);
    }

    public function update(UpdateTrailerTransferRequest $request, TrailerTransfer $transfer): RedirectResponse
    {
        $transfer->update($request->validated());

        return redirect()
            ->route('fleet.trailer-transfers.show', $transfer)
            ->with('success', 'Trailer transfer updated.');
    }

    public function destroy(TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('delete', $transfer);

        $transfer->delete();

        return redirect()
            ->route('fleet.trailer-transfers.index')
            ->with('success', 'Draft transfer deleted.');
    }

    public function submit(TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('submit', $transfer);

        try {
            $this->approvalService->submit($transfer);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer submitted for checking.');
    }

    public function check(TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('check', $transfer);

        try {
            $this->approvalService->check($transfer);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer checked.');
    }

    public function approve(TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('approve', $transfer);

        try {
            $this->approvalService->approve($transfer);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer approved.');
    }

    public function reject(RejectVoucherRequest $request, TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('reject', $transfer);

        try {
            $this->approvalService->reject($transfer, $request->validated('reason'));
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fleet.trailer-transfers.show', $transfer)
            ->with('success', 'Transfer rejected.');
    }

    public function complete(TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('complete', $transfer);

        try {
            $this->approvalService->completeTrailerTransfer($transfer);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fleet.trailer-transfers.show', $transfer)
            ->with('success', 'Transfer completed. Trailer reassigned to new power unit.');
    }

    public function cancel(TrailerTransfer $transfer): RedirectResponse
    {
        $this->authorize('cancel', $transfer);

        try {
            $this->approvalService->cancel($transfer);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fleet.trailer-transfers.index')
            ->with('success', 'Transfer cancelled.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        $activeCombinations = VehicleCombination::query()
            ->where('status', CombinationStatus::Active)
            ->pluck('power_vehicle_id', 'trailer_vehicle_id');

        return [
            'trailers' => Vehicle::query()
                ->where('asset_type', AssetType::Trailer)
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code', 'plate_number'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => $vehicle->displayCodeWithPlate(),
                    'from_power_vehicle_id' => $activeCombinations->get($vehicle->id),
                ]),
            'powerVehicles' => Vehicle::query()
                ->where('asset_type', AssetType::PowerVehicle)
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code', 'plate_number'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => $vehicle->displayCodeWithPlate(),
                ]),
            'locations' => Location::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'label' => collect([$location->code, $location->name])->filter()->implode(' - ') ?: $location->name,
                ]),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeListRow(TrailerTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'display_number' => $transfer->displayNumber(),
            'trailer_code' => $transfer->trailer?->vehicle_code,
            'from_power_code' => $transfer->fromPowerVehicle?->vehicle_code,
            'to_power_code' => $transfer->toPowerVehicle?->vehicle_code,
            'transfer_date' => $transfer->transfer_date?->format('Y-m-d'),
            'status' => $transfer->status->value,
            'status_label' => $transfer->status->label(),
            'prepared_by' => $transfer->preparedByUser?->name,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeForm(TrailerTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'trailer_vehicle_id' => $transfer->trailer_vehicle_id,
            'from_power_vehicle_id' => $transfer->from_power_vehicle_id,
            'to_power_vehicle_id' => $transfer->to_power_vehicle_id,
            'transfer_date' => $transfer->transfer_date?->format('Y-m-d') ?? '',
            'from_odometer' => $transfer->from_odometer,
            'to_odometer' => $transfer->to_odometer,
            'location_id' => $transfer->location_id,
            'reason' => $transfer->reason ?? '',
            'notes' => $transfer->notes ?? '',
        ];
    }

    /** @return array<string, mixed> */
    private function serializeDetail(TrailerTransfer $transfer): array
    {
        return [
            ...$this->serializeForm($transfer),
            'transfer_no' => $transfer->transfer_no,
            'display_number' => $transfer->displayNumber(),
            'status' => $transfer->status->value,
            'status_label' => $transfer->status->label(),
            'trailer_label' => $transfer->trailer?->displayCodeWithPlate(),
            'trailer_vehicle_id' => $transfer->trailer_vehicle_id,
            'from_power_label' => $transfer->fromPowerVehicle?->displayCodeWithPlate() ?? '—',
            'to_power_label' => $transfer->toPowerVehicle?->displayCodeWithPlate(),
            'to_power_vehicle_id' => $transfer->to_power_vehicle_id,
            'location_label' => $transfer->location?->name,
            'prepared_by' => $transfer->preparedByUser?->name,
            'checked_by' => $transfer->checkedByUser?->name,
            'approved_by' => $transfer->approvedByUser?->name,
            'completed_at' => $transfer->completed_at?->toDateTimeString(),
            'pdf_url' => route('vouchers.trailer-transfer.pdf', $transfer),
        ];
    }

    /** @return array<string, bool> */
    private function serializePermissions(TrailerTransfer $transfer): array
    {
        $user = request()->user();

        return [
            'update' => $user?->can('update', $transfer) ?? false,
            'delete' => $user?->can('delete', $transfer) ?? false,
            'submit' => $user?->can('submit', $transfer) ?? false,
            'check' => $user?->can('check', $transfer) ?? false,
            'approve' => $user?->can('approve', $transfer) ?? false,
            'reject' => $user?->can('reject', $transfer) ?? false,
            'complete' => $user?->can('complete', $transfer) ?? false,
            'cancel' => $user?->can('cancel', $transfer) ?? false,
        ];
    }
}
