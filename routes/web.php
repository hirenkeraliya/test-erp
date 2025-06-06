<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:' . config('app.web_threshold_rate_limit_per_minute') . ',1'])->group(
    function (): void {
        if (! config('app.prevent_backend_access')) {
            Route::group([], base_path('routes/web_super_admin.php'));
            Route::group([], base_path('routes/web_admin.php'));
            Route::group([], base_path('routes/web_store_manager.php'));
            Route::group([], base_path('routes/web_warehouse_manager.php'));
        }

        Route::group([], base_path('routes/web_front.php'));
    }
);
