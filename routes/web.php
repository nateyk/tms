<?php

use App\Http\Controllers\TyreScanController;
use App\Http\Controllers\VoucherPdfController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/tyres/scan/{tyre_code}', [TyreScanController::class, 'show'])
    ->name('tyres.scan');

Route::middleware(['auth'])->prefix('vouchers')->name('vouchers.')->group(function () {
    Route::get('/movement/{movement}', [VoucherPdfController::class, 'movement'])
        ->name('movement.pdf');
    Route::get('/trailer-transfer/{transfer}', [VoucherPdfController::class, 'trailerTransfer'])
        ->name('trailer-transfer.pdf');
    Route::get('/maintenance/{maintenance}', [VoucherPdfController::class, 'maintenance'])
        ->name('maintenance.pdf');
    Route::get('/disposal/{disposal}', [VoucherPdfController::class, 'disposal'])
        ->name('disposal.pdf');
    Route::get('/tyre/{tyre}/registration', [VoucherPdfController::class, 'tyreRegistration'])
        ->name('tyre.registration.pdf');
    Route::get('/tyre/{tyre}/history', [VoucherPdfController::class, 'tyreHistory'])
        ->name('tyre.history.pdf');
    Route::get('/vehicle/{vehicle}/tyre-status', [VoucherPdfController::class, 'vehicleTyreStatus'])
        ->name('vehicle.tyre-status.pdf');
});
