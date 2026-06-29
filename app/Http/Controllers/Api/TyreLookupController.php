<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TyreScanResource;
use App\Models\Tyre;
use Illuminate\Http\JsonResponse;

class TyreLookupController extends Controller
{
    public function show(string $tyreCode): JsonResponse
    {
        $tyre = Tyre::query()
            ->where('tyre_code', $tyreCode)
            ->with(['brand', 'size', 'activeAssignment'])
            ->firstOrFail();

        $this->authorize('view', $tyre);

        return (new TyreScanResource($tyre))->response();
    }
}
