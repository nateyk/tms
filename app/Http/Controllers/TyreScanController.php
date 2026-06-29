<?php

namespace App\Http\Controllers;

use App\Models\Tyre;
use Illuminate\View\View;

class TyreScanController extends Controller
{
    public function show(string $tyreCode): View
    {
        $tyre = Tyre::query()
            ->where('tyre_code', $tyreCode)
            ->with([
                'brand',
                'size',
                'movements' => fn ($q) => $q->latest()->limit(10),
                'maintenanceRecords' => fn ($q) => $q->latest()->limit(10),
                'assignments' => fn ($q) => $q->latest()->limit(10),
            ])
            ->firstOrFail();

        return view('tyres.scan', compact('tyre'));
    }
}
