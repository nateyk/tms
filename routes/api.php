<?php

use App\Http\Controllers\Api\TyreLookupController;
use App\Http\Controllers\Tyres\TyreReadingMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/tyres/{tyre_code}', [TyreLookupController::class, 'show'])
        ->name('api.tyres.show');

    Route::get('/vehicles/{vehicle}/reading-monitoring-map', [TyreReadingMonitoringController::class, 'getVehicleTyreMapData'])
        ->name('api.vehicles.reading-monitoring-map');

    Route::get('/trailers/{trailer}/reading-monitoring-map', [TyreReadingMonitoringController::class, 'getTrailerTyreMapData'])
        ->name('api.trailers.reading-monitoring-map');
});
