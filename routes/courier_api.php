<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Courier\NinjaVanCourierController;
use Illuminate\Support\Facades\Route;

Route::prefix('courier')->name('courier.')->group(function (): void {
    Route::controller(NinjaVanCourierController::class)->group(function (): void {
        Route::get('update-status', 'updateStatus');
    });
});
