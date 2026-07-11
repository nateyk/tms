<?php

namespace App\Http\Controllers\Tyres;

use App\Enums\DisposalReason;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\RejectVoucherRequest;
use App\Http\Requests\Tyres\StoreTyreDisposalRequest;
use App\Http\Requests\Tyres\UpdateTyreDisposalRequest;
use App\Models\Tyre;
use App\Models\TyreDisposal;
use App\Services\ApprovalService;
use App\Services\TyreDisposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TyreDisposalController extends Controller
{
    public function __construct(
        private readonly TyreDisposalService $disposalService,
        private readonly ApprovalService $approvalService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TyreDisposal::class);

        $disposals = TyreDisposal::query()
            ->with(['tyre:id,tyre_code', 'preparedByUser:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TyreDisposal $disposal) => $this->serializeListRow($disposal));

        return Inertia::render('tyres/disposals/index', [
            'disposals' => $disposals,
            'filters' => ['status' => $request->query('status')],
            'statusOptions' => collect(VoucherStatus::cases())->map(fn (VoucherStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TyreDisposal::class);

        return Inertia::render('tyres/disposals/create', $this->formOptions());
    }

    public function store(StoreTyreDisposalRequest $request): RedirectResponse
    {
        try {
            $disposal = $this->disposalService->createDraft(
                $request->validated(),
                (int) auth()->id(),
            );
        } catch (TyreBusinessException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.disposals.show', $disposal)
            ->with('success', 'Disposal voucher saved as draft.');
    }

    public function show(TyreDisposal $disposal): Response
    {
        $this->authorize('view', $disposal);

        $disposal->load(['tyre', 'preparedByUser']);

        return Inertia::render('tyres/disposals/show', [
            'disposal' => $this->serializeDetail($disposal),
            'can' => $this->serializePermissions($disposal),
        ]);
    }

    public function edit(TyreDisposal $disposal): Response
    {
        $this->authorize('update', $disposal);

        return Inertia::render('tyres/disposals/edit', [
            ...$this->formOptions(),
            'disposal' => $this->serializeForm($disposal),
        ]);
    }

    public function update(UpdateTyreDisposalRequest $request, TyreDisposal $disposal): RedirectResponse
    {
        $disposal->update($request->validated());

        return redirect()
            ->route('tyres.disposals.show', $disposal)
            ->with('success', 'Disposal voucher updated.');
    }

    public function destroy(TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('delete', $disposal);

        $disposal->delete();

        return redirect()
            ->route('tyres.disposals.index')
            ->with('success', 'Draft disposal deleted.');
    }

    public function submit(TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('submit', $disposal);

        try {
            $this->approvalService->submit($disposal);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Disposal submitted for checking.');
    }

    public function check(TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('check', $disposal);

        try {
            $this->approvalService->check($disposal);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Disposal checked.');
    }

    public function approve(TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('approve', $disposal);

        try {
            $this->approvalService->approve($disposal);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Disposal approved.');
    }

    public function reject(RejectVoucherRequest $request, TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('reject', $disposal);

        try {
            $this->approvalService->reject($disposal, $request->validated('reason'));
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.disposals.show', $disposal)
            ->with('success', 'Disposal rejected.');
    }

    public function complete(TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('complete', $disposal);

        try {
            $this->approvalService->completeDisposal($disposal);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.disposals.show', $disposal)
            ->with('success', 'Disposal completed. Tyre marked as disposed.');
    }

    public function cancel(TyreDisposal $disposal): RedirectResponse
    {
        $this->authorize('cancel', $disposal);

        try {
            $this->approvalService->cancel($disposal);
        } catch (TyreBusinessException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tyres.disposals.index')
            ->with('success', 'Disposal cancelled.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'tyres' => Tyre::query()
                ->whereIn('status', [TyreStatus::Available, TyreStatus::Active, TyreStatus::Maintenance])
                ->orderBy('tyre_code')
                ->get(['id', 'tyre_code', 'status'])
                ->map(fn (Tyre $tyre) => [
                    'id' => $tyre->id,
                    'tyre_code' => $tyre->tyre_code,
                    'status_label' => $tyre->status->label(),
                ]),
            'disposalReasons' => collect(DisposalReason::cases())->map(fn (DisposalReason $reason) => [
                'value' => $reason->value,
                'label' => $reason->label(),
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeListRow(TyreDisposal $disposal): array
    {
        return [
            'id' => $disposal->id,
            'display_number' => $disposal->displayNumber(),
            'tyre_code' => $disposal->tyre?->tyre_code,
            'disposal_reason' => $disposal->disposal_reason->label(),
            'status' => $disposal->status->value,
            'status_label' => $disposal->status->label(),
            'prepared_by' => $disposal->preparedByUser?->name,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeForm(TyreDisposal $disposal): array
    {
        return [
            'id' => $disposal->id,
            'tyre_id' => $disposal->tyre_id,
            'disposal_reason' => $disposal->disposal_reason->value,
            'final_km_used' => $disposal->final_km_used,
            'final_condition' => $disposal->final_condition ?? '',
            'estimated_scrap_value' => $disposal->estimated_scrap_value !== null ? (float) $disposal->estimated_scrap_value : null,
            'sold_amount' => $disposal->sold_amount !== null ? (float) $disposal->sold_amount : null,
            'notes' => $disposal->notes ?? '',
        ];
    }

    /** @return array<string, mixed> */
    private function serializeDetail(TyreDisposal $disposal): array
    {
        return [
            ...$this->serializeForm($disposal),
            'disposal_no' => $disposal->disposal_no,
            'display_number' => $disposal->displayNumber(),
            'status' => $disposal->status->value,
            'status_label' => $disposal->status->label(),
            'tyre_code' => $disposal->tyre?->tyre_code,
            'disposal_reason_label' => $disposal->disposal_reason->label(),
            'last_position_display' => $disposal->lastPositionDisplay(),
            'prepared_by' => $disposal->preparedByUser?->name,
            'completed_at' => $disposal->completed_at?->toDateTimeString(),
            'pdf_url' => route('vouchers.disposal.pdf', $disposal),
        ];
    }

    /** @return array<string, bool> */
    private function serializePermissions(TyreDisposal $disposal): array
    {
        $user = request()->user();

        return [
            'update' => $user?->can('update', $disposal) ?? false,
            'delete' => $user?->can('delete', $disposal) ?? false,
            'submit' => $user?->can('submit', $disposal) ?? false,
            'check' => $user?->can('check', $disposal) ?? false,
            'approve' => $user?->can('approve', $disposal) ?? false,
            'reject' => $user?->can('reject', $disposal) ?? false,
            'complete' => $user?->can('complete', $disposal) ?? false,
            'cancel' => $user?->can('cancel', $disposal) ?? false,
        ];
    }
}
