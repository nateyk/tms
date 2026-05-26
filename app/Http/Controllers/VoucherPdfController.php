<?php

namespace App\Http\Controllers;

use App\Models\Tyre;
use App\Models\TyreDisposal;
use App\Models\TyreMaintenance;
use App\Models\TyreMovement;
use App\Models\TrailerTransfer;
use App\Models\Vehicle;
use App\Services\PdfVoucherService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class VoucherPdfController extends Controller
{
    public function __construct(protected PdfVoucherService $pdf) {}

    public function movement(TyreMovement $movement): Response
    {
        return $this->pdf->movement($movement);
    }

    public function trailerTransfer(TrailerTransfer $transfer): Response
    {
        return $this->pdf->trailerTransfer($transfer);
    }

    public function maintenance(TyreMaintenance $maintenance): Response
    {
        return $this->pdf->maintenance($maintenance);
    }

    public function disposal(TyreDisposal $disposal): Response
    {
        return $this->pdf->disposal($disposal);
    }

    public function tyreRegistration(Tyre $tyre): Response
    {
        return $this->pdf->tyreRegistration($tyre);
    }

    public function tyreHistory(Tyre $tyre): Response
    {
        return $this->pdf->tyreHistory($tyre);
    }

    public function vehicleTyreStatus(Vehicle $vehicle): Response
    {
        return $this->pdf->vehicleTyreStatus($vehicle);
    }
}
