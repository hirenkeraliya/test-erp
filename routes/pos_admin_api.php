<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PosAdmin\CompanyController;
use App\Http\Controllers\Api\PosAdmin\CounterController;
use App\Http\Controllers\Api\PosAdmin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos-admin')->group(function (): void {
    Route::controller(CompanyController::class)->group(function (): void {
        Route::post('get-company-by-uuid', 'getCompanyByUuid');
    });
    Route::controller(CounterController::class)->group(function (): void {
        Route::post('update-counter-by-name', 'updateCounterByName');
    });
    Route::controller(DashboardController::class)->group(function (): void {
        Route::post('get-company-daily-totals', 'getCompanyDailyTotals');
    });
});
