<?php

namespace App\Http\Controllers;

use App\Models\Tyre;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\TrailerTransfer;
use App\Models\Vehicle;
use App\Services\PdfVoucherService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class VoucherPdfController extends Controller
{
    public function __construct(protected PdfVoucherService $pdf) {}

    public function movement(TyreMovement $movement): Response
    {
        Gate::authorize('view', $movement);

        return $this->pdf->movement($movement);
    }

    public function trailerTransfer(TrailerTransfer $transfer): Response
    {
        abort_unless(auth()->user()?->canAny(['trailer.assign', 'trailer.transfer', 'report.view']), 403);

        return $this->pdf->trailerTransfer($transfer);
    }

    public function disposal(TyreDisposal $disposal): Response
    {
        abort_unless(auth()->user()?->canAny(['disposal.create', 'disposal.check', 'disposal.approve', 'report.view']), 403);

        return $this->pdf->disposal($disposal);
    }

    public function tyreRegistration(Tyre $tyre): Response
    {
        Gate::authorize('view', $tyre);

        return $this->pdf->tyreRegistration($tyre);
    }

    public function tyreHistory(Tyre $tyre): Response
    {
        Gate::authorize('view', $tyre);

        return $this->pdf->tyreHistory($tyre);
    }

    public function vehicleTyreStatus(Vehicle $vehicle): Response
    {
        Gate::authorize('view', $vehicle);

        return $this->pdf->vehicleTyreStatus($vehicle);
    }
}
