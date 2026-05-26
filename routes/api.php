<?php

use App\Http\Controllers\Api\TyreLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/tyres/{tyre_code}', [TyreLookupController::class, 'show'])
        ->name('api.tyres.show');
});
