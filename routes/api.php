<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:' . config('app.api_threshold_rate_limit_per_minute') . ',1'])->group(
    function (): void {
        Route::group([], base_path('routes/common_api.php'));
        Route::group([], base_path('routes/pos_api.php'));
        Route::group([], base_path('routes/member_api.php'));
        Route::group([], base_path('routes/promoter_api.php'));
        Route::group([], base_path('routes/warehouse_manager_api.php'));
        Route::group([], base_path('routes/sales_channel_api.php'));
        Route::group([], base_path('routes/store_manager_api.php'));
        Route::group([], base_path('routes/user_api.php'));
        Route::group([], base_path('routes/external_connection_api.php'));
        Route::group([], base_path('routes/courier_api.php'));
        Route::group([], base_path('routes/pos_admin_api.php'));
        Route::group([], base_path('routes/integration_api.php'));
    }
);
