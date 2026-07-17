<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\AuditLogsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Fleet\StoreController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\Fleet\VehicleOdometerController;
use App\Http\Controllers\Fleet\VehicleTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TyreScanController;
use App\Http\Controllers\Tyres\TyreBaselineController;
use App\Http\Controllers\Tyres\TyreConditionAuditController;
use App\Http\Controllers\Tyres\TyreController;
use App\Http\Controllers\Tyres\TyreMovementController;
use App\Http\Controllers\Tyres\TyreReadingMonitoringController;
use App\Http\Controllers\VoucherPdfController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/tyres/scan/{tyre_code}', [TyreScanController::class, 'show'])
    ->name('tyres.scan');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('fleet')->name('fleet.')->group(function () {
        Route::resource('vehicle-types', VehicleTypeController::class)->except(['show']);
        Route::resource('stores', StoreController::class)->except(['show']);
        Route::resource('vehicles', VehicleController::class);

        Route::get('/vehicles/{vehicle}/odometer', [VehicleOdometerController::class, 'edit'])
            ->name('vehicles.odometer');
        Route::put('/vehicles/{vehicle}/odometer', [VehicleOdometerController::class, 'update'])
            ->name('vehicles.odometer.update');

        Route::get('/trailer-transfers', fn () => Inertia::render('modules/placeholder', [
            'title' => 'Trailer Transfers',
            'description' => 'Create and approve trailer transfer vouchers.',
        ]))->name('trailer-transfers.index');
    });

    Route::prefix('tyres')->name('tyres.')->group(function () {
        Route::get('/', [TyreController::class, 'index'])->name('index');
        Route::get('/create', [TyreController::class, 'create'])->name('create');
        Route::post('/', [TyreController::class, 'store'])->name('store');

        Route::get('/reading-monitoring', [TyreReadingMonitoringController::class, 'index'])
            ->name('reading-monitoring.index');
        Route::get('/reading-monitoring/{vehicle}', [TyreReadingMonitoringController::class, 'show'])
            ->name('reading-monitoring.show');

        Route::get('/baselines', [TyreBaselineController::class, 'index'])->name('baselines.index');
        Route::get('/baselines/create', [TyreBaselineController::class, 'create'])->name('baselines.create');
        Route::post('/baselines', [TyreBaselineController::class, 'store'])->name('baselines.store');
        Route::get('/baselines/{baseline}', [TyreBaselineController::class, 'show'])->name('baselines.show');
        Route::get('/baselines/{baseline}/edit', [TyreBaselineController::class, 'edit'])->name('baselines.edit');
        Route::put('/baselines/{baseline}', [TyreBaselineController::class, 'update'])->name('baselines.update');
        Route::delete('/baselines/{baseline}', [TyreBaselineController::class, 'destroy'])->name('baselines.destroy');

        Route::get('/movements', [TyreMovementController::class, 'index'])->name('movements.index');
        Route::get('/movements/create', [TyreMovementController::class, 'create'])->name('movements.create');
        Route::get('/movements/form-options', [TyreMovementController::class, 'options'])
            ->name('movements.form-options');
        Route::post('/movements', [TyreMovementController::class, 'store'])->name('movements.store');
        Route::get('/movements/position-options/{vehicle}', [TyreMovementController::class, 'positionOptions'])
            ->name('movements.position-options');

        Route::get('/movements/{movement}', [TyreMovementController::class, 'show'])->name('movements.show');
        Route::get('/movements/{movement}/edit', [TyreMovementController::class, 'edit'])->name('movements.edit');
        Route::put('/movements/{movement}', [TyreMovementController::class, 'update'])->name('movements.update');
        Route::delete('/movements/{movement}', [TyreMovementController::class, 'destroy'])->name('movements.destroy');
        Route::post('/movements/{movement}/submit', [TyreMovementController::class, 'submit'])->name('movements.submit');
        Route::post('/movements/{movement}/check', [TyreMovementController::class, 'check'])->name('movements.check');
        Route::post('/movements/{movement}/approve', [TyreMovementController::class, 'approve'])->name('movements.approve');
        Route::post('/movements/{movement}/reject', [TyreMovementController::class, 'reject'])->name('movements.reject');
        Route::post('/movements/{movement}/complete', [TyreMovementController::class, 'complete'])->name('movements.complete');
        Route::post('/movements/{movement}/cancel', [TyreMovementController::class, 'cancel'])->name('movements.cancel');

        Route::get('/disposals', fn () => Inertia::render('modules/placeholder', [
            'title' => 'Tyre Disposals',
            'description' => 'Coming in next phase.',
        ]))->name('disposals.index');

        Route::get('/{tyre}/condition-audits/create', [TyreConditionAuditController::class, 'create'])
            ->name('condition-audits.create');
        Route::post('/{tyre}/condition-audits', [TyreConditionAuditController::class, 'store'])
            ->name('condition-audits.store');

        Route::get('/{tyre}', [TyreController::class, 'show'])->name('show');
        Route::get('/{tyre}/edit', [TyreController::class, 'edit'])->name('edit');
        Route::put('/{tyre}', [TyreController::class, 'update'])->name('update');
        Route::delete('/{tyre}', [TyreController::class, 'destroy'])->name('destroy');
        Route::post('/{tyre}/approve', [TyreController::class, 'approve'])->name('approve');
        Route::post('/{tyre}/regenerate-qr', [TyreController::class, 'regenerateQr'])->name('regenerate-qr');
    });

    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/pending', [ApprovalsController::class, 'pending'])->name('pending');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])
            ->middleware('permission:report.view')
            ->name('index');
    });

    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogsController::class, 'index'])
            ->middleware('permission:audit.view')
            ->name('index');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('/roles', [RoleController::class, 'index'])
            ->name('roles.index');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->middleware('permission:settings.manage')
            ->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('settings.update');
    });

    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/movement/{movement}', [VoucherPdfController::class, 'movement'])
            ->name('movement.pdf');
        Route::get('/trailer-transfer/{transfer}', [VoucherPdfController::class, 'trailerTransfer'])
            ->name('trailer-transfer.pdf');
        Route::get('/tyre/{tyre}/registration', [VoucherPdfController::class, 'tyreRegistration'])
            ->name('tyre.registration.pdf');
        Route::get('/tyre/{tyre}/history', [VoucherPdfController::class, 'tyreHistory'])
            ->name('tyre.history.pdf');
        Route::get('/vehicle/{vehicle}/tyre-status', [VoucherPdfController::class, 'vehicleTyreStatus'])
            ->name('vehicle.tyre-status.pdf');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
