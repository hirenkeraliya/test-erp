<?php

declare(strict_types=1);

use App\Http\Controllers\Api\User\DashboardController;
use App\Http\Controllers\Api\User\TokenController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->name('user.')->group(function (): void {
    Route::post('get-token', [TokenController::class, 'issueToken']);
    Route::controller(TokenController::class)->group(function (): void {
        Route::post('get-token', 'issueToken');
        Route::post('forgot-password', 'forgotPassword');
    });

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::get('logout', [TokenController::class, 'logout']);

        Route::controller(UserController::class)->group(function (): void {
            Route::post('profile-update', 'updateProfile');
            Route::get('profile-details', 'getProfileDetails');
            Route::post('update-password', 'updatePassword');
            Route::get('list-permission', 'listPermission');
        });

        Route::controller(DashboardController::class)->group(function (): void {
            Route::get('dashboard', 'index');
            Route::get('get-operational-atv-chart-data', 'getOperationalAtvChartData');
            Route::get('get-operational-upt-chart-data', 'getOperationalUptChartData');
            Route::get('get-operational-revenue-chart-data', 'getOperationalRevenueChartData');
            Route::get('get-operational-sales-count', 'getOperationalSalesCount');
            Route::get('get-operational-today-sales', 'getOperationalTodaySales');
            Route::get('get-operational-this-month-sales', 'getOperationalThisMonthSales');
            Route::get('get-operational-this-year-sales', 'getOperationalThisYearSales');
            Route::get('get-operational-top-promoters', 'getOperationalTopPromoters');
            Route::get('get-operational-this-year-top-promoters', 'getOperationalThisYearTopPromoters');
            Route::get('sale-target', 'saleTarget');
            Route::get('sale-target-by-time-interval', 'saleTargetByTimeInterval');
            Route::get('get-sale-target-time-interval-type', 'getSaleTargetTimeIntervalType');
            Route::get('seasonal', 'seasonal');
            Route::get('get-seasonal-data', 'getSeasonalData');
            Route::get('get-seasonal-chart-data', 'getSeasonalChartData');
            Route::get('get-seasonal-total-discounts', 'getSeasonalTotalDiscounts');
            Route::get('get-seasonal-comparison-data', 'getSeasonalComparisonData');
            Route::get('get-seasonal-member-comparison-data', 'getSeasonalMemberComparisonData');
            Route::get('get-seasonal-sales-comparison-data', 'getSeasonalSalesComparisonData');
            Route::get('get-seasonal-sales-comparison-chart-data', 'getSeasonalSalesComparisonChartData');
            Route::get('revenue-view', 'revenueView');
            Route::get('store-revenue-view', 'storeRevenueView');
            Route::get('business-view', 'businessView');
            Route::get('get-business-view-data', 'getBusinessViewData');
            Route::get('get-style-chart-data', 'getStyleChartData');
            Route::get('stock-overview', 'stockOverview');
            Route::get('get-no-stock-overview', 'getNoStockOverview');
            Route::get('get-low-stock-overview', 'getLowStockOverview');
            Route::get('get-negative-stock-overview', 'getNegativeStockOverview');

            Route::get('get-transfer-order/{locationId}', 'getTransferOrder');
            Route::get('get-purchase-request/{locationId}', 'getPurchaseRequest');
            Route::get('get-transfer-request/{locationId}', 'getTransferRequest');
            Route::get('get-sales-order/{locationId}', 'getSalesOrder');
            Route::get('get-purchase-order/{locationId}', 'getPurchaseOrder');
            Route::get('get-request-order/{locationId}', 'getRequestOrder');
            Route::get('get-this-month-top-selling-products', 'getThisMonthTopSellingProducts');
            Route::get('get-this-year-top-selling-products', 'getThisYearTopSellingProducts');
            Route::get('get-this-month-worst-selling-products', 'getThisMonthWorstSellingProducts');
            Route::get('get-this-year-worst-selling-products', 'getThisYearWorstSellingProducts');
            Route::get('get-this-month-top-selling-colors', 'getThisMonthTopSellingColors');
            Route::get('get-this-year-top-selling-colors', 'getThisYearTopSellingColors');
            Route::get('get-top-ranking-products/{locationId}', 'getTopRankingProducts');
        });
    });
});
