<?php

namespace App\Http\Controllers\Tyres;

use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\StoreTyreDisposalRequest;
use App\Models\TyreDisposal;
use App\Services\ApprovalService;
use App\Services\TyreDisposalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TyreDisposalController extends Controller
{
    public function __construct(
        private readonly TyreDisposalService $disposalService,
        private readonly ApprovalService $approvalService,
    ) {}

    public function index(): Response
    {
        $this->authorizeAnyDisposalAction();

        $disposals = TyreDisposal::query()
            ->with(['tyre:id,tyre_code,serial_number', 'preparedByUser:id,name'])
            ->latest()
            ->paginate(20)
            ->through(fn (TyreDisposal $disposal): array => $this->serializeDisposal($disposal));

        return Inertia::render('tyres/disposals/index', [
            'disposals' => $disposals,
            'canCreate' => auth()->user()?->can('disposal.create') ?? false,
        ]);
    }

    public function store(StoreTyreDisposalRequest $request): RedirectResponse
    {
        try {
            $disposal = $this->disposalService->createDraft([
                'tyre_id' => $request->integer('tyre_id'),
                'disposal_reason' => $request->string('disposal_reason')->toString(),
                'final_condition' => $request->input('final_condition'),
                'notes' => $request->input('disposal_notes'),
            ], (int) $request->user()->id);
        } catch (TyreBusinessException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('tyres.disposals.show', $disposal)
            ->with('success', "Disposal voucher {$disposal->disposal_no} created as a draft.");
    }

    public function show(TyreDisposal $disposal): Response
    {
        $this->authorizeAnyDisposalAction();
        $disposal->load(['tyre.brand', 'tyre.size', 'preparedByUser:id,name']);

        $status = $disposal->status instanceof VoucherStatus
            ? $disposal->status
            : VoucherStatus::from((string) $disposal->status);
        $user = auth()->user();

        return Inertia::render('tyres/disposals/show', [
            'disposal' => [
                ...$this->serializeDisposal($disposal),
                'tyre_brand' => $disposal->tyre?->brand?->name,
                'tyre_size' => $disposal->tyre?->size?->size_label,
                'final_condition' => $disposal->final_condition,
                'notes' => $disposal->notes,
                'last_location' => $this->lastLocationLabel($disposal),
                'final_km_used' => $disposal->final_km_used,
            ],
            'actions' => [
                'can_submit' => $status === VoucherStatus::Draft && ($user?->can('disposal.create') ?? false),
                'can_check' => $status === VoucherStatus::Submitted && ($user?->can('disposal.check') ?? false),
                'can_approve' => in_array($status, [VoucherStatus::Submitted, VoucherStatus::Checked], true) && ($user?->can('disposal.approve') ?? false),
                'can_complete' => $status === VoucherStatus::Approved && ($user?->can('disposal.approve') ?? false),
                'can_void' => ! $status->isTerminal() && (($user?->id === $disposal->prepared_by && $user?->can('disposal.create')) || ($user?->can('disposal.approve') ?? false)),
            ],
        ]);
    }

    public function submit(TyreDisposal $disposal): RedirectResponse
    {
        return $this->transition($disposal, 'disposal.create', fn (): mixed => $this->approvalService->submit($disposal), 'Disposal submitted for checking.');
    }

    public function check(TyreDisposal $disposal): RedirectResponse
    {
        return $this->transition($disposal, 'disposal.check', fn (): mixed => $this->approvalService->check($disposal), 'Disposal checked.');
    }

    public function approve(TyreDisposal $disposal): RedirectResponse
    {
        return $this->transition($disposal, 'disposal.approve', fn (): mixed => $this->approvalService->approve($disposal), 'Disposal approved.');
    }

    public function complete(TyreDisposal $disposal): RedirectResponse
    {
        return $this->transition($disposal, 'disposal.approve', fn (): mixed => $this->approvalService->completeDisposal($disposal), 'Tyre disposal completed.');
    }

    public function void(TyreDisposal $disposal): RedirectResponse
    {
        $status = $disposal->status instanceof VoucherStatus ? $disposal->status : VoucherStatus::from((string) $disposal->status);
        $canVoidOwnDraft = auth()->id() === $disposal->prepared_by && auth()->user()?->can('disposal.create');

        abort_unless($canVoidOwnDraft || auth()->user()?->can('disposal.approve'), 403);

        try {
            $this->approvalService->cancel($disposal);
        } catch (TyreBusinessException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Disposal {$disposal->disposal_no} voided.");
    }

    private function transition(TyreDisposal $disposal, string $permission, callable $transition, string $message): RedirectResponse
    {
        abort_unless(auth()->user()?->can($permission), 403);

        try {
            $transition();
        } catch (TyreBusinessException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $message);
    }

    private function authorizeAnyDisposalAction(): void
    {
        abort_unless(
            auth()->user()?->canAny(['disposal.create', 'disposal.check', 'disposal.approve', 'disposal.reject']),
            403,
        );
    }

    private function serializeDisposal(TyreDisposal $disposal): array
    {
        $status = $disposal->status instanceof VoucherStatus
            ? $disposal->status
            : VoucherStatus::from((string) $disposal->status);

        return [
            'id' => $disposal->id,
            'disposal_no' => $disposal->disposal_no,
            'tyre_code' => $disposal->tyre?->tyre_code,
            'tyre_serial_number' => $disposal->tyre?->serial_number,
            'disposal_reason' => $disposal->disposal_reason?->label(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'prepared_by' => $disposal->preparedByUser?->name,
            'created_at' => $disposal->created_at?->toDateString(),
            'view_url' => route('tyres.disposals.show', $disposal),
        ];
    }

    private function lastLocationLabel(TyreDisposal $disposal): string
    {
        $position = $disposal->last_position_code ? " position {$disposal->last_position_code}" : '';

        return match ($disposal->last_location_type?->value) {
            'power_vehicle', 'trailer' => "Fleet vehicle{$position}",
            'store' => 'Store',
            default => 'Not recorded',
        };
    }
}
