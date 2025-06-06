<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Integration\AttributeController;
use App\Http\Controllers\Api\Integration\BrandController;
use App\Http\Controllers\Api\Integration\CompanyController;
use App\Http\Controllers\Api\Integration\InventoryController;
use App\Http\Controllers\Api\Integration\LocationController;
use App\Http\Controllers\Api\Integration\MasterProductController;
use App\Http\Controllers\Api\Integration\ProductController;
use App\Http\Controllers\Api\Integration\RegionController;
use App\Http\Controllers\Api\Integration\SaleController;
use App\Http\Controllers\Api\Integration\SeasonController;
use App\Http\Controllers\Api\Integration\StyleController;
use App\Http\Controllers\Api\Integration\TemplateController;
use App\Http\Controllers\Api\Integration\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('integration')->middleware(['auth:sanctum', 'checkIntegrationStatus'])->group(function (): void {
    Route::controller(ProductController::class)->group(function (): void {
        Route::post('add-products', 'store');
        Route::get('get-product-categories', 'getCategories');
        Route::get('get-product-brands', 'getBrands');
        Route::get('get-all-product-variants', 'getAllProducts');
        Route::get('get-all-product-variants-count', 'getAllProductVariantsCount');
    });

    Route::controller(CompanyController::class)->group(function (): void {
        Route::get('get-all-companies', 'getAllCompanies');
    });

    Route::controller(TemplateController::class)->group(function (): void {
        Route::get('get-all-templates', 'getAllTemplates');
    });

    Route::controller(AttributeController::class)->group(function (): void {
        Route::get('get-all-attributes', 'getAllAttributes');
    });

    Route::controller(BrandController::class)->group(function (): void {
        Route::get('get-all-brands', 'getAllBrands');
    });

    Route::controller(RegionController::class)->group(function (): void {
        Route::get('get-all-regions', 'getAllRegions');
    });

    Route::controller(StyleController::class)->group(function (): void {
        Route::get('get-all-styles', 'getAllStyles');
    });

    Route::controller(VendorController::class)->group(function (): void {
        Route::get('get-all-vendors', 'getAllVendors');
    });

    Route::controller(SeasonController::class)->group(function (): void {
        Route::get('get-all-seasons', 'getAllSeasons');
    });

    Route::controller(LocationController::class)->group(function (): void {
        Route::get('get-all-store-locations', 'getAllStoreLocations');
    });

    Route::controller(MasterProductController::class)->group(function (): void {
        Route::get('get-all-products', 'getAllMasterProducts');
        Route::get('get-all-products-count', 'getAllProductsCount');
    });

    Route::controller(InventoryController::class)->group(function (): void {
        Route::get('get-products-closing-stocks-per-day', 'getProductsClosingStocksPerDay');
        Route::get('get-products-current-stock', 'getProductsCurrentStock');
    });

    Route::controller(SaleController::class)->group(function (): void {
        Route::get('get-all-aggregated-sales', 'getAllAggregatedSales');
        Route::get('get-aggregated-regular-sales-for-specified-date', 'getAggregatedRegularSalesForSpecifiedDate');
        Route::get(
            'get-complete-layaway-and-credit-aggregated-sales-for-specified-date',
            'getCompleteLayawayAndCreditAggregatedSalesForSpecifiedDate'
        );
    });
});
