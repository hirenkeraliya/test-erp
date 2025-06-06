<?php

declare(strict_types=1);

use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Admin\ActivityReportController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\UserResetPasswordController;
use App\Http\Controllers\Admin\AutomatedNotificationController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BarCodeController;
use App\Http\Controllers\Admin\BatchExpiryController;
use App\Http\Controllers\Admin\BookingPaymentReportController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CancelLayawaySaleController;
use App\Http\Controllers\Admin\CashbackController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\CashierGroupController;
use App\Http\Controllers\Admin\CashMovementReasonController;
use App\Http\Controllers\Admin\CashMovementReportController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChangePasswordController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ClosedCounterReportController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\ColorGroupController;
use App\Http\Controllers\Admin\ComplimentaryItemReasonController;
use App\Http\Controllers\Admin\ConsignmentReportController;
use App\Http\Controllers\Admin\CounterController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Http\Controllers\Admin\CreditSaleController;
use App\Http\Controllers\Admin\CustomFieldValueController;
use App\Http\Controllers\Admin\CustomReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DayCloseReportController;
use App\Http\Controllers\Admin\DenominationController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\DifferentStoreReturnsController;
use App\Http\Controllers\Admin\DigitalInvoiceController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\Admin\DraftProductController;
use App\Http\Controllers\Admin\DreamPriceController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\DynamicMenuController;
use App\Http\Controllers\Admin\EmailRecipientController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeGroupController;
use App\Http\Controllers\Admin\EmployeeSalesReportController;
use App\Http\Controllers\Admin\ExportRecordController;
use App\Http\Controllers\Admin\ExternalInventoryReportController;
use App\Http\Controllers\Admin\ExternalLocationController;
use App\Http\Controllers\Admin\ExternalLoginController;
use App\Http\Controllers\Admin\ExternalProductController;
use App\Http\Controllers\Admin\ExternalPurchaseOrderController;
use App\Http\Controllers\Admin\ExternalPurchaseOrderReceiveController;
use App\Http\Controllers\Admin\GenuineProductVerificationReportController;
use App\Http\Controllers\Admin\GenuineReceiptVerificationReportController;
use App\Http\Controllers\Admin\GiftCardController;
use App\Http\Controllers\Admin\GoodsReceivedNoteController;
use App\Http\Controllers\Admin\HappyHourDiscountController;
use App\Http\Controllers\Admin\ImportRecordController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryReportController;
use App\Http\Controllers\Admin\LayawaySaleController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\LoyaltyCampaignConfigurationController;
use App\Http\Controllers\Admin\LoyaltyCampaignController;
use App\Http\Controllers\Admin\ManualNotificationController;
use App\Http\Controllers\Admin\MasterProductController;
use App\Http\Controllers\Admin\MasterProductFilterController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberGroupController;
use App\Http\Controllers\Admin\MemberReportController;
use App\Http\Controllers\Admin\MemberSalesReportController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\MysteryGiftController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OnlineProductsReportController;
use App\Http\Controllers\Admin\OnlineSalesChargesController;
use App\Http\Controllers\Admin\OpenCounterController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderPickingListController;
use App\Http\Controllers\Admin\OrderReturnController;
use App\Http\Controllers\Admin\PackageTypeController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\PaymentTypeReportController;
use App\Http\Controllers\Admin\PosAdminController;
use App\Http\Controllers\Admin\PosAdvertisementController;
use App\Http\Controllers\Admin\ProductAgeingReportController;
use App\Http\Controllers\Admin\ProductCollectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductFilterController;
use App\Http\Controllers\Admin\ProductSerialNumberReportController;
use App\Http\Controllers\Admin\ProductsReportController;
use App\Http\Controllers\Admin\ProfitAndLossReportController;
use App\Http\Controllers\Admin\PromoterCommissionController;
use App\Http\Controllers\Admin\PromoterController;
use App\Http\Controllers\Admin\PromoterGroupController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseOrderFulfillmentController;
use App\Http\Controllers\Admin\PurchaseOrderInvoiceController;
use App\Http\Controllers\Admin\PurchasePlanController;
use App\Http\Controllers\Admin\QuantitySoldReportController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\ReservedInventoryReportController;
use App\Http\Controllers\Admin\RewardController;
use App\Http\Controllers\Admin\SaleAnalysisByGradeReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SaleExchangesReportController;
use App\Http\Controllers\Admin\SaleReturnController;
use App\Http\Controllers\Admin\SaleReturnReasonController;
use App\Http\Controllers\Admin\SalesByPromoterController;
use App\Http\Controllers\Admin\SaleSeasonsController;
use App\Http\Controllers\Admin\SaleTargetController;
use App\Http\Controllers\Admin\SaleTargetReportController;
use App\Http\Controllers\Admin\SaleThroughRatioController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\SellThroughAggregateReportController;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\SizeGroupController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockMovementLedgerReportController;
use App\Http\Controllers\Admin\StockMovementSummaryReportController;
use App\Http\Controllers\Admin\StockPositionController;
use App\Http\Controllers\Admin\StockTakeController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\StockTransferReasonController;
use App\Http\Controllers\Admin\StoreManagerController;
use App\Http\Controllers\Admin\StoreManagerRoleController;
use App\Http\Controllers\Admin\StyleController;
use App\Http\Controllers\Admin\SubPaymentTypeController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TemplateAttributeController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\TransitInventoryReportController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\UnitOfMeasureController;
use App\Http\Controllers\Admin\UnitOfMeasureDerivativeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VoidSaleController;
use App\Http\Controllers\Admin\VoidSaleReasonController;
use App\Http\Controllers\Admin\VoucherConfigurationController;
use App\Http\Controllers\Admin\VoucherReportController;
use App\Http\Controllers\Admin\WarehouseManagerController;
use App\Http\Controllers\Admin\WarehouseManagerRoleController;
use App\Http\Middleware\RedirectIfAdminCompanyNotSelected;
use App\Services\RetailPlanningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::inertia('menu/{pageUrl}', 'menu/Index')->name('menu_page');
    Route::get(
        'logging',
        fn (Request $request): RedirectResponse => (new ExternalLoginController())->logging($request)
    )->name('logging');

    Route::controller(UserResetPasswordController::class)->group(function (): void {
        Route::get('user-reset-password/{token}', 'index')->name('user_reset_password');
        Route::post('user-reset-password', 'resetPassword')->name('user_password_update');
        Route::get('user-password-changed', 'passwordChanged')->name('user_password_changed');
    });

    Route::group([
        'middleware' => 'guest',
    ], function (): void {
        Route::controller(LoginController::class)->group(function (): void {
            Route::get('', 'showLoginPage')->name('login');
            Route::post('login', 'login')->name('login_user');
        });
        Route::inertia('forgot-password', 'guest/ForgotPassword')->name('forgot_password');
        Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->name(
            'send_forgot_password_email'
        );
        Route::controller(ResetPasswordController::class)->group(function (): void {
            Route::get('reset-password/{token}', 'index')->name('reset_password');
            Route::post('reset-password', 'resetPassword')->name('password_update');
        });
    });

    Route::prefix('2fa')->name('2fa.')->group(function (): void {
        Route::get('show-validation-page', [TwoFactorController::class, 'showValidationPage'])->name(
            'show_validation_page'
        );
        Route::post('validate', [TwoFactorController::class, 'validateOtp'])->name('validateOTP');

        Route::post('verify2fa/{id}', [TwoFactorController::class, 'confirm2FA'])->name('verify2fa');
    });

    Route::middleware(['auth:admin', 'twoFactor', RedirectIfAdminCompanyNotSelected::class])->group(function (): void {
        $retailPlanningService = resolve(RetailPlanningService::class);
        Route::controller(DashboardController::class)->group(function (): void {
            Route::get('dashboard', 'index')->name('dashboard');
            Route::middleware(
                'permission:dashboard_' . PermissionList::DASHBOARD_OPERATIONAL->value
            )->group(function (): void {
                Route::get('get-operational-sales-count', 'getOperationalSalesCount')
                    ->name('get_operational_sales_count');
                Route::get('get-operational-today-sales', 'getOperationalTodaySales')
                    ->name('get_operational_today_sales');
                Route::get('get-operational-this-month-sales', 'getOperationalThisMonthSales')
                    ->name('get_operational_this_month_sales');
                Route::get('get-operational-this-year-sales', 'getOperationalThisYearSales')
                    ->name('get_operational_this_year_sales');
                Route::get('get-operational-revenue-chart-data', 'getOperationalRevenueChartData')
                    ->name('get_operational_revenue_chart_data');
                Route::get('get-operational-atv-chart-data', 'getOperationalAtvChartData')
                    ->name('get_operational_atv_chart_data');
                Route::get('get-operational-upt-chart-data', 'getOperationalUptChartData')
                    ->name('get_operational_upt_chart_data');
                Route::get('get-operational-top-promoters', 'getOperationalTopPromoters')
                    ->name('get_operational_top_promoters');
                Route::get('get-operational-this-year-top-promoters', 'getOperationalThisYearTopPromoters')
                    ->name('get_operational_this_year_top_promoters');
            });
            Route::middleware(
                'permission:dashboard_' . PermissionList::DASHBOARD_STORE_REVENUE->value
            )->group(function (): void {
                Route::get('revenue-view', 'revenueView')
                    ->name('revenue_view');
                Route::get('print-revenue-view-stores-sales', 'printRevenueViewStoresSales')
                    ->name('print_revenue_view_stores_sales');
                Route::get('export-revenue-stores-sales/{fileName}', 'exportRevenueStoresSales')
                    ->name('export_revenue_stores_sales');
                Route::get('store-revenue-view', 'storeRevenueView')
                    ->name('store_revenue');
                Route::get('print-store-revenue', 'printStoreRevenue')
                    ->name('print_store_revenue');
                Route::get('export-store-revenue/{fileName}', 'exportStoreRevenue')
                    ->name('export_store_revenue');
            });
            Route::middleware('permission:dashboard_' . PermissionList::DASHBOARD_BUSINESS->value)->group(
                function (): void {
                    Route::get('business-view', 'businessView')
                        ->name('business_view');
                    Route::get('get-business-view-data', 'getBusinessViewData')
                        ->name('get_business_view_data');
                    Route::get('get-style-chart-data', 'getStyleChartData')
                        ->name('get_style_chart_data');
                    Route::get('get-live-top-ten-stores/{brandId}', 'getLiveTopTenStores')
                        ->name('get_live_top_ten_stores');
                    Route::get('get-store-sales-by-region/{regionId}/{brandId}', 'getStoreSalesByRegion')
                        ->name('get_store_sales_by_region');
                }
            );
            Route::middleware(
                'permission:dashboard_' . PermissionList::DASHBOARD_STOCK_OVERVIEW->value
            )->group(function (): void {
                Route::get('stock-overview', 'stockOverview')
                    ->name('stock_overview');
                Route::get('get-stock-overview/{storeId}', 'getStockOverview')
                    ->name('get_stock_overview');
                Route::get('get-this-month-top-selling-products', 'getThisMonthTopSellingProducts')
                    ->name('get_this_month_top_selling_products');
                Route::get('get-this-year-top-selling-products', 'getThisYearTopSellingProducts')
                    ->name('get_this_year_top_selling_products');
                Route::get('get-this-month-worst-selling-products', 'getThisMonthWorstSellingProducts')
                    ->name('get_this_month_worst_selling_products');
                Route::get('get-this-year-worst-selling-products', 'getThisYearWorstSellingProducts')
                    ->name('get_this_year_worst_selling_products');
                Route::get('get-this-month-top-selling-colors', 'getThisMonthTopSellingColors')
                    ->name('get_this_month_top_selling_colors');
                Route::get('get-this-year-top-selling-colors', 'getThisYearTopSellingColors')
                    ->name('get_this_year_top_selling_colors');
                Route::get('get-transfer-order/{storeId}', 'getTransferOrder')->name('get_transfer_order');
                Route::get('get-purchase-request/{storeId}', 'getPurchaseRequest')->name('get_purchase_request');
                Route::get('get-transfer-request/{storeId}', 'getTransferRequest')->name('get_transfer_request');
                Route::get('get-sales-order/{storeId}', 'getSalesOrder')->name('get_sales_order');
                Route::get('get-purchase-order/{storeId}', 'getPurchaseOrder')->name('get_purchase_order');
                Route::get('get-request-order/{storeId}', 'getRequestOrder')
                    ->name('get_request_order');
                Route::get('get-low-stock-overview', 'getLowStockOverview')
                    ->name('get_low_stock_overview');
                Route::get('get-no-stock-stock-overview', 'getNoStockStockOverview')
                    ->name('get_no_stock_stock_overview');
                Route::get('get-negative-stock-stock-overview', 'getNegativeStockStockOverview')
                    ->name('get_negative_stock_stock_overview');
                Route::get('get-top-ranking-products/{storeId?}', 'getTopRankingProducts')
                    ->name('get_top_ranking_products');
            });
            Route::middleware(
                'permission:dashboard_' . PermissionList::DASHBOARD_SALE_TARGET->value
            )->group(function (): void {
                Route::get('sale-target', 'saleTarget')
                    ->name('sale_target');
                Route::get('fetch-yearly-sale-target/{id}', 'fetchYearlySaleTarget')->name('fetch_yearly_sale_target');
                Route::get('fetch-monthly-sale-target/{id}', 'fetchMonthlySaleTarget')->name(
                    'fetch_monthly_sale_target'
                );
                Route::get('fetch-weekly-sale-target/{id}', 'fetchWeeklySaleTarget')->name('fetch_weekly_sale_target');
                Route::get('fetch-daily-sale-target/{id}', 'fetchDailySaleTarget')->name('fetch_daily_sale_target');
                Route::get('sale-target-details/{saleTargetId}', 'saleTargetDetails')
                    ->name('sale_target_details');
                Route::get('sale-target-weekly-sales', 'saleTargetWeeklySales')
                    ->name('sale_target_weekly_sales');
                Route::get('sale-target-daily-sales', 'saleTargetDailySales')
                    ->name('sale_target_daily_sales');
                Route::get('sale-target-get-card-data', 'saleTargetGetCardData')
                    ->name('sale_target_get_card_data');
            });
            Route::get('demand-forecasting', 'demandForecasting')
                ->name('demand_forecasting');
            Route::get('basket-analysis', 'basketAnalysis')
                ->name('basket_analysis');
            Route::get('data-analysis', 'dataAnalysis')
                ->name('data_analysis');
            Route::middleware('permission:dashboard_' . PermissionList::DASHBOARD_SEASONAL->value)->group(
                function (): void {
                    Route::get('seasonal', 'seasonal')
                        ->name('seasonal');
                    Route::get('get-seasonal-data', 'getSeasonalData')
                        ->name('get_seasonal_data');
                    Route::get('get-seasonal-chart-data', 'getSeasonalChartData')
                        ->name('get_seasonal_chart_data');
                    Route::get('get-seasonal-total-discounts', 'getSeasonalTotalDiscounts')
                        ->name('get_seasonal_total_discounts');
                    Route::get('get-seasonal-comparison-data', 'getSeasonalComparisonData')
                        ->name('get_seasonal_comparison_data');
                    Route::get('get-seasonal-member-comparison-data', 'getSeasonalMemberComparisonData')
                        ->name('get_seasonal_member_comparison_data');
                    Route::get('get-seasonal-sales-comparison-data', 'getSeasonalSalesComparisonData')
                        ->name('get_seasonal_sales_comparison_data');
                    Route::get('get-seasonal-sales-comparison-chart-data', 'getSeasonalSalesComparisonChartData')
                        ->name('get_seasonal_sales_comparison_chart_data');
                }
            );
            Route::middleware('permission:dashboard_' . PermissionList::DASHBOARD_MEMBER->value)->group(
                function (): void {
                    Route::get('member', 'memberDashboardIndex')
                        ->name('member_dashboard_index');
                    Route::get('get-member-count-details', 'getMemberCountDetails')
                        ->name('get_member_count_details');
                    Route::get('get-new-and-existing-member-in-chart-data', 'getNewAndExistingMemberInChartData')
                        ->name('get_new_and_existing_member_in_chart_data');
                    Route::get('get-member-gender-details', 'getMemberGenderDetails')
                        ->name('get_member_gender_details');
                    Route::get('get-member-age-group-details', 'getMemberAgeGroupDetails')
                        ->name('get_member_age_group_details');
                    Route::get('get-top-ten-members-by-year', 'getTopTenMembersByYear')
                        ->name('get_top_ten_members_by_year');
                    Route::get('get-top-ten-members-by-month', 'getTopTenMembersByMonth')
                        ->name('get_top_ten_members_by_month');
                    Route::get('get-inactive-members-counts', 'getInactiveMembersCounts')
                        ->name('get_inactive_members_counts');
                    Route::get('get-top-ten-location', 'getTopTenLocation')
                        ->name('get_top_ten_location');
                    Route::get('get-worst-ten-location', 'getWorstTenLocation')
                        ->name('get_worst_ten_location');
                    Route::get('get-top-ten-promoter', 'getTopTenPromoter')
                        ->name('get_top_ten_promoter');
                    Route::get('get-worst-ten-promoter', 'getWorstTenPromoter')
                        ->name('get_worst_ten_promoter');
                }
            );
        });

        Route::post('/generate2fa/{adminId}', [TwoFactorController::class, 'generate2FA'])->name('generate2fa');
        Route::post('/disable2fa/{adminId}', [TwoFactorController::class, 'disable2FA'])->name('disable2fa');

        Route::get('admin/edit-profile', [AdminProfileController::class, 'editProfile'])->name('edit_profile');

        Route::put('admin/{adminId}/update-profile', [AdminProfileController::class, 'update'])->name('update');

        Route::inertia('change-password', 'ChangePassword')->name('change_password');
        Route::post('update-password', [ChangePasswordController::class, 'updatePassword'])->name('update_password');

        Route::post(
            'logout',
            fn (Request $request): RedirectResponse => (new LoginController())->logout($request)
        )->name('logout');

        Route::controller(CategoryController::class)->name('categories.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('category'))->group(
                function (): void {
                    Route::get('categories', 'index')->name('index');
                    Route::get('fetch-categories', 'fetchCategories')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('category'))->group(
                function (): void {
                    Route::get('categories/create/{parentCategoryId?}', 'create')->name('create');
                    Route::post('categories', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('category'))->group(
                function (): void {
                    Route::get('categories/{categoryId}/edit', 'edit')->name('edit');
                    Route::put('categories/{categoryId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('category'))->group(
                function (): void {
                    Route::get('export-categories/{fileName}', 'exportCategories')->name('export_categories');
                }
            );
            Route::get('get-child-categories/{categoryId}', 'getChildCategories')->name('get_child_categories');
            Route::post('get-filtered-categories', 'getFilteredCategories')->name('get_filtered_categories');
            Route::get('get-parent-categories', 'getParentCategories')->name('get_parent_categories');
            Route::get('get-categories-list', 'getCategoriesList')->name('get_categories_list');
            Route::get('get-category-sales-summary', 'getCategorySalesSummary')->name('get_category_sales_summary');
            Route::get('remove-category-square-image/{categoryId}', 'removeSquareImage')->name(
                'remove_category_square_image'
            );
            Route::get('remove-category-portrait-image/{categoryId}/{mediaId}', 'removePortraitImage')
                ->name('remove_portrait_image');
            Route::get('remove-category-landscape-image/{categoryId}/{mediaId}', 'removeLandscapeImage')
                ->name('remove_landscape_image');
            Route::get('export-existing-categories', 'exportBulkUpdateCategories')->name(
                'export_bulk_update_categories'
            );
            Route::get('category-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(SaleReturnReasonController::class)->name('sale_return_reasons.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_return_reason'))->group(
                    function (): void {
                        Route::get('sale-return-reasons', 'index')->name('index');
                        Route::get('fetch-sale-return-reasons', 'fetchSaleReturnReasons')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('sale_return_reason'))->group(
                    function (): void {
                        Route::get('sale-return-reasons/create', 'create')->name('create');
                        Route::post('sale-return-reasons', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('sale_return_reason'))->group(
                    function (): void {
                        Route::get('sale-return-reasons/{saleReturnReasonId}/edit', 'edit')->name('edit');
                        Route::put('sale-return-reasons/{saleReturnReasonId}/update', 'update')->name('update');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_return_reason'))->group(
                    function (): void {
                        Route::get('export-sale-return-reasons/{fileName}', 'exportSaleReturnReasons')->name(
                            'export_sale_return_reasons'
                        );
                    }
                );
            }
        );
        Route::controller(UnitOfMeasureController::class)->name('unit_of_measures.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('unit_of_measure'))->group(
                function (): void {
                    Route::get('unit-of-measures', 'index')->name('index');
                    Route::get('fetch-unit-of-measures', 'fetchUnitOfMeasures')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('unit_of_measure'))->group(
                function (): void {
                    Route::inertia('unit-of-measures/create', 'unit_of_measures/Manage')->name('create');
                    Route::post('unit-of-measures', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('unit_of_measure'))->group(
                function (): void {
                    Route::get('unit-of-measures/{unitOfMeasureId}/edit', 'edit')->name('edit');
                    Route::put('unit-of-measures/{unitOfMeasureId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('unit_of_measure'))->group(
                function (): void {
                    Route::get('export-unit-of-measures/{fileName}', 'exportUnitOfMeasures')->name(
                        'export_unit-of-measures'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('unit_of_measure'))->group(
                function (): void {
                    Route::post('unit-of-measures/{unitOfMeasureId}/delete', 'delete')->name('delete');
                }
            );
        });
        Route::controller(PackageTypeController::class)->name('package_types.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('package_type'))->group(
                function (): void {
                    Route::get('package_types', 'index')->name('index');
                    Route::get('fetch-package-types', 'fetchPackageTypes')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('package_type'))->group(
                function (): void {
                    Route::inertia('package-types/create', 'package_types/Manage')->name('create');
                    Route::post('package-types', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('package_type'))->group(
                function (): void {
                    Route::get('package-types/{packageTypeId}/edit', 'edit')->name('edit');
                    Route::put('package-types/{packageTypeId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('package_type'))->group(
                function (): void {
                    Route::get('export-package-types/{fileName}', 'exportPackageType')->name(
                        'export_package-types'
                    );
                }
            );
        });
        Route::controller(UnitOfMeasureDerivativeController::class)->name('unit_of_measure_derivatives.')
            ->group(function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('unit_of_measure_derivative')
                )->group(
                    function (): void {
                        Route::get('unit-of-measures/{unitOfMeasureId}/derivatives', 'index')->name('index');
                        Route::get('fetch/{unitOfMeasureId}/derivatives', 'fetchDerivatives')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('unit_of_measure_derivative')
                )->group(
                    function (): void {
                        Route::get('unit-of-measures/{unitOfMeasureId}/derivatives/create', 'create')->name('create');
                        Route::post('unit-of-measures/{unitOfMeasureId}/derivatives', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('unit_of_measure_derivative')
                )->group(
                    function (): void {
                        Route::get('unit-of-measures/{unitOfMeasureId}/get-derivative/{derivativeId}', 'edit')->name(
                            'edit'
                        );
                        Route::put('unit-of-measures/{unitOfMeasureId}/derivatives/{derivativeId}', 'update')->name(
                            'update'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('unit_of_measure_derivative')
                )->group(
                    function (): void {
                        Route::get(
                            'export-derivatives-unit-of-measures/{unitOfMeasureId}/{fileName}',
                            'exportDerivatives'
                        )->name('export_derivatives_unit_of_measures');
                    }
                );
            });
        Route::controller(SizeController::class)->name('sizes.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('size'))->group(
                function (): void {
                    Route::get('sizes', 'index')->name('index');
                    Route::get('fetch-sizes', 'fetchSizes')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('size'))->group(
                function (): void {
                    Route::get('sizes/create', 'create')->name('create');
                    Route::post('sizes', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('size'))->group(
                function (): void {
                    Route::get('sizes/{sizeId}/edit', 'edit')->name('edit');
                    Route::put('sizes/{sizeId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('size'))->group(
                function (): void {
                    Route::get('export-sizes/{fileName}', 'exportSizes')->name('export_sizes');
                }
            );
            Route::post('sizes/store-return', 'storeAndReturn')->name('store_return');
            Route::post('get-filtered-sizes', 'getFilteredSizes')->name('get_filtered_sizes');
            Route::get('get-size-sales-summary', 'getSizeSalesSummary')->name('get_size_sales_summary');
            Route::get('size-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(DenominationController::class)->name('denominations.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('denomination'))->group(
                function (): void {
                    Route::get('denominations', 'index')->name('index');
                    Route::get('fetch-denominations', 'fetchDenominations')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('denomination'))->group(
                function (): void {
                    Route::inertia('denominations/create', 'denominations/Manage')->name('create');
                    Route::post('denominations', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('denomination'))->group(
                function (): void {
                    Route::get('denominations/{denominationId}/edit', 'edit')->name('edit');
                    Route::put('denominations/{denominationId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('denomination'))->group(
                function (): void {
                    Route::post('denominations/{denominationId}/delete', 'delete')->name('delete');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('denomination'))->group(
                function (): void {
                    Route::get('export-denominations/{fileName}', 'exportDenominations')->name('export_denominations');
                }
            );
        });
        Route::controller(EmployeeController::class)->name('employees.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('employee'))->group(
                function (): void {
                    Route::get('employees', 'index')->name('index');
                    Route::get('fetch-employees', 'fetchEmployees')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('employee'))->group(
                function (): void {
                    Route::get('employees/create', 'create')->name('create');
                    Route::post('employees', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('employee'))->group(
                function (): void {
                    Route::get('employees/{employeeId}/edit', 'edit')->name('edit');
                    Route::put('employees/{employeeId}', 'update')->name('update');
                    Route::get('employees/{employeeId}/resend-verification-email', 'resendVerificationEmail')->name(
                        'resend_verification_email'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('employee'))->group(
                function (): void {
                    Route::get('export-employees/{fileName}', 'exportEmployees')->name('export_employees');
                }
            );
            Route::post('employees/{employeeId}/set-status/{status}', 'setStatus')->name('set_status');
            Route::get('get-filtered-employees', 'getFilteredEmployees')->name('get_filtered_employees');
            Route::get('export-existing-employees', 'exportExistingEmployees')->name('export_existing_employees');
        });
        Route::controller(StyleController::class)->name('styles.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('style'))->group(
                function (): void {
                    Route::get('styles', 'index')->name('index');
                    Route::get('fetch-styles', 'fetchStyles')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('style'))->group(
                function (): void {
                    Route::inertia('styles/create', 'styles/Manage')->name('create');
                    Route::post('styles', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('style'))->group(
                function (): void {
                    Route::get('styles/{styleId}/edit', 'edit')->name('edit');
                    Route::put('styles/{styleId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('style'))->group(
                function (): void {
                    Route::get('export-styles/{fileName}', 'exportStyles')->name('export_styles');
                }
            );
            Route::post('styles/store-return', 'storeAndReturn')->name('store_return');
            Route::post('get-filtered-styles', 'getFilteredStyles')->name('get_filtered_styles');
            Route::get('get-style-list', 'getStylesList')->name('get_styles_list');
            Route::get('get-style-sales-summary', 'getStyleSalesSummary')->name('get_style_sales_summary');
        });
        Route::controller(ColorController::class)->name('colors.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('color'))->group(
                function (): void {
                    Route::get('colors', 'index')->name('index');
                    Route::get('fetch-colors', 'fetchColors')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('color'))->group(
                function (): void {
                    Route::get('colors/create', 'create')->name('create');
                    Route::post('colors', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('color'))->group(
                function (): void {
                    Route::get('colors/{colorId}/edit', 'edit')->name('edit');
                    Route::put('colors/{colorId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('color'))->group(
                function (): void {
                    Route::get('export-colors/{fileName}', 'exportColors')->name('export_colors');
                }
            );
            Route::post('colors/store-return', 'storeAndReturn')->name('store_return');
            Route::post('get-filtered-colors', 'getFilteredColors')->name('get_filtered_colors');
            Route::get('get-color-sales-summary', 'getColorSalesSummary')->name('get_color_sales_summary');
            Route::get('color-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(ColorGroupController::class)->name('color_groups.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('color_group'))->group(
                function (): void {
                    Route::get('color-groups', 'index')->name('index');
                    Route::get('fetch-color-groups', 'fetchColorGroups')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('color_group'))->group(
                function (): void {
                    Route::inertia('color-groups/create', 'color_group/Manage')->name('create');
                    Route::post('color-groups', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('color_group'))->group(
                function (): void {
                    Route::get('color-groups/{colorGroupId}/edit', 'edit')->name('edit');
                    Route::put('color-groups/{colorGroupId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('color_group'))->group(
                function (): void {
                    Route::get('export-color-groups/{fileName}', 'exportColorGroups')->name('export_color_groups');
                }
            );
            Route::get('get-color-group-sales-summary', 'getColorGroupSalesSummary')->name(
                'get_color_group_sales_summary'
            );
        });
        Route::controller(SizeGroupController::class)->name('size_groups.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('size_group'))->group(
                function (): void {
                    Route::get('size-groups', 'index')->name('index');
                    Route::get('fetch-size-groups', 'fetchSizeGroups')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('size_group'))->group(
                function (): void {
                    Route::inertia('size-groups/create', 'size_group/Manage')->name('create');
                    Route::post('size-groups', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('size_group'))->group(
                function (): void {
                    Route::get('size-groups/{sizeGroupId}/edit', 'edit')->name('edit');
                    Route::put('size-groups/{sizeGroupId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('size_group'))->group(
                function (): void {
                    Route::get('export-size-groups/{fileName}', 'exportSizeGroups')->name('export_size_groups');
                }
            );
        });
        Route::controller(PromoterGroupController::class)->name('promoter_groups.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('promoter_group'))->group(
                function (): void {
                    Route::get('promoter-groups', 'index')->name('index');
                    Route::get('fetch-promoter-groups', 'fetchPromoterGroups')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('promoter_group'))->group(
                function (): void {
                    Route::get('promoter-groups/create', 'create')->name('create');
                    Route::post('promoter-groups', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('promoter_group'))->group(
                function (): void {
                    Route::get('promoter-groups/{promoterGroupId}/edit', 'edit')->name('edit');
                    Route::put('promoter-groups/{promoterGroupId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('promoter_group'))->group(
                function (): void {
                    Route::get('export-promoter-groups/{fileName}', 'exportPromoterGroups')->name(
                        'export_promoter_groups'
                    );
                }
            );
            Route::get('get-promoter-groups-list', 'getPromoterGroupsList')->name('get_promoter_groups_list');
        });
        Route::controller(SeasonController::class)->name('seasons.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('season'))->group(
                function (): void {
                    Route::get('seasons', 'index')->name('index');
                    Route::get('fetch-seasons', 'fetchSeasons')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('season'))->group(
                function (): void {
                    Route::inertia('seasons/create', 'seasons/Manage')->name('create');
                    Route::post('seasons', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('season'))->group(
                function (): void {
                    Route::get('seasons/{seasonId}/edit', 'edit')->name('edit');
                    Route::put('seasons/{seasonId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('season'))->group(
                function (): void {
                    Route::get('export-seasons/{fileName}', 'exportSeasons')->name('export_seasons');
                }
            );
            Route::post('seasons/store-return', 'storeAndReturn')->name('store_return');
        });
        Route::controller(DepartmentController::class)->name('departments.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('department'))->group(
                function (): void {
                    Route::get('departments', 'index')->name('index');
                    Route::get('fetch-departments', 'fetchDepartments')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('department'))->group(
                function (): void {
                    Route::get('departments/create', 'create')->name('create');
                    Route::post('departments', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('department'))->group(
                function (): void {
                    Route::get('departments/{departmentId}/edit', 'edit')->name('edit');
                    Route::put('departments/{departmentId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('department'))->group(
                function (): void {
                    Route::get('export-departments/{fileName}', 'exportDepartments')->name('export_departments');
                }
            );
            Route::post('departments/store-return', 'storeAndReturn')->name('store_return');
            Route::post('get-filtered-departments', 'getFilteredDepartments')->name('get_filtered_departments');
            Route::get('get-departments-list', 'getDepartmentsList')->name('get_departments_list');
            Route::get('get-department-sales-summary', 'getDepartmentSalesSummary')->name(
                'get_department_sales_summary'
            );
        });
        Route::controller(CashierController::class)->name('cashiers.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('cashier'))->group(
                function (): void {
                    Route::get('cashiers', 'index')->name('index');
                    Route::get('fetch-cashiers', 'fetchCashiers')->name('fetch');
                    Route::get('cashiers/{cashierId}/change-pin', 'changePin')->name('change_pin');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('cashier'))->group(
                function (): void {
                    Route::get('cashiers/create', 'create')->name('create');
                    Route::post('cashiers', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('cashier'))->group(
                function (): void {
                    Route::get('cashiers/{cashierId}/edit', 'edit')->name('edit');
                    Route::put('cashiers/{cashierId}/update', 'update')->name('update');
                    Route::put('cashiers/{cashierId}/update-pin', 'updatePin')->name('update_pin');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('cashier'))->group(
                function (): void {
                    Route::get('export-cashiers/{fileName}', 'exportCashiers')->name('export_cashiers');
                }
            );
            Route::get('get-store-cashiers/{storeId}', 'getStoreCashiers')->name('get_store_cashiers');
            Route::post('get-cashiers-of-stores', 'getCashiersOfStores')->name('get_cashiers_of_stores');
            Route::get('export-existing-cashiers', 'exportBulkUpdateCashiers')->name('export_bulk_update_cashiers');
        });
        Route::controller(CounterController::class)->name('counters.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('counter'))->group(
                function (): void {
                    Route::get('counters', 'index')->name('index');
                    Route::get('fetch-counters', 'fetchCounters')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('counter'))->group(
                function (): void {
                    Route::get('counters/create', 'create')->name('create');
                    Route::post('counters', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('counter'))->group(
                function (): void {
                    Route::get('counters/{counterId}/edit', 'edit')->name('edit');
                    Route::put('counters/{counterId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('counter'))->group(
                function (): void {
                    Route::get('export-counters/{fileName}', 'exportCounters')->name('export_counters');
                }
            );
            Route::get('get-location-counters/{locationId}', 'getLocationCounters')->name('get_location_counters');
            Route::post('get-counters-of-locations', 'getCountersOfLocations')->name('get_counters_of_locations');
        });
        Route::controller(CashierGroupController::class)->name('cashier_groups.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('cashier_group'))->group(
                function (): void {
                    Route::get('cashier-groups', 'index')->name('index');
                    Route::get('fetch-cashier-groups', 'fetchCashierGroups')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('cashier_group'))->group(
                function (): void {
                    Route::get('cashier-groups/create', 'create')->name('create');
                    Route::post('cashier-groups', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('cashier_group'))->group(
                function (): void {
                    Route::get('cashier-groups/{cashierGroupId}/edit', 'edit')->name('edit');
                    Route::put('cashier-groups/{cashierGroupId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('cashier_group'))->group(
                function (): void {
                    Route::get('export-cashier-groups/{fileName}', 'exportCashierGroups')->name(
                        'export_cashier_groups'
                    );
                }
            );
        });
        Route::controller(ExternalProductController::class)->name('external_products.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('product'))->group(
                    function (): void {
                        Route::get('external-products', 'index')->name('index');
                        Route::get('fetch-external-products', 'fetchExternalProducts')->name('fetch');
                        Route::post('external-products/approved', 'approved')->name('approved');
                        Route::post('external-products/rejected', 'rejected')->name('rejected');
                    }
                );
            }
        );
        Route::controller(ProductController::class)->name('products.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('product'))->group(
                function (): void {
                    Route::get('products', 'index')->name('index');
                    Route::get('fetch-products', 'fetchProducts')->name('fetch');
                    Route::get('product-details/{productId}/', 'productDetails')->name('product_details');
                    Route::get('exists-product-upc/{upc}', 'existsProductUpc')->name('exists_product_upc');
                    Route::get('fetch-product-detail-by-article-number', 'fetchProductDetailsByArticleNumber')->name(
                        'fetch_product_details_by_article_number'
                    );
                    Route::get('get-select-all-product-ids', 'getSelectAllProductIds')->name(
                        'get_select_all_product_ids'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('product'))->group(
                function (): void {
                    Route::get('products/create', 'create')->name('create');
                    Route::post('products', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('product'))->group(
                function (): void {
                    Route::get('products/{productId}/edit', 'edit')->name('edit');
                    Route::put('products/{productId}', 'update')->name('update');
                    Route::put('products/{productId}/restore', 'restore')->name('restore');
                    Route::put('merge-products/{oldProductId}/{newProductId}', 'mergeAndDeleteProduct')->name(
                        'merge_products'
                    );
                }
            );
            Route::middleware('permission:product_' . PermissionList::PRODUCT_UPLOAD_IMAGE->value)->group(
                function (): void {
                    Route::post('product-upload-image', 'uploadImage')->name('upload_image');
                    Route::get('remove-product-image/{productId}/{mediaId}', 'removeProductImage')->name(
                        'remove_product_image'
                    );
                    Route::get('remove-product-video/{productId}/{mediaId}', 'removeProductVideo')->name(
                        'remove_product_video'
                    );
                    Route::get(
                        'remove-product-thumbnail/{productId}/remove-product-thumbnail',
                        'removeProductThumbnail'
                    )->name('remove_product_thumbnail');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('product'))->group(
                function (): void {
                    Route::post('products/{productId}/archive', 'archive')->name('archive');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('product'))->group(
                function (): void {
                    Route::get('export-products/{fileName}', 'exportProducts')->name('export_products');
                    Route::get('export-loyalty-point-products/{fileName}', 'exportLoyaltyPointProducts')->name(
                        'export_loyalty_point_products'
                    );
                    Route::get('export-box-products/{fileName}', 'exportBoxProducts')->name('export_box_products');
                    Route::get('print-products', 'printProducts')->name('print_products');
                    Route::get('check-product-export-limit', 'checkProductExportLimit')->name(
                        'check_product_export_limit'
                    );
                    Route::get('check-product-loyalty-point-export-limit', 'checkProductLoyaltyPointExportLimit')->name(
                        'check_product_loyalty_export_limit'
                    );
                    Route::get('check-box-product-export-limit', 'checkBoxProductExportLimit')->name(
                        'check_box_product_export_limit'
                    );
                    Route::get(
                        'check-product-export-limit-for-import-bulk-update',
                        'checkProductExportLimitForImportBulkUpdate'
                    )->name('check_product_export_limit_for_import_bulk_update');
                    Route::get(
                        'export-products-for-import-bulk-update/{fileName}',
                        'exportProductsForImportBulkUpdate'
                    )->name('export_products_for_import_bulk_update');
                }
            );
            Route::post('get-matching-upc-products', 'getMatchingUpcProducts')->name('get_matching_upc_products');
            Route::post('get-matching-upc-and-is-selling-products', 'getMatchingUpcAndIsSellingProducts')->name(
                'get_matching_upc_and_is_selling_products'
            );
            Route::post('get-products-article-numbers', 'getFilteredArticleNumber')->name(
                'get_filtered_article_number'
            );
            Route::post('get-matching-upc-inventory-products', 'getActiveInventoryProductsByUpcs')->name(
                'get_matching_upc_inventory_products'
            );
            Route::post(
                'get-matching-upc-inventory-products-with-derivatives',
                'getActiveInventoryProductsByUpcsWithDerivatives'
            )->name('get_matching_upc_inventory_products_with_derivatives');
            Route::post('search-by-article-number', 'searchByArticleNumber')->name('search_by_article_number');
            Route::post('search-by-article-number-with-stock', 'searchByArticleNumberWithStock')->name(
                'search_by_article_number_with_stock'
            );
            Route::post('search-products-by-article-number', 'searchProductsByOnlyArticleNumber')->name(
                'search_products_by_article_number'
            );
            Route::get('get-products-sales-summary', 'getProductSalesSummary')->name('get_product_sales_summary');
            Route::get('products-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
            Route::post('products-remove-sales-channel-references-data', 'removeSalesChannelReferencesData')->name(
                'remove_sales_channel_references_data'
            );
            Route::post('product-upload-image-by-article-number', 'uploadImagesByArticleNumber')->name(
                'upload_image_by_article_number'
            );
            Route::post('search-by-article-number-for-purchase-plan', 'searchByArticleNumberForPurchasePlan')->name(
                'search_by_article_number_for_purchase_plan'
            );
        });
        Route::controller(TagController::class)->name('tags.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('tag'))->group(
                function (): void {
                    Route::get('tags', 'index')->name('index');
                    Route::get('fetch-tags', 'fetchTags')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('tag'))->group(
                function (): void {
                    Route::inertia('tags/create', 'tags/Manage')->name('create');
                    Route::post('tags', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('tag'))->group(
                function (): void {
                    Route::get('tags/{tagId}/edit', 'edit')->name('edit');
                    Route::put('tags/{tagId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('tag'))->group(
                function (): void {
                    Route::get('export-tags/{fileName}', 'exportTags')->name('export_tags');
                }
            );
            Route::post('get-filtered-tags', 'getFilteredTags')->name('get_filtered_tags');
            Route::get('get-tag-list', 'getTagsList')->name('get_tags_list');
        });
        Route::controller(PaymentTypeController::class)->name('payment_types.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('payment_type'))->group(
                function (): void {
                    Route::get('payment-types', 'index')->name('index');
                    Route::get('fetch-payment-types', 'fetchPaymentTypes')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('payment_type'))->group(
                function (): void {
                    Route::get('payment-types/create', 'create')->name('create');
                    Route::post('payment-types', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('payment_type'))->group(
                function (): void {
                    Route::get('payment-types/{paymentTypeId}/edit', 'edit')->name('edit');
                    Route::put('payment-types/{paymentTypeId}', 'update')->name('update');
                    Route::post('payment-types/{paymentTypeId}/set-status/{status}', 'setStatus')->name('set_status');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('payment_type'))->group(
                function (): void {
                    Route::get('payment-types-export/{fileName}', 'paymentTypesExport')->name('export_payment_types');
                }
            );
            Route::get('export-existing-payment-types', 'exportBulkUpdatePaymentTypes')->name(
                'export_bulk_update_payment_types'
            );
        });
        Route::controller(SubPaymentTypeController::class)->name('sub_payment_types.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('payment_type'))->group(
                    function (): void {
                        Route::get('sub-payment-types/{paymentTypeId}', 'index')->name('index');
                        Route::get('fetch-sub-payment-types/{paymentTypeId}', 'fetchSubPaymentTypes')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('payment_type'))->group(
                    function (): void {
                        Route::get('sub-payment-types/{paymentTypeId}/create', 'create')->name('create');
                        Route::post('sub-payment-types/{paymentTypeId}', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('payment_type'))->group(
                    function (): void {
                        Route::get('sub-payment-types/{paymentTypeId}/{subPaymentTypeId}/edit', 'edit')->name('edit');
                        Route::put('sub-payment-types/{paymentTypeId}/{subPaymentTypeId}', 'update')->name('update');
                        Route::post(
                            'sub-payment-types/{paymentTypeId}/{subPaymentTypeId}/set-status/{status}',
                            'setStatus'
                        )->name('set_status');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('payment_type'))->group(
                    function (): void {
                        Route::get(
                            'export-sub-payment-types/{paymentTypeId}/{fileName}',
                            'exportSubPaymentTypes'
                        )->name('export_sub_payment_types');
                    }
                );
            }
        );
        Route::controller(PromoterController::class)->name('promoters.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('promoter'))->group(
                function (): void {
                    Route::get('promoters', 'index')->name('index');
                    Route::get('fetch-promoters', 'fetchPromoters')->name('fetch');
                    Route::get('promoters/{promoterId}/change-password', 'changePassword')->name('change_password');
                }
            );
            Route::post('promoters/regenerate-commission', 'regenerateCommission')->name('regenerate_commission');
            Route::middleware('permission:' . PermissionList::getWritePermissionName('promoter'))->group(
                function (): void {
                    Route::get('promoters/create', 'create')->name('create');
                    Route::post('promoters', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('promoter'))->group(
                function (): void {
                    Route::get('promoters/{promoterId}/edit', 'edit')->name('edit');
                    Route::put('promoters/{promoterId}', 'update')->name('update');
                    Route::put('promoters/{promoterId}/update-password', 'updatePassword')->name('update_password');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('promoter'))->group(
                function (): void {
                    Route::get('export-promoters/{fileName}', 'exportPromoters')->name('export_promoters');
                }
            );
            Route::get('get-store-promoters/{storeId}', 'getStorePromoters')->name('get_store_promoters');
            Route::get('get-location-promoters', 'getByLocationIds')->name('get_promoters_by_location_ids');
            Route::get('get-location-active-promoters', 'getActivePromoterByLocationIds')->name(
                'get_active_promoters_by_location_ids'
            );
            Route::post('get-promoters-of-staff-ids', 'getPromotersOfStaffIds')->name('get_promoters_of_staff_ids');
            Route::get('export-existing-promoters', 'exportExistingPromoters')->name('export_existing_promoters');
        });
        Route::controller(StoreManagerController::class)->name('store_managers.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('store_manager'))->group(
                function (): void {
                    Route::get('store-managers', 'index')->name('index');
                    Route::get('fetch-store_managers', 'fetchStoreManagers')->name('fetch');
                    Route::get('store-managers/{storeManagerId}/change-password', 'changePassword')->name(
                        'change_password'
                    );
                    Route::get('store-managers/{storeManagerId}/change-passcode', 'changePasscode')->name(
                        'change_passcode'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('store_manager'))->group(
                function (): void {
                    Route::get('store-managers/create', 'create')->name('create');
                    Route::post('store-managers', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('store_manager'))->group(
                function (): void {
                    Route::get('store-managers/{storeManagerId}/edit', 'edit')->name('edit');
                    Route::put('store-managers/{storeManagerId}/update', 'update')->name('update');
                    Route::put('store-managers/{storeManagerId}/update-password', 'updatePassword')->name(
                        'update_password'
                    );
                    Route::put('store-managers/{storeManagerId}/update-passcode', 'updatePasscode')->name(
                        'update_passcode'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('store_manager'))->group(
                function (): void {
                    Route::get('export-store-managers/{fileName}', 'exportStoreManagers')->name(
                        'export_store_managers'
                    );
                }
            );
            Route::post('get-stores-store-managers', 'getStoresStoreManagers')->name('get_stores_store_managers');
            Route::post('get-locations-store-managers', 'getLocationsStoreManagers')->name(
                'get_locations_store_managers'
            );
            Route::get(
                'store-managers/get-stores-of-store-manager-id/{storeManagerId}/',
                'getStoresOfStoreManagerId'
            )->name('get_stores_of_store_manager_id');
            Route::get('export-existing-store-managers', 'exportBulkUpdateStoreManagers')->name(
                'export_bulk_update_store_managers'
            );
        });
        Route::controller(WarehouseManagerController::class)->name('warehouse_managers.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('warehouse_manager'))->group(
                    function (): void {
                        Route::get('warehouse-managers', 'index')->name('index');
                        Route::get('fetch-warehouse-managers', 'fetchWarehouseManagers')->name('fetch');
                        Route::get('warehouse-managers/{warehouseManagerId}/change-password', 'changePassword')->name(
                            'change_password'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('warehouse_manager'))->group(
                    function (): void {
                        Route::get('warehouse-managers/create', 'create')->name('create');
                        Route::post('warehouse-managers', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('warehouse_manager'))->group(
                    function (): void {
                        Route::get('warehouse-managers/{warehouseManagerId}/edit', 'edit')->name('edit');
                        Route::put('warehouse-managers/{warehouseManagerId}/update', 'update')->name('update');
                        Route::put('warehouse-managers/{warehouseManagerId}/update-password', 'updatePassword')->name(
                            'update_password'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('warehouse_manager'))->group(
                    function (): void {
                        Route::get('export-warehouse-managers/{fileName}', 'exportWarehouseManagers')->name(
                            'export_store_managers'
                        );
                    }
                );
            }
        );
        Route::controller(GoodsReceivedNoteController::class)->name('goods_received_notes.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('goods_received_note'))->group(
                    function (): void {
                        Route::get('goods-received-notes', 'index')->name('index');
                        Route::get('fetch-goods-received-notes', 'fetchGoodsReceivedNotes')->name('fetch');
                        Route::get(
                            'get-goods-received-note-products/{goodsReceivedNoteId}',
                            'getGoodsReceivedNoteProducts'
                        )
                            ->name('products');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('goods_received_note'))->group(
                    function (): void {
                        Route::get('goods-received-notes/create', 'create')->name('create');
                        Route::post('goods-received-notes', 'store')->name('store');
                        Route::put(
                            're-upload-failed-import-records/{goodsReceivedNoteId}',
                            'reUploadFailedRecord'
                        )->name('re_upload_goods_received_note_record');
                        Route::put('goods-received-notes/{goodsReceivedNoteId}/cancel', 'markAsCancel')->name(
                            'mark_as_cancel'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('goods_received_note')
                )->group(
                    function (): void {
                        Route::get('export-goods-received-note/{fileName}', 'exportGoodReceivedNote')->name(
                            'export_goods_received_note'
                        );
                        Route::get(
                            'export-goods-received-note-products/{goodsReceivedNoteId}/{fileName}',
                            'exportGoodReceivedNoteProducts'
                        );
                        Route::get(
                            'goods-received-note-print/{goodsReceivedNoteId}',
                            'goodsReceivedNotePrint'
                        )->name('goods_received_note_print');
                    }
                );
            }
        );
        Route::controller(CashMovementReasonController::class)->name('cash_movement_reasons.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('cash_movement_reason'))->group(
                    function (): void {
                        Route::get('cash-movement-reasons', 'index')->name('index');
                        Route::get('fetch-cash-movement-reasons', 'fetchCashMovementReasons')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('cash_movement_reason')
                )->group(
                    function (): void {
                        Route::get('cash-movement-reasons/create', 'create')->name('create');
                        Route::post('cash-movement-reasons', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('cash_movement_reason')
                )->group(
                    function (): void {
                        Route::get('cash-movement-reasons/{cashMovementReasonId}/edit', 'edit')->name('edit');
                        Route::put('cash-movement-reasons/{cashMovementReasonId}', 'update')->name('update');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('cash_movement_reason')
                )->group(
                    function (): void {
                        Route::get('export-cash-movement-reasons/{fileName}', 'exportCashMovementReasons')->name(
                            'export_cash_movement_reasons'
                        );
                    }
                );
            }
        );
        Route::controller(SaleThroughRatioController::class)->name('sale_through_ratios.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_through_ratio'))->group(
                    function (): void {
                        Route::get('sale-through-ratios', 'index')->name('index');
                        Route::get('fetch-sale-through-ratios', 'fetchSaleThroughRatios')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('sale_through_ratio'))->group(
                    function (): void {
                        Route::inertia('sale-through-ratios/create', 'sale_through_ratios/Manage')->name('create');
                        Route::post('sale-through-ratios', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('sale_through_ratio'))->group(
                    function (): void {
                        Route::get('sale-through-ratios/{saleThroughRatioId}/edit', 'edit')->name('edit');
                        Route::put('sale-through-ratios/{saleThroughRatioId}', 'update')->name('update');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_through_ratio'))->group(
                    function (): void {
                        Route::get('export-sale-through-ratios/{fileName}', 'exportSaleThroughRatios')->name(
                            'export_sale_through_ratios'
                        );
                    }
                );
            }
        );
        Route::controller(MemberGroupController::class)->name('member_groups.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('member_group'))->group(
                function (): void {
                    Route::get('member-groups', 'index')->name('index');
                    Route::get('fetch-member-groups', 'fetchMemberGroups')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('member_group'))->group(
                function (): void {
                    Route::get('member-groups/create', 'create')->name('create');
                    Route::post('member-groups', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('member_group'))->group(
                function (): void {
                    Route::get('member-groups/{memberGroupId}/edit', 'edit')->name('edit');
                    Route::post('member-groups/{memberGroupId}/update', 'update')->name('update');
                    Route::post('member-groups/{memberGroupId}/remove-selected-members', 'removeSelectedMembers')->name(
                        'remove_selected_members'
                    );
                    Route::post(
                        'member-groups/{memberGroupId}/remove-selected-products',
                        'removeSelectedProducts'
                    )->name('remove_selected_products');
                    Route::post('member-sync-with-member-group', 'syncMembers')->name('sync_member');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('member_group'))->group(
                function (): void {
                    Route::get('export-member-groups/{fileName}', 'exportMemberGroups')->name('export');
                }
            );
            Route::get('member-groups-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
            Route::post('get-group-member-count', 'getGroupMemberCount')->name('get_group_member_count');
        });
        Route::controller(MemberController::class)->name('members.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('member'))->group(
                function (): void {
                    Route::get('members', 'index')->name('index');
                    Route::get('members-purchase-details/{memberId}', 'memberDetails')->name('member_details');
                    Route::get('fetch-members-purchase-details/{memberId}', 'fetchMemberDetails')
                        ->name('fetch_member_details');
                    Route::get('fetch-members', 'fetchMembers')->name('fetch');
                    Route::get('member-loyalty-points-history/{memberId}', 'loyaltyPointsHistory')->name(
                        'loyalty_points_history'
                    );
                    Route::get('fetch-member-addresses/{memberId}', 'fetchMemberAddresses')->name(
                        'fetch_member_addresses'
                    );
                    Route::get('fetch-member-sale-return-details', 'fetchMemberSaleReturnDetails')->name(
                        'fetch_member_sale_return_details'
                    );
                    Route::get('fetch-member-sale-details', 'fetchMemberSaleDetails')->name(
                        'fetch_member_sale_details'
                    );
                    Route::post('members-change-status', 'changeStatus')->name('change_status');
                    Route::get('members-merge-details/{memberId}/', 'fetchMemberDetailsForMerge')->name(
                        'fetch_member_details_for_merge'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('member'))->group(
                function (): void {
                    Route::get('members/create', 'create')->name('create');
                    Route::post('members', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('member'))->group(
                function (): void {
                    Route::get('members/{memberId}/edit', 'edit')->name('edit');
                    Route::get('members/{memberId}/resend-verification-email', 'resendVerificationEmail')->name(
                        'resend_verification_email'
                    );
                    Route::put('members/{memberId}', 'update')->name('update');
                    Route::get('delete-member-address/{memberAddressId}', 'deleteMemberAddress')->name(
                        'delete_member_address'
                    );
                    Route::put('update-loyalty-points/{memberId}', 'updateLoyaltyPoints')->name(
                        'update_loyalty_points'
                    );
                    Route::put('update-member-addresses/{memberId}', 'updateMemberAddresses')->name(
                        'update_member_addresses'
                    );
                    Route::post('merge-members/{oldMemberId}/{newMemberId}', 'mergeAndDeleteMember')->name(
                        'merge_members'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('member'))->group(
                function (): void {
                    Route::get('export-members/{fileName}', 'exportMembers')->name('export_members');
                    Route::get('check-member-export-limit', 'checkMemberExportLimit')->name(
                        'check_member_export_limit'
                    );
                    Route::get('print-members', 'printMembers')->name('print_members');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('member'))->group(
                function (): void {
                    Route::post('members/{memberId}/delete', 'delete')->name('delete');
                }
            );
            Route::get('export-existing-members', 'exportExistingMembers')->name('export_existing_members');
            Route::get('get-filtered-members', 'getFilteredMembers')->name('get_filtered_members');
            Route::post('send-emails', 'sendEmails')->name('send_emails');
            Route::get('member-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(MemberReportController::class)->name('members_report.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('member_report'))->group(
                function (): void {
                    Route::get('members-report', 'index')->name('index');
                    Route::get('fetch-members-report', 'fetchMembersReport')->name('fetch');
                    Route::get('fetch-members-details', 'fetchMembersDetails')->name('fetch_member_details');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('member_report'))->group(
                function (): void {
                    Route::get('export-members-report/{fileName}', 'exportMembersReport')->name(
                        'export_members_report'
                    );
                    Route::get('print-members-report', 'printMembers')->name('print_members_report');
                }
            );
        });
        Route::controller(EmployeeGroupController::class)->name('employee_groups.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('employee_group'))->group(
                function (): void {
                    Route::get('employee-groups', 'index')->name('index');
                    Route::get('fetch-employee-groups', 'fetchEmployeeGroups')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('employee_group'))->group(
                function (): void {
                    Route::get('employee-groups/create', 'create')->name('create');
                    Route::post('employee-groups', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('employee_group'))->group(
                function (): void {
                    Route::get('employee-groups/{employeeGroupId}/edit', 'edit')->name('edit');
                    Route::put('employee-groups/{employeeGroupId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('employee_group'))->group(
                function (): void {
                    Route::get('export-employee-groups/{fileName}', 'exportEmployeeGroups')->name('export');
                }
            );
        });
        Route::controller(VoidSaleReasonController::class)->name('void_sale_reasons.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('void_sale_reason'))->group(
                    function (): void {
                        Route::get('void-sale-reasons', 'index')->name('index');
                        Route::get('fetch-void-sale-reasons', 'fetchVoidSaleReasons')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('void_sale_reason'))->group(
                    function (): void {
                        Route::get('void-sale-reasons/create', 'create')->name('create');
                        Route::post('void-sale-reasons', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('void_sale_reason'))->group(
                    function (): void {
                        Route::get('void-sale-reasons/{voidSaleReasonId}/edit', 'edit')->name('edit');
                        Route::put('void-sale-reasons/{voidSaleReasonId}', 'update')->name('update');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('void_sale_reason'))->group(
                    function (): void {
                        Route::get('export-void-sale-reasons/{fileName}', 'exportVoidSaleReasons')->name(
                            'export_void_sale_reasons'
                        );
                    }
                );
            }
        );
        Route::controller(DirectorController::class)->name('directors.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('director'))->group(
                function (): void {
                    Route::get('directors', 'index')->name('index');
                    Route::get('fetch-directors', 'fetchDirectors')->name('fetch');
                    Route::get('directors/{directorId}/change-passcode', 'changePasscode')->name('change_passcode');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('director'))->group(
                function (): void {
                    Route::get('directors/create', 'create')->name('create');
                    Route::post('directors', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('director'))->group(
                function (): void {
                    Route::get('directors/{directorId}/edit', 'edit')->name('edit');
                    Route::put('directors/{directorId}', 'update')->name('update');
                    Route::put('directors/{directorId}/update-passcode', 'updatePasscode')->name('update_passcode');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('director'))->group(
                function (): void {
                    Route::get('export-directors/{fileName}', 'exportDirectors')->name('export_directors');
                }
            );
        });
        Route::controller(DreamPriceController::class)->name('dream_prices.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('dream_price'))->group(
                function (): void {
                    Route::get('dream-prices', 'index')->name('index');
                    Route::get('fetch-dream-prices', 'fetchDreamPrices')->name('fetch');
                    Route::get('dream-price-products/{dreamPriceId}', 'getDreamPriceProduct')->name(
                        'get_dream_price_product'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('dream_price'))->group(
                function (): void {
                    Route::get('dream-prices/create', 'create')->name('create');
                    Route::post('dream-prices', 'store')->name('store');
                    Route::get('dream-prices/{dreamPriceId}/upload-form', 'uploadForm')->name('upload_form');
                    Route::post('dream-prices/{dreamPriceId}/upload-products', 'uploadProducts')->name(
                        'upload_products'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('dream_price'))->group(
                function (): void {
                    Route::get('dream-prices/{dreamPriceId}/edit', 'edit')->name('edit');
                    Route::post('dream-prices/{dreamPriceId}/update', 'update')->name('update');
                    Route::post('dream-prices/{dreamPriceId}/update-status/{status}', 'updateStatus')
                        ->name('update_status');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('dream_price'))->group(
                function (): void {
                    Route::get('export-dream-prices/{fileName}', 'exportDreamPrices')->name('export_dream_prices');
                    Route::get(
                        'export-dream-price-products/{dreamPriceId}/{fileName}',
                        'exportDreamPriceProducts'
                    )->name('export_dream_price_products');
                }
            );
            Route::post('dream-prices/remove-selected-products', 'removeSelectedProducts')->name(
                'remove_selected_products'
            );
            Route::get('dream-prices-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(ImportRecordController::class)->name('import_records.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('import_record'))->group(
                function (): void {
                    Route::get('import-records/{id?}', 'index')->name('index');
                    Route::get('fetch-import-records', 'fetchImportRecords')->name('fetch');
                    Route::get(
                        'get-import-record-pending-statuses/{moduleType}',
                        'getPendingImportRecordCount'
                    )->name('get_import_record_pending_statuses');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('import_record'))->group(
                function (): void {
                    Route::get('import-records-create', 'create')->name('create');
                    Route::post('import-records', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('import_record'))->group(
                function (): void {
                    Route::get('export-import-records/{fileName}', 'exportImportRecords')->name(
                        'export_import_records'
                    );
                    Route::get('export-product-price-update/{fileName}', 'exportProductPriceUpdate')->name(
                        'export_product_price_update'
                    );
                }
            );
        });
        Route::controller(ComplimentaryItemReasonController::class)->name('complimentary_item_reasons.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('complimentary_setup'))->group(
                    function (): void {
                        Route::get('complimentary-item-reasons', 'index')->name('index');
                        Route::get('fetch-complimentary-item-reasons', 'fetchComplimentaryItemReasons')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('complimentary_setup'))->group(
                    function (): void {
                        Route::inertia('complimentary-item-reasons/create', 'complimentary_item_reasons/Manage')->name(
                            'create'
                        );
                        Route::post('complimentary-item-reasons', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('complimentary_setup')
                )->group(
                    function (): void {
                        Route::get('complimentary-item-reasons/{complimentaryItemReasonId}/edit', 'edit')->name('edit');
                        Route::put('complimentary-item-reasons/{complimentaryItemReasonId}', 'update')->name('update');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('complimentary_setup')
                )->group(
                    function (): void {
                        Route::get(
                            'export-complimentary-item-reasons/{fileName}',
                            'exportComplimentaryItemReasons'
                        )->name('export_complimentary_item_reasons');
                    }
                );
            }
        );
        Route::controller(EmailRecipientController::class)->name('email_recipients.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('email_recipient'))->group(
                function (): void {
                    Route::get('email-recipients', 'index')->name('index');
                    Route::get('fetch-email-recipients', 'fetchEmailRecipients')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('email_recipient'))->group(
                function (): void {
                    Route::get('email-recipients/create', 'create')->name('create');
                    Route::post('email-recipients', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('email_recipient'))->group(
                function (): void {
                    Route::get('email-recipients/{emailRecipientId}/edit', 'edit')->name('edit');
                    Route::get(
                        'email-recipients/{emailRecipientId}/resend-verification-email',
                        'resendVerificationEmail'
                    )->name('resend_verification_email');
                    Route::put('email-recipients/{emailRecipientId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('email_recipient'))->group(
                function (): void {
                    Route::get('export-email-recipients/{fileName}', 'exportEmailRecipients')->name(
                        'export_email_recipients'
                    );
                }
            );
        });
        Route::controller(AutomatedNotificationController::class)->name('automated_notifications.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('automated_notification')
                )->group(
                    function (): void {
                        Route::get('automated-notifications', 'index')->name('index');
                        Route::get('fetch-automated-notifications', 'fetchAutomatedNotifications')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('automated_notification')
                )->group(
                    function (): void {
                        Route::get('automated-notifications/create', 'create')->name('create');
                        Route::post('automated-notifications', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('automated_notification')
                )->group(
                    function (): void {
                        Route::get('automated-notifications/{automatedNotificationId}/edit', 'edit')->name('edit');
                        Route::post('automated-notifications/{automatedNotificationId}', 'update')->name('update');
                        Route::put('remove-selected-stores/{automatedNotificationId}', 'removeSelectedStores')->name(
                            'remove_selected_stores'
                        );
                        Route::put(
                            'remove-selected-products/{automatedNotificationId}',
                            'removeSelectedProducts'
                        )->name('remove_selected_products');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('automated_notification')
                )->group(
                    function (): void {
                        Route::get('export-automated-notifications/{fileName}', 'exportAutomatedNotifications')->name(
                            'export_automated_notifications'
                        );
                    }
                );
                Route::get(
                    'automated-notifications/{automatedNotificationId}/export-automated-notification-stores/{fileName}',
                    'exportAutomatedNotificationStores'
                )
                    ->name('export_automated_notification_stores');
                Route::get(
                    'automated-notifications/{automatedNotificationId}/export-automated-notification-products/{fileName}',
                    'exportAutomatedNotificationProducts'
                )
                    ->name('export_automated_notification_products');
            }
        );
        Route::controller(PromotionController::class)->name('promotions.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('promotion'))->group(
                function (): void {
                    Route::get('promotions', 'index')->name('index');
                    Route::get('fetch-promotions', 'fetchPromotions')->name('fetch');
                    Route::get('promotions/{promotionId}/clone', 'clone')->name('clone');
                    Route::get('fetch-promotions-details/{promotionId}', 'fetchPromotionDetailsById')->name(
                        'fetch_promotions_details'
                    );
                    Route::get('fetch-calender', 'fetchCalender')->name('fetch_calender');
                    Route::get('generate-promo-codes/{total}', 'generatePromoCodes')->name('generate_promo_codes');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('promotion'))->group(
                function (): void {
                    Route::get('promotions/create', 'create')->name('create');
                    Route::post('promotions', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('promotion'))->group(
                function (): void {
                    Route::get('promotions/{promotionId}/edit', 'edit')->name('edit');
                    Route::put('promotions/{promotionId}/update', 'update')->name('update');
                    Route::post('promotions/{promotionId}/set-status/{status}', 'setStatus')->name('set_status');
                    Route::post('exists-promo-codes/{promotionId?}', 'existsPromoCodes')->name('exists_promo_codes');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('promotion'))->group(
                function (): void {
                    Route::get('export-promotions/{fileName}', 'exportPromotions')->name('export_promotions');
                }
            );
            Route::post('promotions/remove-selected-products', 'removeSelectedProducts')->name(
                'remove_selected_products'
            );
            Route::get(
                'promotions/{promotionId}/export-promotions-products-details/{fileName}',
                'exportPromotionsProductsDetails'
            )->name('export_promotions_products_details');
        });
        Route::controller(MysteryGiftController::class)->name('mystery_gifts.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('mystery_gift'))->group(
                function (): void {
                    Route::get('mystery-gifts', 'index')->name('index');
                    Route::get('fetch-mystery-gifts', 'fetchMysteryGifts')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('mystery_gift'))->group(
                function (): void {
                    Route::get('mystery-gifts/create', 'create')->name('create');
                    Route::post('mystery-gifts', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('mystery_gift'))->group(
                function (): void {
                    Route::get('mystery-gifts/{mysteryGiftId}/edit', 'edit')->name('edit');
                    Route::put('mystery-gifts/{mysteryGiftId}/update', 'update')->name('update');
                    Route::post('mystery-gifts/{mysteryGiftId}/set-status/{status}', 'setStatus')->name(
                        'set_status'
                    );
                    Route::get('mystery-gifts/{mysteryGiftId}/generate-qr-code', 'generateQrCode')->name(
                        'generate_qr_code'
                    );
                }
            );
            Route::post('mystery-gifts/remove-selected-products', 'removeSelectedProducts')->name(
                'remove_selected_products'
            );
            Route::get(
                'mystery-gifts/{mysteryGiftId}/export-mystery-gifts-products-details/{fileName}',
                'exportMysteryGiftsProductsDetails'
            )->name('export_mystery_gifts_products_details');
        });
        Route::controller(StockAdjustmentController::class)->name('stock_adjustments.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_adjustment'))->group(
                    function (): void {
                        Route::get('stock-adjustments', 'index')->name('index');
                        Route::get('fetch-stock-adjustments', 'fetchStockAdjustments')->name('fetch');
                        Route::get('get-stock-adjustment-items/{stockAdjustmentId}', 'getStockAdjustmentItems')->name(
                            'items'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('stock_adjustment'))->group(
                    function (): void {
                        Route::get('stock-adjustments/create', 'create')->name('create');
                        Route::post('stock-adjustments', 'store')->name('store');
                        Route::put(
                            're-upload-stock-adjustments-failed-import-records/{stockAdjustmentId}',
                            'reUploadFailedRecord'
                        )->name('re_upload_failed_record');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_adjustment'))->group(
                    function (): void {
                        Route::get('export-stock-adjustment/{fileName}', 'exportStockAdjustments')->name(
                            'export_stock_adjustment'
                        );
                        Route::get('export-stock-adjustment-items/{stockAdjustmentId}/{fileName}', 'exportItems')->name(
                            'export_items'
                        );
                    }
                );
            }
        );
        Route::controller(StockTransferController::class)->name('stock_transfers.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_transfer'))->group(
                function (): void {
                    Route::get('stock-transfers', 'index')->name('index');
                    Route::get('fetch-stock-transfers', 'fetchStockTransfers')->name('fetch');
                    Route::get('get-stock-transfer-types', 'getStockTransferTypes')
                        ->name('get_stock_transfer_types');
                    Route::get(
                        'fetch-stock-transfer-items/{stockTransferId}',
                        'fetchStockTransferItemByStockTransferId'
                    )->name('fetch_stock_transfer_items');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('stock_transfer'))->group(
                function (): void {
                    Route::get('stock-transfers/create/{transferType}', 'create')->name('create');
                    Route::post('stock-transfers', 'store')->name('store');
                    Route::get('fetch-aggregate-average-days', 'fetchAggregateAverageDays')->name(
                        'aggregate_average_days'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('stock_transfer'))->group(
                function (): void {
                    Route::get('stock-transfers/{stockTransferId}/edit', 'edit')->name('edit');
                    Route::put('stock-transfers/{stockTransferId}/update', 'update')->name('update');
                    Route::get('stock-transfers/{stockTransferId}/edit-request-order', 'editRequestOrder')
                        ->name('edit_request_order');
                    Route::put('stock-transfers/{stockTransferId}/update-request-order', 'updateRequestOrder')
                        ->name('update_request_order');
                    Route::post('stock-transfers/{stockTransferId}/update-status', 'updateStatus')->name(
                        'update_status'
                    );
                    Route::post(
                        'stock-transfers/{stockTransferId}/update-received-date-and-status',
                        'updateReceivedDateAndStatus'
                    )
                        ->name('update_received_date_and_status');
                    Route::get('stock-transfers/{stockTransferId}/shipping-details', 'shippingDetails')
                        ->name('shipping_details');
                    Route::get('stock-transfers/{stockTransferId}/delivery-note', 'deliveryNote')
                        ->name('delivery_note');
                    Route::post(
                        'stock-transfers/{stockTransferId}/update-received-quantities',
                        'updateReceivedQuantities'
                    )
                        ->name('update_received_quantities');
                    Route::put('stock-transfers/{stockTransferId}/close', 'closeStockTransfer')
                        ->name('close_stock_transfer');
                    Route::post(
                        'stock-transfers/{stockTransferId}/set-received-quantity-same-as-quantity',
                        'setReceivedQuantitySameAsQuantity'
                    )
                        ->name('set_received_same_quantities');
                    Route::post(
                        'stock-transfers/{stockTransferId}/update-shipping-details-and-mark-as-approved',
                        'updateShippingDetailsAndMarkAsApproved'
                    )->name('update_shipping_details_and_mark_as_approved');
                    Route::get('stock-transfers/{stockTransferId}/discrepancy', 'discrepancy')
                        ->name('discrepancy');
                    Route::post(
                        'stock-transfers/{stockTransferId}/{stockTransferItemId}/discrepancy-proof',
                        'discrepancyProof'
                    )
                        ->name('discrepancy_proof');
                    Route::get(
                        'stock-transfers/{stockTransferItemId}/remove-discrepancy-proof',
                        'removeDiscrepancyProof'
                    )
                        ->name('remove_discrepancy_proof');
                    Route::put('stock-transfers/{stockTransferId}/close-discrepancy', 'closeDiscrepancy')
                        ->name('close_discrepancy');
                    Route::put('stock-transfers/{stockTransferId}/update-additional-items', 'updateAdditionalItems')
                        ->name('update_additional_items');
                    Route::post('add-delivery-note-item-remarks/{stockTransferItemId}', 'deliveryNoteItemRemarks')
                        ->name('add_delivery_note_item_remarks');
                    Route::get('remove-additional-item/{stockTransferItemId}', 'removeAdditionalItem')
                        ->name('remove_additional_item');
                    Route::post('update-shipped-type/{stockTransferId}', 'markAsShippedOrTransit')
                        ->name('mark_as_shipped_or_transit');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_transfer'))->group(
                function (): void {
                    Route::get('export-stock-transfers/{fileName}', 'exportStockTransfers')->name(
                        'export_stock_transfers'
                    );
                    Route::get('export-stock-transfer-items/{stockTransferId}/{fileName}', 'exportStockTransferItems');
                }
            );
            Route::get('stock-transfers/{stockTransferId}/{transferType}/print', 'printStockTransfer')
                ->name('print_stock_transfer');
        });
        Route::controller(StockTransferReasonController::class)->name('stock_transfer_reasons.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('stock_transfer_reason')
                )->group(
                    function (): void {
                        Route::get('stock-transfer-reasons', 'index')->name('index');
                        Route::get('fetch-stock-transfer-reasons', 'fetchStockTransferReasons')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('stock_transfer_reason')
                )->group(
                    function (): void {
                        Route::inertia('stock-transfer-reasons/create', 'stock_transfer_reasons/Manage')->name(
                            'create'
                        );
                        Route::post('stock-transfer-reasons', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('stock_transfer_reason')
                )->group(
                    function (): void {
                        Route::get('stock-transfer-reasons/{stockTransferReasonId}/edit', 'edit')->name('edit');
                        Route::put('stock-transfer-reasons/{stockTransferReasonId}', 'update')->name('update');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('stock_transfer_reason')
                )->group(
                    function (): void {
                        Route::get('export-stock-transfer-reasons/{fileName}', 'exportStockTransferReasons')->name(
                            'export_stock_transfer_reasons'
                        );
                    }
                );
            }
        );
        Route::controller(VoucherConfigurationController::class)->name('vouchers_configuration.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('vouchers_configuration')
                )->group(
                    function (): void {
                        Route::get('vouchers-configuration', 'index')->name('index');
                        Route::get('fetch-vouchers-configuration', 'fetchVoucherConfigurations')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('vouchers_configuration')
                )->group(
                    function (): void {
                        Route::get('vouchers-configuration/create', 'create')->name('create');
                        Route::post('voucher-configurations', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('vouchers_configuration')
                )->group(
                    function (): void {
                        Route::get('voucher-configurations/{voucherConfigurationId}/edit', 'edit')->name('edit');
                        Route::put('voucher-configurations/{voucherConfigurationId}', 'update')->name('update');
                        Route::post(
                            'voucher-configurations/{voucherConfigurationId}/set-status/{status}',
                            'setStatus'
                        )->name('set_status');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('vouchers_configuration')
                )->group(
                    function (): void {
                        Route::get('export-voucher-configurations/{fileName}', 'exportVoucherConfigurations')->name(
                            'export_voucher_configurations'
                        );
                    }
                );
                Route::post('voucher-configurations/remove-selected-products', 'removeSelectedProducts')->name(
                    'remove_selected_products'
                );
            }
        );
        Route::controller(CashbackController::class)->name('cashbacks.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('cashback'))->group(
                function (): void {
                    Route::get('cashbacks', 'index')->name('index');
                    Route::get('fetch-cashbacks', 'fetchCashbacks')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('cashback'))->group(
                function (): void {
                    Route::get('cashbacks/create', 'create')->name('create');
                    Route::post('cashbacks', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('cashback'))->group(
                function (): void {
                    Route::get('cashbacks/{cashbackId}/edit', 'edit')->name('edit');
                    Route::put('cashbacks/{cashbackId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('cashback'))->group(
                function (): void {
                    Route::get('export-cashbacks/{fileName}', 'exportCashbacks')->name('export_cashbacks');
                }
            );
            Route::post('cashbacks/remove-selected-products', 'removeSelectedProducts')->name(
                'remove_selected_products'
            );
            Route::get(
                'cashbacks/{cashbackId}/export-cashback-products/{fileName}',
                'exportCashbackProducts'
            )->name('export_cashback_products');
        });
        Route::controller(MembershipController::class)->name('memberships.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('membership'))->group(
                function (): void {
                    Route::get('memberships', 'index')->name('index');
                    Route::get('fetch-memberships', 'fetchMemberships')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('membership'))->group(
                function (): void {
                    Route::inertia('memberships/create', 'memberships/Manage')->name('create');
                    Route::post('memberships', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('membership'))->group(
                function (): void {
                    Route::get('memberships/{membershipId}/edit', 'edit')->name('edit');
                    Route::put('memberships/{membershipId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('membership'))->group(
                function (): void {
                    Route::get('export-memberships/{fileName}', 'exportMemberships')->name('export_memberships');
                }
            );
        });
        Route::controller(LoyaltyCampaignController::class)->name('loyalty_campaigns.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('loyalty_campaign'))->group(
                    function (): void {
                        Route::get('loyalty-campaigns', 'index')->name('index');
                        Route::get('fetch-loyalty-campaigns', 'fetchLoyaltyCampaigns')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('loyalty_campaign'))->group(
                    function (): void {
                        Route::get('loyalty-campaigns/create', 'create')->name('create');
                        Route::post('loyalty-campaigns', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('loyalty_campaign'))->group(
                    function (): void {
                        Route::get('loyalty-campaigns/{loyaltyCampaignId}/edit', 'edit')->name('edit');
                        Route::put('loyalty-campaigns/{loyaltyCampaignId}/update', 'update')->name('update');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('loyalty_campaign'))->group(
                    function (): void {
                        Route::get('export-loyalty-campaigns/{fileName}', 'exportLoyaltyCampaigns')->name(
                            'export_loyalty_campaigns'
                        );
                    }
                );
            }
        );
        Route::controller(ProductFilterController::class)->group(function (): void {
            Route::get('get-filtered-products', 'getFilteredProducts')->name('get_filtered_products');
            Route::get('get-filtered-products-list', 'getFilteredProductsList')->name('get_filtered_products_list');
            Route::get('get-filtered-inventory-products', 'getFilteredInventoryProducts')->name(
                'get_filtered_inventory_products'
            );
            Route::get('get-filtered-regular-products', 'getFilteredRegularProducts')->name(
                'get_filtered_regular_products'
            );
            Route::get('get-filtered-regular-products-list', 'getFilteredRegularProductsList')->name(
                'get_filtered_regular_products_list'
            );
            Route::get('get-filtered-inventory-products-list', 'getFilteredInventoryProductsList')->name(
                'get_filtered_inventory_products_list'
            );
            Route::get('products/{productId}', 'getProduct')->name('get_product');
        });
        Route::controller(InventoryController::class)->group(function (): void {
            Route::get('get-stocks', 'getStocks')->name('get_inventory_stocks');
            Route::get('get-location-stocks', 'getLocationStocksForPurchaseOrder')->name(
                'get_location_inventory_stocks'
            );
            Route::get('get-brand-sales-summary', 'getStocks')->name('get-stocks');
            Route::post('get-matching-upc-product-with-store', 'getMatchingUpcProductWithStore')->name(
                'get_matching_upc_product_with_store'
            );
        });
        Route::controller(BrandController::class)->name('brands.')->group(function (): void {
            Route::post('get-filtered-brands', 'getFilteredBrands')->name('get_filtered_brands');
            Route::post('get-brands', 'getBrands')->name('get_brands');
            Route::get('get-brand-sales-summary', 'getBrandSalesSummary')->name('get_brand_sales_summary');
        });
        Route::controller(DesignationController::class)->name('designations.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('designation'))->group(
                function (): void {
                    Route::get('designations', 'index')->name('index');
                    Route::get('fetch-designations', 'fetchDesignations')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('designation'))->group(
                function (): void {
                    Route::inertia('designations/create', 'designations/Manage')->name('create');
                    Route::post('designations', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('designation'))->group(
                function (): void {
                    Route::get('designations/{designationId}/edit', 'edit')->name('edit');
                    Route::put('designations/{designationId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('designation'))->group(
                function (): void {
                    Route::get('export-designations/{fileName}', 'exportDesignations')->name('export_designations');
                }
            );
        });
        Route::controller(ProductsReportController::class)->name('products_report.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('product_report'))->group(
                function (): void {
                    Route::get('products-report', 'index')->name('index');
                    Route::get('fetch-products-report', 'fetchProductsReport')->name('fetch');
                }
            );
            Route::get('fetch-products-verification-details/{id?}', 'fetchProductsVerificationDetails')->name(
                'fetch_products_verification_details'
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('product_report'))->group(
                function (): void {
                    Route::get('export-products-report/{fileName}', 'exportProductsReport')->name(
                        'export_products_report'
                    );
                    Route::get('print-products-report', 'printProducts')->name('print_products_report');
                }
            );
        });
        Route::controller(GenuineProductVerificationReportController::class)->name(
            'product_verification_reports.'
        )->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('genuine_product_verification')
                )->group(
                    function (): void {
                        Route::get('product-verification-reports', 'productVerificationReports')->name('index');
                        Route::get('fetch-product-verification-reports', 'fetchProductVerificationReports')->name(
                            'fetch'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('genuine_product_verification')
                )->group(
                    function (): void {
                        Route::get(
                            'export-products-verification-report/{fileName}',
                            'exportProductsVerificationReport'
                        )->name('export_products_report');
                        Route::get('print-products-verification-report', 'printProductVerifications')->name(
                            'print_products_verification_report'
                        );
                    }
                );
            }
        );
        Route::controller(GenuineReceiptVerificationReportController::class)->name(
            'receipt_verification_reports.'
        )->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('genuine_receipt_verification')
                )->group(
                    function (): void {
                        Route::get('receipt-verification-reports', 'index')->name('index');
                        Route::get('fetch-receipt-verification-reports', 'fetchReceiptVerificationReports')->name(
                            'fetch'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('genuine_receipt_verification')
                )->group(
                    function (): void {
                        Route::get(
                            'export-receipts-verification-report/{fileName}',
                            'exportReceiptsVerificationReport'
                        )->name('export_receipts_report');
                        Route::get('print-receipts-verification-report', 'printReceiptVerifications')->name(
                            'print_receipts_verification_report'
                        );
                    }
                );
            }
        );
        Route::controller(ConsignmentReportController::class)->name('consignment_report.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('consignment_report'))->group(
                    function (): void {
                        Route::get('consignment-report', 'index')->name('index');
                        Route::get('fetch-consignment-report', 'fetchConsignmentReport')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('consignment_report'))->group(
                    function (): void {
                        Route::get('export-consignment-report/{fileName}', 'exportConsignmentReport')->name(
                            'export_consignment_report'
                        );
                        Route::get('print-consignment-report', 'printConsignment')->name('print_consignment_report');
                    }
                );
            }
        );
        Route::controller(ProfitAndLossReportController::class)->name('profits_and_losses_report.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('profit_and_loss_report')
                )->group(
                    function (): void {
                        Route::get('profits-and-losses-report', 'index')->name('index');
                        Route::get('fetch-profits-and-losses-report', 'fetch')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('profit_and_loss_report')
                )->group(
                    function (): void {
                        Route::get('export-profits-and-losses-report/{fileName}', 'export')->name('export');
                        Route::get('print-profits-and-losses-report', 'print')->name('print');
                    }
                );
            }
        );
        Route::controller(ProductAgeingReportController::class)->name('products_ageing_report.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('product_ageing'))->group(
                    function (): void {
                        Route::get('products-ageing-report', 'index')->name('index');
                        Route::get('fetch-products-ageing-report', 'fetchProductsAgeingReport')->name('fetch');
                        Route::get(
                            'fetch-consolidate-products-ageing-report',
                            'fetchConsolidateProductsAgeingReport'
                        )->name('fetch_consolidate');
                        Route::get(
                            'fetch-products-ageing-report-by-article-number',
                            'fetchProductsAgeingReportByArticleNumber'
                        )->name('fetch_product_aging_by_article_number');
                        Route::get(
                            'fetch-consolidate-products-ageing-report-by-article-number',
                            'fetchConsolidateProductsAgeingReportByArticleNumber'
                        )->name('fetch_consolidate_by_article_number');
                        Route::get(
                            'fetch-products-ageing-report-by-upc',
                            'fetchProductsAgeingReportByUpc'
                        )->name('fetch_product_aging_by_upc');
                        Route::get(
                            'fetch-consolidate-products-ageing-report-by-upc',
                            'fetchConsolidateProductsAgeingReportByUpc'
                        )->name('fetch_consolidate_by_upc');
                        Route::get('products-ageing-report-get-latest-data-sync', 'getLatestDataSync')->name(
                            'get_latest_data_sync'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('product_ageing'))->group(
                    function (): void {
                        Route::get('export-products-ageing-report/{fileName}', 'exportProductsAgeingReport')->name(
                            'export_products_ageing_report'
                        );
                        Route::get('check-product-ageing-export-limit', 'checkProductAgeingExportLimit')->name(
                            'check_product_ageing_export_limit'
                        );
                        Route::get('print-products-ageing-report', 'printProductsAgeing')->name(
                            'print_products_ageing_report'
                        );
                        Route::get(
                            'print-products-ageing-report-by-article-number',
                            'printProductsAgeingReportByArticleNumber'
                        )->name('print_products_ageing_report_by_article_number');
                        Route::get(
                            'check-product-ageing-by-month-year-export-limit',
                            'checkProductAgeingByMonthAndYearExportLimit'
                        )->name('check_product_ageing_by_month_year_export_limit');
                        Route::get(
                            'export-products-ageing-report-by-month-and-year/{fileName}',
                            'exportProductsAgeingReportByMonthAndYear'
                        )->name('export_products_ageing_report_by_month_and_year');
                        Route::get(
                            'print-products-ageing-report-by-month-and-year',
                            'printProductsAgeingByMonthAndYear'
                        )->name('print_products_ageing_report_by_month_and_year');
                        Route::get(
                            'export-products-ageing-report-by-article-number/{fileName}',
                            'exportProductsAgeingReportByArticleNumber'
                        )->name('export_products_ageing_report_by_article_number');
                        Route::get(
                            'check-product-ageing-export-limit-by-article-number',
                            'checkProductAgeingExportLimitByArticleNumber'
                        )->name('check_product_ageing_export_limit_by_article_number');
                        Route::get(
                            'print-products-ageing-report-by-upc',
                            'printProductsAgeingReportByUpc'
                        )->name('print_products_ageing_report_by_upc');
                        Route::get(
                            'export-products-ageing-report-by-upc/{fileName}',
                            'exportProductsAgeingReportByUpc'
                        )->name('export_products_ageing_report_by_upc');
                        Route::get(
                            'check-product-ageing-export-limit-by-upc',
                            'checkProductAgeingExportLimitByUpc'
                        )->name('check_product_ageing_export_limit_by_upc');
                    }
                );
                Route::get(
                    'fetch-products-ageing-report-by-month-and-year',
                    'fetchProductsAgeingReportByMonthAndYear'
                )->name('fetch_by_month_and_year');
                Route::get(
                    'fetch-consolidate-products-ageing-report-by-month-and-year',
                    'fetchConsolidateProductsAgeingReportByMonthAndYear'
                )->name('fetch_consolidate_by_month_and_year');
            }
        );
        Route::controller(SaleController::class)->name('sales.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('sale'))->group(
                function (): void {
                    Route::get('sales', 'index')->name('index');
                    Route::get('fetch-sales', 'fetchRegularSales')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('sale'))->group(
                function (): void {
                    Route::get('export-sales/{fileName}', 'exportSales')->name('export_sales');
                    Route::get('print-sale-digital-invoice/{saleId}', 'printDigitalInvoice')->name(
                        'print_sale_digital_invoice'
                    );
                }
            );
            Route::get('fetch-sale-items/{saleId}', 'fetchSaleItemsBySaleId')->name('fetch_sale_items');
        });
        Route::controller(DigitalInvoiceController::class)->name('digital_invoices.')->group(function (): void {
            Route::middleware('permission:digital_invoice_' . PermissionList::E_INVOICE_GENERATE->value)->group(
                function (): void {
                    Route::post('digital-invoice-store', 'digitalInvoiceStore')->name('digital_invoice_store');
                }
            );
        });
        Route::controller(SaleReturnController::class)->name('sale_returns.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_return'))->group(
                function (): void {
                    Route::get('sale-returns', 'index')->name('index');
                    Route::get('fetch-sale-returns', 'fetchSaleReturns')->name('fetch');
                    Route::get('get-sale-return-items/{saleReturnId}', 'fetchSaleReturnItems')->name(
                        'fetch_sale_return_items'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_return'))->group(
                function (): void {
                    Route::get('export-sale-returns/{fileName}', 'exportSaleReturns')->name('export_sale_returns');
                    Route::get('print-sale-return-digital-invoice/{saleReturnId}', 'printDigitalInvoice')->name(
                        'print_sale_return_digital_invoice'
                    );
                }
            );
        });
        Route::controller(DifferentStoreReturnsController::class)->name('different_store_returns.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('different_store_return')
                )->group(
                    function (): void {
                        Route::get('different-store-returns', 'index')->name('index');
                        Route::get('fetch-different-store-returns', 'fetchDifferentStoreReturns')->name('fetch');
                        Route::get(
                            'get-sale-return-items-for-different-store/{saleReturnId}',
                            'fetchSaleReturnItemsForDifferentStore'
                        )->name('fetch_sale_return_items');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('different_store_return')
                )->group(
                    function (): void {
                        Route::get('export-different-store-returns/{fileName}', 'exportDifferentStoreReturns')->name(
                            'export_sale_returns'
                        );
                        Route::get(
                            'print-sale-return-digital-invoice-for-different-store/{saleReturnId}',
                            'printDigitalInvoice'
                        )->name('print_sale_return_digital_invoice_for_different_store');
                    }
                );
            }
        );
        Route::controller(ClosedCounterReportController::class)->name('closed_counters.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('shift_close'))->group(
                    function (): void {
                        Route::get('closed-counters', 'index')->name('index');
                        Route::get('fetch-closed-counters', 'fetchClosedCounters')->name('fetch');
                        Route::get('fetch-closed-counter-details/{counterUpdateId}', 'fetchClosedCounterDetails')->name(
                            'fetch_closed_counter_details'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('shift_close'))->group(
                    function (): void {
                        Route::get('export-closed-counters/{fileName}', 'exportClosedCounters')->name(
                            'export_closed_counters'
                        );
                        Route::get(
                            'fetch-closed-counter-print-details/{counterUpdateId}',
                            'fetchClosedCounterPrintDetails'
                        )->name('fetch_closed_counter_print_details');
                    }
                );
                Route::get('export-closed-counter-attempts/{counterUpdateId}', 'exportClosedCounterAttempts')->name(
                    'export_closed_counter_attempts'
                );
                Route::get('export-closed-counter-tills/{counterUpdateId}', 'exportClosedCounterTills')->name(
                    'export_closed_counter_tills'
                );
                Route::get('export-take-break/{counterUpdateId}', 'exportClosedCounterTakeBreak')->name(
                    'export_take_break'
                );
                Route::get('export-drawer-details/{counterUpdateId}', 'exportClosedCounterDrawerDetails')->name(
                    'export_drawer_details'
                );
                Route::get('export-track-offline-mode/{counterUpdateId}', 'exportTrackOfflineMode')->name(
                    'export_track_offline_mode'
                );
            }
        );
        Route::controller(MemberSalesReportController::class)->name('member_sales_report.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('member_sale'))->group(
                    function (): void {
                        Route::get('member-sales-report', 'index')->name('index');
                        Route::get('fetch-member-sales-report', 'fetchMemberSales')->name('fetch');
                        Route::get(
                            'fetch-member-report-sale-details/{saleItemId}',
                            'fetchSaleDetailsBySaleItemId'
                        )->name('fetch_member_report_sale_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('member_sale'))->group(
                    function (): void {
                        Route::get('export-member-sales/{fileName}', 'exportMemberSales')->name('export_member_sales');
                    }
                );
            }
        );
        Route::controller(StockMovementLedgerReportController::class)->name('stock_movement_ledger_report.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('stock_movement_ledger')
                )->group(
                    function (): void {
                        Route::get('stock-movement-ledger-report', 'index')->name('index');
                        Route::get('fetch-stock-movement-ledger-report', 'fetchStockMovementLedgerReport')->name(
                            'fetch'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('stock_movement_ledger')
                )->group(
                    function (): void {
                        Route::get('export-stock-movement-ledger/{fileName}', 'exportStockMovementLedger')->name(
                            'export_stock_movement_ledger'
                        );
                    }
                );
            }
        );

        Route::controller(ProductSerialNumberReportController::class)->name('product_serial_number_report.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('product_serial_number')
                )->group(
                    function (): void {
                        Route::get('product-serial-number-report', 'index')->name('index');
                        Route::get('fetch-product-serial-number-report', 'fetchProductSerialNumberReport')->name(
                            'fetch'
                        );
                    }
                );
            }
        );

        Route::controller(ReservedInventoryReportController::class)->name('reserved_inventory_reports.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('reserved_inventory'))->group(
                    function (): void {
                        Route::get('reserved-inventory-reports', 'index')->name('index');
                        Route::get('fetch-reserved-inventory-report', 'fetchReservedInventoryReport')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('reserved_inventory'))->group(
                    function (): void {
                        Route::get('export-reserved-inventory/{fileName}', 'exportReservedInventory')->name(
                            'export_reserved_inventories'
                        );
                    }
                );
            }
        );
        Route::controller(TransitInventoryReportController::class)->name('transit_inventory_reports.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('transit_inventory'))->group(
                    function (): void {
                        Route::get('transit-inventory-reports', 'index')->name('index');
                        Route::get('fetch-transit-inventory-report', 'fetchTransitInventoryReport')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('transit_inventory'))->group(
                    function (): void {
                        Route::get('export-transit-inventory/{fileName}', 'exportTransitInventory')->name(
                            'export_transit_inventories'
                        );
                    }
                );
            }
        );
        Route::controller(SalesByPromoterController::class)->name('sales_by_promoters.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sales_by_promoter'))->group(
                    function (): void {
                        Route::get('sales-by-promoters', 'index')->name('index');
                        Route::get('fetch-sales-by-promoters', 'fetchSalesByPromoters')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sales_by_promoter'))->group(
                    function (): void {
                        Route::get('export-sales-by-promoters/{fileName}', 'exportSalesByPromoters')->name(
                            'export_sales_by_promoters'
                        );
                    }
                );
            }
        );
        Route::controller(LayawaySaleController::class)->name('layaway_sales.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('layaway_sale'))->group(
                function (): void {
                    Route::get('layaway-sales', 'index')->name('index');
                    Route::get('fetch-layaway-sales', 'fetchPendingLayawaySales')->name('fetch');
                    Route::get('fetch-layaway-sale-items/{saleId}', 'fetchLayawaySaleItemsBySaleId')->name(
                        'fetch_layaway_sale_items'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('layaway_sale'))->group(
                function (): void {
                    Route::get('export-layaway-sales/{fileName}', 'exportLayawaySales')->name('export_layaway_sales');
                    Route::get('print-layaway-sale/{saleId}', 'printLayawaySale')->name('print_layaway_sale');
                    Route::get('print-layaway-sale-tax-invoice/{saleId}', 'printSaleTaxInvoice')->name(
                        'print_sale_tax_invoice'
                    );
                    Route::get('print-layaway-sale-digital-invoice/{saleId}', 'printDigitalInvoice')->name(
                        'print_layaway_sale_digital_invoice'
                    );
                }
            );
        });
        Route::controller(CancelLayawaySaleController::class)->name('cancel_layaway_sales.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('cancel_layaway_sale'))->group(
                    function (): void {
                        Route::get('cancel-layaway-sales', 'index')->name('index');
                        Route::get('fetch-cancel-layaway-sales', 'fetchCancelLayawaySales')->name('fetch');
                        Route::get(
                            'fetch-cancel-layaway-sale-items/{saleId}',
                            'fetchCancelLayawaySaleItemsBySaleId'
                        )->name('fetch_cancel_layaway_sale_items');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('cancel_layaway_sale')
                )->group(
                    function (): void {
                        Route::get('export-cancel-layaway-sales/{fileName}', 'exportCancelLayawaySales')->name(
                            'export_cancel_layaway_sales'
                        );
                        Route::get('print-cancel-layaway-sale/{saleId}', 'printCancelLayawaySale')->name(
                            'print_cancel_layaway_sale'
                        );
                        Route::get('print-cancel-layaway-sale-digital-invoice/{saleId}', 'printDigitalInvoice')->name(
                            'print_cancel_layaway_sale_digital_invoice'
                        );
                    }
                );
            }
        );
        Route::controller(CreditSaleController::class)->name('credit_sales.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('credit_sale'))->group(
                function (): void {
                    Route::get('credit-sales', 'index')->name('index');
                    Route::get('fetch-credit-sales', 'fetchPendingCreditSales')->name('fetch');
                    Route::get('fetch-credit-sale-items/{saleId}', 'fetchCreditSaleItemsBySaleId')->name(
                        'fetch_credit_sale_items'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('credit_sale'))->group(
                function (): void {
                    Route::get('export-credit-sales/{fileName}', 'exportCreditSales')->name('export_credit_sales');
                    Route::get('print-credit-sale/{saleId}', 'printCreditSale')->name('print_credit_sale');
                    Route::get('print-credit-sale-tax-invoice/{saleId}', 'printCreditSaleTaxInvoice')->name(
                        'print_credit_sale_tax_invoice'
                    );
                    Route::get('print-credit-sale-digital-invoice/{saleId}', 'printDigitalInvoice')->name(
                        'print_credit_sale_digital_invoice'
                    );
                }
            );
        });
        Route::controller(VoidSaleController::class)->name('void_sales.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('void_sale'))->group(
                function (): void {
                    Route::get('void-sales', 'index')->name('index');
                    Route::get('fetch-void-sales', 'fetchVoidSales')->name('fetch');
                    Route::get('fetch-void-sale-items/{saleId}', 'fetchVoidSaleItemsBySaleId')->name(
                        'fetch_void_sale_items'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('void_sale'))->group(
                function (): void {
                    Route::get('export-void-sale/{fileName}', 'exportVoidSale')->name('export_void_sale');
                    Route::get('print-void-sale-digital-invoice/{saleId}', 'printDigitalInvoice')->name(
                        'print_void_sale_digital_invoice'
                    );
                }
            );
        });
        Route::controller(CreditNoteController::class)->name('credit_notes.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('credit_note'))->group(
                function (): void {
                    Route::get('credit-notes', 'index')->name('index');
                    Route::get('fetch-credit-notes', 'fetchCreditNotes')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('credit_note'))->group(
                function (): void {
                    Route::get('export-credit-notes/{fileName}', 'exportCreditNotes')->name('export_credit_notes');
                    Route::get('print-credit-notes-digital-invoice/{creditNoteId}', 'printDigitalInvoice')->name(
                        'print_credit_notes_digital_invoice'
                    );
                }
            );
        });
        Route::controller(DayCloseReportController::class)->name('day_close_report.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('day_close'))->group(
                function (): void {
                    Route::get('day-close-report', 'index')->name('index');
                    Route::get('fetch-day-close-report', 'fetchDayCloseReport')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('day_close'))->group(
                function (): void {
                    Route::get('export-store-day-close/{fileName}', 'exportStoreDayClose')->name(
                        'export_store_day_close'
                    );
                    Route::get('print-day-close/{id}', 'printDayCloseReport')->name('print_day_close_report');
                    Route::get('fetch-day-close-report/{dayCloseId}', 'fetchDayClosedReportById')->name(
                        'fetch_day_close_report_by_id'
                    );
                }
            );
        });
        Route::controller(VoucherReportController::class)->name('vouchers.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('voucher'))->group(
                function (): void {
                    Route::get('vouchers', 'index')->name('index');
                    Route::get('fetch-vouchers', 'fetchVouchers')->name('fetch');
                    Route::get('fetch-voucher-transaction-details/{voucherId}', 'fetchVoucherTransactionDetails')->name(
                        'fetch_voucher_transaction_details'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('voucher'))->group(
                function (): void {
                    Route::get('export-vouchers/{fileName}', 'exportVouchers')->name('export_vouchers');
                    Route::get('print-voucher-transaction-details/{voucherId}', 'printVoucherTransactionDetails')->name(
                        'print_voucher_transaction_details'
                    );
                }
            );
        });
        Route::controller(ActivityReportController::class)->name('activities.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('activities'))->group(
                function (): void {
                    Route::get('activities', 'index')->name('index');
                    Route::get('fetch-activities', 'fetchActivities')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('activities'))->group(
                function (): void {
                    Route::get('export-activities/{fileName}', 'exportActivities')->name('export_activities');
                    Route::get('print-activities', 'printActivities')->name('print_activities');
                }
            );
        });

        Route::controller(InventoryReportController::class)->name('inventory_reports.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('inventory'))->group(
                function (): void {
                    Route::get('inventory-reports', 'index')->name('index');
                    Route::get('fetch-inventories', 'fetchInventories')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('inventory'))->group(
                function (): void {
                    Route::get('export-inventories/{fileName}', 'exportInventories')->name('export_inventories');
                    Route::get('check-inventory-export-limit', 'checkInventoryExportLimit')->name(
                        'check_inventory_export_limit'
                    );
                }
            );
            Route::get('inventory-report-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });

        Route::controller(StockPositionController::class)->name('stock_positions.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_position'))->group(
                function (): void {
                    Route::get('stock-positions', 'index')->name('index');
                    Route::get('fetch-stock-positions', 'fetchStockPositions')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_position'))->group(
                function (): void {
                    Route::get('export-stock-positions/{fileName}', 'exportStockPositions')->name(
                        'export_stock_positions'
                    );
                    Route::get('check-stock-position-export-limit', 'checkStockPositionExportLimit')->name(
                        'check_stock_position_export_limit'
                    );
                }
            );
        });

        Route::controller(ExternalInventoryReportController::class)->name('external_inventory_reports.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('external_inventory'))->group(
                    function (): void {
                        Route::get('external-inventory-reports', 'index')->name('index');
                        Route::get('fetch-external-inventories', 'fetchExternalInventories')->name('fetch');
                        Route::get('get-stores-warehouses-and-regions', 'getStoresWarehousesAndRegions')->name(
                            'get_stores_warehouses_and_regions'
                        );
                        Route::get(
                            'get-filtered-external-inventory-products',
                            'getFilteredExternalInventoryProducts'
                        )->name('get_filtered_external_inventory_products');
                        Route::get(
                            'get-filtered-external-inventory-categories',
                            'getFilteredExternalInventoryCategories'
                        )->name('get_filtered_external_inventory_categories');
                        Route::get(
                            'get-filtered-external-inventory-brands',
                            'getFilteredExternalInventoryBrands'
                        )->name('get_filtered_external_inventory_brands');
                        Route::get(
                            'get-filtered-external-inventory-sizes',
                            'getFilteredExternalInventorySizes'
                        )->name('get_filtered_external_inventory_sizes');
                        Route::get(
                            'get-filtered-external-inventory-attributes',
                            'getFilteredExternalInventoryAttributes'
                        )->name('get_filtered_external_inventory_attributes');
                        Route::get(
                            'get-filtered-external-inventory-colors',
                            'getFilteredExternalInventoryColors'
                        )->name('get_filtered_external_inventory_colors');
                        Route::get(
                            'get-filtered-external-inventory-departments',
                            'getFilteredExternalInventoryDepartments'
                        )->name('get_filtered_external_inventory_departments');
                        Route::get(
                            'get-filtered-external-inventory-articleNumbers',
                            'getFilteredExternalInventoryArticleNumbers'
                        )->name('get_filtered_external_inventory_articleNumbers');
                        Route::get(
                            'get-filtered-external-inventory-tags',
                            'getFilteredExternalInventoryTags'
                        )->name('get_filtered_external_inventory_tags');
                        Route::get(
                            'get-filtered-external-inventory-styles',
                            'getFilteredExternalInventoryStyles'
                        )->name('get_filtered_external_inventory_styles');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('external_inventory'))->group(
                    function (): void {
                        Route::get('export-external-inventories/{fileName}', 'exportExternalInventories')->name(
                            'export_external_inventories'
                        );
                    }
                );
            }
        );
        Route::controller(CashMovementReportController::class)->name('cash_movements.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('cash_movement'))->group(
                    function (): void {
                        Route::get('cash-movements', 'index')->name('index');
                        Route::get('fetch-cash-movements', 'fetchCashMovements')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('cash_movement'))->group(
                    function (): void {
                        Route::get('export-cash-movements/{fileName}', 'exportCashMovements')->name(
                            'export_cash_movements'
                        );
                    }
                );
            }
        );
        Route::controller(ManualNotificationController::class)->name('manual_notifications.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('manual_notification'))->group(
                    function (): void {
                        Route::get('manual-notifications', 'index')->name('index');
                        Route::get('fetch-manual-notifications', 'fetchManualNotifications')->name('fetch');
                        Route::get(
                            'fetch-manual-notification-details/{manualNotificationId}',
                            'fetchDetailsByManualNotificationId'
                        )->name('fetch_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('manual_notification'))->group(
                    function (): void {
                        Route::get('manual-notifications/create', 'create')->name('create');
                        Route::post('manual-notifications', 'store')->name('store');
                    }
                );
            }
        );
        Route::controller(BannerController::class)->name('banners.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('banner'))->group(
                function (): void {
                    Route::get('banners', 'index')->name('index');
                    Route::get('fetch-banners', 'fetchBanners')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('banner'))->group(
                function (): void {
                    Route::get('banners/create', 'create')->name('create');
                    Route::post('banners', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('banner'))->group(
                function (): void {
                    Route::get('banners/{bannerId}/edit', 'edit')->name('edit');
                    Route::put('banners/{bannerId}', 'update')->name('update');
                }
            );
            Route::post('banners/{bannerId}/set-status/{status}', 'setStatus')->name('update_status');
            Route::get('banners-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(NotificationController::class)->name('notifications.')->group(function (): void {
            Route::get('fetch-notifications', 'fetchNotifications')->name('fetch');
            Route::post('mark-all-as-read', 'markAllAsRead')->name('mark_all_as_read');
            Route::get('fetch-read-notifications', 'fetchReadNotifications')->name('fetch_read_notification');
            Route::post('mark-as-read', 'markAsRead')->name('mark_as_read');
            Route::post('mark-as-unread', 'markAsUnRead')->name('mark_as_unread');
        });
        Route::controller(PaymentTypeReportController::class)->name('payment_type_report.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('payment_type_report'))->group(
                    function (): void {
                        Route::get('payment-types-report', 'index')->name('index');
                        Route::get('fetch-payment-types-report', 'fetchPaymentTypeReport')->name('fetch');
                        Route::get('fetch-transactions', 'fetchTransactions')->name('fetch_transactions');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('payment_type_report')
                )->group(
                    function (): void {
                        Route::get('export-payment-types/{fileName}', 'exportPaymentTypes')->name(
                            'export_payment_types'
                        );
                    }
                );
            }
        );
        Route::controller(PromoterCommissionController::class)->name('promoter_commission.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('commission'))->group(
                    function (): void {
                        Route::get('promoter-commission', 'index')->name('index');
                        Route::get('fetch-promoter-commission', 'fetCommissionsByPromoters')->name('fetch');
                        Route::post('get-location-wise-promoters', 'getLocationWisePromoters')->name('get_promoters');
                        Route::get(
                            'get-promoter-commission-details/{promoterCommissionId}',
                            'fetchPromoterCommissionDetails'
                        )->name('get_promoter_commission_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('commission'))->group(
                    function (): void {
                        Route::get('export-promoter-commission/{fileName}', 'exportCommissionByPromoters')->name(
                            'exportCommissionByPromoters'
                        );
                        Route::get(
                            'export-promoter-commission-details/{promoterCommissionId}/{fileName}',
                            'exportPromoterCommissionDetails'
                        );
                        Route::get(
                            'print-promoter-commission-details/{promoterCommissionId}',
                            'printPromoterCommissionDetails'
                        )->name('print_promoter_commission_details');
                        Route::get('print-promoter-commission-report', 'printPromoterCommission')->name(
                            'print_promoter_commission'
                        );
                    }
                );
            }
        );
        Route::controller(StockTakeController::class)->name('stock_takes.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_take'))->group(
                function (): void {
                    Route::get('stock-takes', 'index')->name('index');
                    Route::get('fetch-stock-takes', 'fetchStockTakes')->name('fetch_stock_takes');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_take'))->group(
                function (): void {
                    Route::get('export-stock-takes/{fileName}', 'exportStockTakes')->name('export_stock_takes');
                }
            );
            Route::get('export-stock-take-products/{stockTakeId}/{fileName}', 'exportStockTakeProducts');
        });
        Route::controller(CustomReportController::class)->name('custom_reports.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('custom_report'))->group(
                function (): void {
                    Route::get('custom-reports', 'index')->name('index');
                    Route::get('stock-movement-report-print', 'stockMovementReportPrint')->name(
                        'stock_movement_report_print'
                    );
                    Route::get('sale-hour-print', 'saleHourPrint')->name('sale_hour_print');
                    Route::get('print-sales-collection', 'print')->name('print_sales_collection');
                    Route::get('print-void-report', 'printVoidReport')->name('print_void_report');
                    Route::get('export-sales-collection/{filename}', 'exportSaleCollection');
                    Route::get('export-void-report/{filename}', 'exportVoidReport');
                    Route::get('export-custom-stock-movement/{filename}', 'exportStockMovementReport')->name(
                        'stock_movement_report_export'
                    );
                    Route::get('export-sale-hour/{filename}', 'exportSaleHour')->name('sale_hour_export');
                    Route::get('print-sales-exchange', 'printExchange')->name('print_sales_exchange');
                    Route::get('export-sales-exchange/{filename}', 'exportExchange')->name('export_sales_exchange');
                    Route::get('print-top-twenty', 'printTopTwenty')->name('print_top_twenty');
                    Route::get('print-worst-twenty', 'printWorstTwenty')->name('print_worst_twenty');
                    Route::get('print-general-sales', 'printGeneralSale')->name('print_general_sales_report');
                    Route::get('print-promoter-commission', 'printPromoterCommission')->name(
                        'print_promoter_commission_report'
                    );
                    Route::get('export-general-sales-report/{filename}', 'exportGeneralSalesReport');
                    Route::get('print-stock-card', 'printStockCard')->name('print_stock_card');
                    Route::get('print-stock-summary', 'printStockSummary')->name('print_stock_summary');
                    Route::get('print-cash-movement', 'printCashMovement')->name('print_cash_movements');
                    Route::get('export-cash-movement-report/{filename}', 'exportCashMovementsReport');
                    Route::get('print-sales-by-promoter', 'printSalesByPromoter')->name('print_sales_by_promoter');
                    Route::get('print-sale-return', 'printSaleReturn')->name('print_sale_return');
                    Route::get('export-sale-return/{filename}', 'exportSaleReturn')->name('export_sale_return');
                    Route::get('print-stock-transfer', 'printStockTransfer')->name('print_stock_transfer');
                    Route::get('print-stock-transfer-discrepancy', 'printStockTransferDiscrepancy')->name(
                        'print_stock_transfer_discrepancy'
                    );
                    Route::get('export-stock-transfer/{filename}', 'exportStockTransfer')->name(
                        'export_stock_transfer'
                    );
                    Route::get('export-stock-transfer-discrepancy/{filename}', 'exportStockTransferDiscrepancy')->name(
                        'export_stock_transfer_discrepancy'
                    );
                    Route::get('print-goods-received-note', 'printGoodsReceivedNote')->name(
                        'print_goods_received_note'
                    );
                    Route::get('print-sales-return-and-exchange', 'printReturnAndExchange')->name(
                        'print_sales_return_and_exchange'
                    );
                    Route::get('export-sales-return-and-exchange/{filename}', 'exportReturnAndExchange')->name(
                        'export_sales_return_and_exchange'
                    );
                    Route::get('export-stock-card/{filename}', 'exportStockCard');
                    Route::get('export-goods-received-note-report/{filename}', 'exportGoodsReceivedNote');
                    Route::get('export-top-twenty/{filename}', 'exportTopTwenty');
                    Route::get('export-worst-twenty/{filename}', 'exportWorstTwenty');
                    Route::get('export-sales-by-promoter/{filename}', 'exportSalesByPromoter');
                    Route::get('export-promoter-commission-report/{filename}', 'exportPromoterCommission');
                    Route::get('export-stock-summary-report/{filename}', 'exportStockSummaryReport');
                    Route::get('print-suspend-and-resume', 'printSuspendAndResume')->name('print_suspend_and_resume');
                    Route::get('export-suspend-and-resume/{filename}', 'exportSuspendAndResume');
                    Route::get('print-discount-report', 'printDiscount')->name('print_discount_report');
                    Route::get('export-discount-report/{filename}', 'exportDiscountReport');
                    Route::get('stock-adjustment-report', 'printStockAdjustment')->name('print_stock_adjustment');
                    Route::get('export-stock-adjustment-report/{filename}', 'exportStockAdjustment');
                    Route::get('get-discount-type-reports', 'getDiscountTypeReports')->name(
                        'get_discount_type_reports'
                    );
                    Route::get('get-sale-discount-type-reports', 'getSaleDiscountTypeReports')->name(
                        'get_sale_discount_type_reports'
                    );
                    Route::get('print-discount-summary-report', 'printDiscountSummaryReport')->name(
                        'print_discount_summary_report'
                    );
                    Route::get('export-discount-summary-report/{filename}', 'exportDiscountSummaryReport');
                    Route::get('print-sales-overall-report', 'printSaleOverallByStore')->name(
                        'print-sales-overall-report'
                    );
                    Route::get('export-sales-overall-report/{filename}', 'exportSaleOverallByStore');
                    Route::get('print-inter-company', 'printInterCompany')->name('print_inter_company');
                    Route::get('export-inter-company/{filename}', 'exportInterCompany')->name('export_inter_company');
                    Route::get('print-order-report', 'printOrderReport')->name('print_order_report');
                    Route::get('export-order-report/{filename}', 'exportOrderReport')->name('export_order_report');
                    Route::get('print-inter-company-invoice', 'printInterCompanyInvoiceReport')->name(
                        'print_inter_company_invoice'
                    );
                    Route::get('export-inter-company-invoice/{filename}', 'exportInterCompanyInvoiceReport')->name(
                        'export_inter_company_invoice'
                    );
                    Route::get('seasonal-sales-print', 'seasonalSalesPrint')->name('seasonal_sales_print');
                    Route::get('export-seasonal-sales/{filename}', 'exportSeasonalSales')->name(
                        'export_seasonal_sales'
                    );
                    Route::get('layaway-sales-print', 'layawaySalesPrint')->name('layaway_sales_print');
                    Route::get('layaway-sales-export/{filename}', 'layawaySalesExport')->name('layaway_sales_export');
                    Route::get('credit-sales-print', 'creditSalesPrint')->name('credit_sales_print');
                    Route::get('credit-sales-export/{filename}', 'creditSalesExport')->name('credit_sales_export');
                    Route::get('accumulated-sell-through-export/{filename}', 'exportAccumulatedReport')->name(
                        'export_accumulated_report'
                    );

                    Route::get('print-stock-transfer-status-summary', 'printStockTransfersStatusSummary')->name(
                        'print_stock_transfer_status_summary'
                    );
                    Route::get('export-stock-transfer-status-summary/{filename}', 'exportStockTransfersStatusSummary');

                    Route::get('print-stock-summary-by-module', 'printStockSummaryByModule')->name(
                        'print_stock_summary_by_module'
                    );

                    Route::get('export-stock-summary-by-module/{filename}', 'exportStockSummaryByModule')->name(
                        'export_stock_summary_by_module'
                    );
                }
            );
        });
        Route::controller(QuantitySoldReportController::class)->name('quantity_sold_reports.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('quantity_sold'))->group(
                    function (): void {
                        Route::get('quantity-sold-report', 'index')->name('index');
                        Route::get('fetch-quantity-sold', 'fetchQuantitySold')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('quantity_sold'))->group(
                    function (): void {
                        Route::get('print-quantity-sold', 'printQuantitySold')->name('print');
                        Route::get('export-quantity-sold-report/{filename}', 'exportQuantitySold')->name('export');
                    }
                );
            }
        );
        Route::controller(GiftCardController::class)->name('gift_cards.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('gift_card'))->group(
                function (): void {
                    Route::get('gift-cards', 'index')->name('index');
                    Route::get('fetch-gift-cards', 'fetchGiftCard')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('gift_card'))->group(
                function (): void {
                    Route::get('upload-gift-cards', 'uploadGiftCardView')->name('upload_gift_card_view');
                    Route::post('upload', 'upload')->name('upload');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('gift_card'))->group(
                function (): void {
                    Route::get('export-gift-cards/{fileName}', 'exportGiftCards')->name('export_gift_cards');
                }
            );
        });
        Route::controller(BookingPaymentReportController::class)->name('booking_payments.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('booking_payment'))->group(
                    function (): void {
                        Route::get('booking-payments', 'index')->name('index');
                        Route::get('fetch-booking-payments', 'fetchBookingPayments')->name('fetch');
                        Route::get(
                            'fetch-booking-payments/{bookingPaymentId}',
                            'fetchBookingPaymentsDetailsById'
                        )->name('fetch_booking_payments_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('booking_payment'))->group(
                    function (): void {
                        Route::get('export-booking-payments/{fileName}', 'exportBookingPayments')->name(
                            'export_booking_payments'
                        );
                        Route::get('print-booking-payment/{bookingPaymentId}', 'printBookingPayment')->name(
                            'print_booking_payment'
                        );
                        Route::get(
                            'print-booking-payment-digital-invoice/{bookingPaymentId}',
                            'printDigitalInvoice'
                        )->name('print_booking_payment_digital_invoice');
                    }
                );
            }
        );
        Route::controller(SaleExchangesReportController::class)->name('sale_exchanges.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_exchange'))->group(
                    function (): void {
                        Route::get('sale-exchanges', 'index')->name('index');
                        Route::get('fetch-sale-exchanges', 'fetchSaleExchanges')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_exchange'))->group(
                    function (): void {
                        Route::get('export-sale-exchanges/{fileName}', 'export')->name('export');
                    }
                );
            }
        );
        Route::controller(BarCodeController::class)->name('barcode_prints.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('barcode'))->group(
                function (): void {
                    Route::get('barcodes', 'index')->name('index');
                    Route::get('fetch-barcode-records', 'fetchBarcodeRecords')->name('fetch_barcodes');
                    Route::get('get-export-records-pending-status-counts', 'getPendingExportRecordCount')->name(
                        'get_pending_export_record_count'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('barcode'))->group(
                function (): void {
                    Route::get('barcodes/create', 'create')->name('create');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('barcode'))->group(
                function (): void {
                    Route::get('export-barcode-records/{fileName}', 'ExportBarcodeRecords')->name('export_barcode');
                    Route::post('barcodes-print', 'productsBarcodePrint')->name('products_barcode_print');
                    Route::post('barcodes-print-download', 'downloadPdfEntry')->name('download_pdf_entry');
                    Route::post('barcodes-print-manual', 'printTheBarcodeByManualProcess')->name(
                        'products_barcode_print_manual'
                    );
                    Route::get('view-print/{fileName}', 'viewPdf')->name('view_pdf');
                    Route::get('verify-file/{fileName}', 'isPDFFileExists')->name('is_pdf_file_exists');
                }
            );
        });
        Route::controller(PosAdvertisementController::class)->name('pos_advertisements.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('pos_advertisement'))->group(
                    function (): void {
                        Route::get('pos-advertisements', 'index')->name('index');
                        Route::get('fetch-pos-advertisements', 'fetchPosAdvertisement')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('pos_advertisement'))->group(
                    function (): void {
                        Route::get('pos-advertisements/create', 'create')->name('create');
                        Route::post('pos-advertisements', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('pos_advertisement'))->group(
                    function (): void {
                        Route::get('pos-advertisements/{posAdvertisementId}/edit', 'edit')->name('edit');
                        Route::put('pos-advertisements/{posAdvertisementId}/update', 'update')->name('update');
                        Route::post('pos-advertisements/{posAdvertisementId}/set-status/{status}', 'setStatus')->name(
                            'set_status'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('pos_advertisement'))->group(
                    function (): void {
                        Route::get('export-pos-advertisements/{fileName}', 'exportPosAdvertisement')->name(
                            'export_pos_advertisements'
                        );
                    }
                );
            }
        );
        Route::controller(PosAdminController::class)->name('pos_admin.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('app_release'))->group(
                function (): void {
                    Route::get('app-releases', 'index')->name('index');
                }
            );
        });
        Route::controller(OpenCounterController::class)->name('open_counter_reports.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('open_counter'))->group(
                    function (): void {
                        Route::get('open-counter-reports', 'index')->name('index');
                        Route::get('open-counters', 'fetchOpenCounters')->name('fetch');
                        Route::get('fetch-open-counter-sales/{counterUpdateId}', 'fetchOpenCounterSales')->name(
                            'sales_fetch'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('open_counter'))->group(
                    function (): void {
                        Route::get('export-open-counter/{fileName}', 'exportOpenCounters')->name(
                            'export_open_counters'
                        );
                    }
                );
                Route::get('open-counters-sales/{counterUpdateId}', 'openCounterSales')->name('sales_view');
            }
        );
        Route::controller(RegionController::class)->name('regions.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('region'))->group(
                function (): void {
                    Route::get('regions', 'index')->name('index');
                    Route::get('fetch-regions', 'fetchRegions')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('region'))->group(
                function (): void {
                    Route::inertia('regions/create', 'regions/Manage')->name('create');
                    Route::post('regions', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('region'))->group(
                function (): void {
                    Route::get('regions/{regionId}/edit', 'edit')->name('edit');
                    Route::put('regions/{regionId}/update', 'update')->name('update');
                    Route::get('regions/{regionId}/resend-verification-email', 'resendVerificationEmail')->name(
                        'resend_verification_email'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('region'))->group(
                function (): void {
                    Route::get('export-regions/{fileName}', 'exportRegions')->name('export_regions');
                }
            );
            Route::post('region/add-new-from-location', 'addNewFromLocation')->name('store_from_location');
        });
        Route::controller(VendorController::class)->name('vendors.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('vendor'))->group(
                function (): void {
                    Route::get('vendors', 'index')->name('index');
                    Route::get('fetch-vendors', 'fetchVendors')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('vendor'))->group(
                function (): void {
                    Route::inertia('vendors/create', 'vendors/Manage')->name('create');
                    Route::post('vendors', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('vendor'))->group(
                function (): void {
                    Route::get('vendors/{vendorId}/edit', 'edit')->name('edit');
                    Route::put('vendors/{vendorId}', 'update')->name('update');
                    Route::get('vendors/{vendorId}/resend-verification-email', 'resendVerificationEmail')->name(
                        'resend_verification_email'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('vendor'))->group(
                function (): void {
                    Route::get('export-vendors/{fileName}', 'exportVendors')->name('export_vendors');
                }
            );
            Route::get('get-vendors-list', 'getVendorsList')->name('get_vendors_list');
        });
        Route::controller(PurchaseOrderController::class)->name('purchase_orders.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('purchase_order'))->group(
                function (): void {
                    Route::get('purchase-orders', 'index')->name('index');
                    Route::get('fetch-purchase-orders', 'fetchPurchaseOrders')->name('fetch');
                    Route::get(
                        'fetch-purchase-order-items/{purchaseOrderId}',
                        'fetchPurchaseOrderItemByPurchaseOrderId'
                    )->name('fetch_purchase_order_items');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('purchase_order'))->group(
                function (): void {
                    Route::get('purchase-orders/create/{orderType}', 'create')->name('create')->where(
                        'orderType',
                        '[0-9]+'
                    );
                    Route::post('purchase-orders', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('purchase_order'))->group(
                function (): void {
                    Route::get('purchase-orders/{purchaseOrderId}/edit', 'edit')->name('edit');
                    Route::post('purchase-orders/{purchaseOrderId}/update', 'update')->name('update');
                    Route::post('purchase-orders/{purchaseOrderId}/cancel', 'cancel')->name('cancel');
                    Route::post('purchase-orders/{purchaseOrderId}/approve', 'approve')->name('approve');
                    Route::post('purchase-orders/{purchaseOrderId}/reject', 'reject')->name('reject');
                    Route::post('purchase-orders/{purchaseOrderId}/open', 'open')->name('open');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('purchase_order'))->group(
                function (): void {
                    Route::get('export-purchase-order-items/{purchaseOrderId}/{fileName}', 'exportPurchaseOrderItems');
                    Route::get('purchase-order/{purchaseOrderId}/print', 'print')->name('print');
                    Route::get('export-purchase-orders/{fileName}', 'exportPurchaseOrders');
                }
            );
        });
        Route::controller(ExternalLocationController::class)->name('external_locations.')->group(
            function (): void {
                Route::get('get-external-locations/{externalCompanyId}', 'getExternalLocations')->name(
                    'get_external_locations'
                );
            }
        );
        Route::controller(PurchaseOrderFulfillmentController::class)->name('purchase_order_fulfillments.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('purchase_order'))->group(
                    function (): void {
                        Route::get('purchase-order-fulfillments/{purchaseOrderId}', 'deliveryOrder')->name(
                            'delivery_order'
                        )->where('purchaseOrderId', '[0-9]+');
                        Route::get('fetch-purchase-order-fulfillments', 'fetchPurchaseOrderFulfillments')->name(
                            'fetch'
                        );
                        Route::get(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/delivery-note',
                            'deliveryNote'
                        )->name('delivery_note');
                        Route::get(
                            'fetch-purchase-order-fulfillment-items/{purchaseOrderFulfillmentId}',
                            'fetchPurchaseOrderFulfillmentItemById'
                        )->name('fetch_purchase_order_fulfillment_items');
                        Route::get('purchase-order-fulfillments', 'deliveryOrders')->name('delivery_orders');
                        Route::get('fetch-purchase-delivery-orders', 'fetchPurchaseDeliveryOrders')->name(
                            'fetch_delivery_orders'
                        );
                        Route::get(
                            'fetch-partially-receive-fulfillment/{purchaseOrderFulfillmentId}',
                            'fetchPartiallyReceiveFulfillment'
                        )->name('fetch_partially_receive_fulfillment');
                        Route::get(
                            'fetch-partially-receive-fulfillment-items/{partialReceiveId}',
                            'fetchPartiallyReceiveFulfillmentItems'
                        )->name('fetch_partially_receive_fulfillment_items');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('purchase_order'))->group(
                    function (): void {
                        Route::get('purchase-order-fulfillments/{purchaseOrderId}/shipping-details', 'shippingDetails')
                            ->name('shipping_details');
                        Route::put(
                            'purchase-order-fulfillments/{purchaseOrderId}/add-shipping-details',
                            'addShippingDetails'
                        )->name('add_shipping_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('purchase_order'))->group(
                    function (): void {
                        Route::get('purchase-order-fulfillments/{purchaseOrderFulfillmentId}/edit', 'edit')->name(
                            'edit'
                        );
                        Route::put('purchase-order-fulfillments/{purchaseOrderFulfillmentId}/update', 'update')->name(
                            'update'
                        );
                        Route::put(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/shipped',
                            'shipped'
                        )->name('shipped');
                        Route::put(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/mark-as-received',
                            'markAsReceived'
                        )->name('mark_as_received');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/mark-as-cancel',
                            'markAsCancel'
                        )->name('mark_as_cancel');
                        Route::post('purchase-order-fulfillments/{purchaseOrderFulfillmentId}/closed', 'closed')->name(
                            'closed'
                        );
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/mark-as-open',
                            'markAsOpen'
                        )->name('mark_as_open');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/discrepancy',
                            'discrepancy'
                        )->name('discrepancy');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/discrepancy-proof',
                            'discrepancyProof'
                        )->name('discrepancy_proof');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/update-received-quantities',
                            'updateReceivedQuantities'
                        )->name('update_received_quantities');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/set-received-quantity-same-as-quantity',
                            'setReceivedQuantitySameAsQuantity'
                        )->name('set_received_same_quantities');
                        Route::post(
                            'purchase-order-fulfillment-delivery-note-item-remarks/{purchaseOrderFulfillmentItemId}',
                            'purchaseOrderDeliveryNoteItemRemarks'
                        )
                            ->name('purchase_order_fulfillment_delivery_note_item_remarks');
                        Route::get(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/discrepancy-closed',
                            'discrepancyClosedDeliveryOrder'
                        )->name('discrepancy_closed_delivery_order');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/update-additional-items',
                            'updateAdditionalItems'
                        )
                            ->name('update_additional_items');
                        Route::put(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/closed-discrepancy',
                            'closeDiscrepancy'
                        )->name('closed_discrepancy');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/partial-receive',
                            'partialReceive'
                        )->name('partial_receive');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/update-batch-details',
                            'updateBatchDetails'
                        )->name('update_batch_details');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/update-discrepancy-batch-details',
                            'updateDiscrepancyBatchDetails'
                        )->name('update_discrepancy_batch_details');
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/delete-batch-details',
                            'deleteBatchDetails'
                        )->name('delete_batch_details');
                        Route::post(
                            'purchase-order-fulfillments/{partialReceiveId}/approved',
                            'partialReceiveApproved'
                        )->name('partial_receive_approved');
                        Route::post(
                            'purchase-order-fulfillments/{partialReceiveId}/completed',
                            'partialReceiveCompleted'
                        )->name('partial_receive_completed');
                        Route::post(
                            'purchase-order-fulfillments/{partialReceiveId}/cancelled',
                            'partialReceiveCancelled'
                        )->name('partial_receive_cancelled');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('purchase_order'))->group(
                    function (): void {
                        Route::get('purchase-order-fulfillments/{purchaseOrderFulfillmentId}/print', 'print')->name(
                            'print'
                        );
                        Route::post(
                            'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/{transferItemIds}print-box-sticker',
                            'printBoxSticker'
                        )->name('print_box_sticker');
                    }
                );
                Route::get(
                    '/export-purchase-order-fulfillment-items/{purchaseOrderFulfillmentId}/{fileName}',
                    'exportPurchaseOrderFulfillmentItems'
                );
                Route::get(
                    'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/remove-discrepancy-proof',
                    'removeDiscrepancyProof'
                )->name('remove_discrepancy_proof');
                Route::get(
                    'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/remove-additional-item',
                    'removeAdditionalItem'
                )
                    ->name('remove_additional_item');
            }
        );
        Route::controller(PurchaseOrderInvoiceController::class)->name('purchase_order_invoices.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('purchase_order_invoice')
                )->group(
                    function (): void {
                        Route::get('purchase-order-invoices', 'index')->name('index');
                        Route::get('fetch-purchase-order-invoices', 'fetchPurchaseOrderInvoices')->name('fetch');
                        Route::get(
                            'purchase-order-invoices/{purchaseOrderId}/fulfillment-details',
                            'fulfillmentDetails'
                        )->name('fulfillment_details');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('purchase_order_invoice')
                )->group(
                    function (): void {
                        Route::get('purchase-order-invoices/create', 'create')->name('create');
                        Route::post('purchase-order-invoices/store', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('purchase_order_invoice')
                )->group(
                    function (): void {
                        Route::get(
                            'purchase-order-invoices/{purchaseOrderInvoiceId}/{purchaseOrderId}/edit',
                            'edit'
                        )->name('edit');
                        Route::post(
                            'purchase-order-invoices/{purchaseOrderFulfillmentId}/{purchaseOrderInvoiceId}/add-invoice-item',
                            'updateInvoiceId'
                        )->name('update_invoice_id');
                        Route::post(
                            'purchase-order-invoices/{purchaseOrderFulfillmentId}/{purchaseOrderInvoiceId}/remove-invoice-item',
                            'removeInvoiceId'
                        )->name('remove_update_invoice_id');
                        Route::post('purchase-order-invoices/{purchaseOrderInvoiceId}/cancel', 'cancel')->name(
                            'cancel'
                        );
                        Route::post('purchase-order-invoices/{purchaseOrderInvoiceId}/sent', 'sent')->name('sent');
                        Route::post('purchase-order-invoices/{purchaseOrderInvoiceId}/paid', 'paid')->name('paid');
                        Route::post(
                            'purchase-order-invoices/{purchaseOrderInvoiceId}/mark-as-received',
                            'markAsReceived'
                        )->name('mark_as_received');
                        Route::get(
                            'refresh-prices/{purchaseOrderId}',
                            'refreshPrice'
                        )->name('refresh_purchase_cost');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('purchase_order_invoice')
                )->group(
                    function (): void {
                        Route::get('purchase-order-invoices/{purchaseOrderInvoiceId}/print', 'print')->name('print');
                    }
                );
            }
        );
        Route::controller(SellThroughAggregateReportController::class)->name('sell_through_aggregate_reports.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sell_through'))->group(
                    function (): void {
                        Route::get('sell-through-aggregate-report', 'index')->name('index');
                        Route::get('fetch-sell-through-aggregate-report', 'fetchSellThroughDetails')->name(
                            'fetch_details'
                        );
                        Route::get('fetch-balance-details-by-upc/{productId}', 'fetchBalanceDetailsByUpc')->name(
                            'fetch_balance_details_by_upc'
                        );
                        Route::get('fetch-sold-details-by-upc/{productId}', 'fetchSoldDetailsByUpc')->name(
                            'fetch_sold_details_by_upc'
                        );
                        Route::get('fetch-received-details-by-upc/{productId}', 'fetchReceivedDetailsByUpc')->name(
                            'fetch_received_details_by_upc'
                        );

                        Route::get('fetch-balance-details-by-color/{colorId}', 'fetchBalanceDetailsByColor')->name(
                            'fetch_balance_details_by_color'
                        );
                        Route::get('fetch-sold-details-by-color/{colorId}', 'fetchSoldDetailsByColor')->name(
                            'fetch_sold_details_by_color'
                        );
                        Route::get('fetch-received-details-by-color/{colorId}', 'fetchReceivedDetailsByColor')->name(
                            'fetch_received_details_by_color'
                        );

                        Route::get('fetch-balance-details-by-size/{sizeId}', 'fetchBalanceDetailsBySize')->name(
                            'fetch_balance_details_by_size'
                        );
                        Route::get('fetch-sold-details-by-size/{sizeId}', 'fetchSoldDetailsBySize')->name(
                            'fetch_sold_details_by_size'
                        );
                        Route::get('fetch-received-details-by-size/{sizeId}', 'fetchReceivedDetailsBySize')->name(
                            'fetch_received_details_by_size'
                        );

                        Route::get('fetch-balance-details-by-style/{styleId}', 'fetchBalanceDetailsByStyle')->name(
                            'fetch_balance_details_by_style'
                        );
                        Route::get('fetch-sold-details-by-style/{styleId}', 'fetchSoldDetailsByStyle')->name(
                            'fetch_sold_details_by_style'
                        );
                        Route::get('fetch-received-details-by-style/{styleId}', 'fetchReceivedDetailsByStyle')->name(
                            'fetch_received_details_by_style'
                        );

                        Route::get('fetch-balance-details-by-brand/{brandId}', 'fetchBalanceDetailsByBrand')->name(
                            'fetch_balance_details_by_brand'
                        );
                        Route::get('fetch-sold-details-by-brand/{brandId}', 'fetchSoldDetailsByBrand')->name(
                            'fetch_sold_details_by_brand'
                        );
                        Route::get('fetch-received-details-by-brand/{brandId}', 'fetchReceivedDetailsByBrand')->name(
                            'fetch_received_details_by_brand'
                        );

                        Route::get(
                            'fetch-balance-details-by-location/{locationId}',
                            'fetchBalanceDetailsByLocation'
                        )->name('fetch_balance_details_by_location');
                        Route::get('fetch-sold-details-by-location/{locationId}', 'fetchSoldDetailsByLocation')->name(
                            'fetch_sold_details_by_location'
                        );
                        Route::get(
                            'fetch-received-details-by-location/{locationId}',
                            'fetchReceivedDetailsByLocation'
                        )->name('fetch_received_details_by_location');

                        Route::get(
                            'fetch-balance-details-by-department/{departmentId}',
                            'fetchBalanceDetailsByDepartment'
                        )->name('fetch_balance_details_by_department');
                        Route::get(
                            'fetch-sold-details-by-department/{departmentId}',
                            'fetchSoldDetailsByDepartment'
                        )->name('fetch_sold_details_by_department');
                        Route::get(
                            'fetch-received-details-by-department/{departmentId}',
                            'fetchReceivedDetailsByDepartment'
                        )->name('fetch_received_details_by_department');

                        Route::get(
                            'fetch-balance-details-by-category/{categoryId}',
                            'fetchBalanceDetailsByCategory'
                        )->name('fetch_balance_details_by_category');
                        Route::get(
                            'fetch-sold-details-by-category/{categoryId}',
                            'fetchSoldDetailsByCategory'
                        )->name('fetch_sold_details_by_category');
                        Route::get(
                            'fetch-received-details-by-category/{categoryId}',
                            'fetchReceivedDetailsByCategory'
                        )->name('fetch_received_details_by_category');

                        Route::get(
                            'fetch-balance-details-by-article-number/{articleNumber}',
                            'fetchBalanceDetailsByArticleNumber'
                        )->name('fetch_balance_details_by_article_number');
                        Route::get(
                            'fetch-sold-details-by-article-number/{articleNumber}',
                            'fetchSoldDetailsByArticleNumber'
                        )->name('fetch_sold_details_by_article_number');
                        Route::get(
                            'fetch-received-details-by-article-number/{articleNumber}',
                            'fetchReceivedDetailsByArticleNumber'
                        )->name('fetch_received_details_by_article_number');

                        Route::get(
                            'fetch-balance-details-by-attribute/{attributeId}',
                            'fetchBalanceDetailsByAttribute'
                        )->name('fetch_balance_details_by_attribute');
                        Route::get(
                            'fetch-sold-details-by-attribute/{attributeId}',
                            'fetchSoldDetailsByAttribute'
                        )->name('fetch_sold_details_by_attribute');
                        Route::get(
                            'fetch-received-details-by-attribute/{attributeId}',
                            'fetchReceivedDetailsByAttribute'
                        )->name('fetch_received_details_by_attribute');

                        Route::get('sell-through-aggregate-get-latest-data-sync', 'getLatestDataSync')->name(
                            'get_latest_data_sync'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sell_through'))->group(
                    function (): void {
                        Route::get('print-sell-through-aggregate-report', 'printSellThroughAggregateDetails')->name(
                            'print_details'
                        );
                        Route::get(
                            'export-sell-through-aggregate-report/{filename}',
                            'exportSellThroughAggregateDetails'
                        )->name('export_details');
                        Route::get(
                            'export-balance-details-by-article-number/{filename}',
                            'exportBalanceDetailsByArticleNumber'
                        )->name('export_balance_details_by_article_number');
                        Route::get(
                            'print-balance-details-by-article-number',
                            'printBalanceDetailsByArticleNumber'
                        )->name('print_balance_details_by_article_number');
                        Route::get(
                            'export-sold-details-by-article-number/{filename}',
                            'exportSoldDetailsByArticleNumber'
                        )->name('export_sold_details_by_article_number');
                        Route::get(
                            'print-sold-details-by-article-number',
                            'printSoldDetailsByArticleNumber'
                        )->name('print_sold_details_by_article_number');
                        Route::get(
                            'export-received-details-by-article-number/{filename}',
                            'exportReceivedDetailsByArticleNumber'
                        )->name('export_received_details_by_article_number');
                        Route::get(
                            'print-received-details-by-article-number',
                            'printReceivedDetailsByArticleNumber'
                        )->name('print_received_details_by_article_number');

                        Route::get(
                            'export-balance-details-by-size/{filename}',
                            'exportBalanceDetailsBySize'
                        )->name('export_balance_details_by_size');
                        Route::get(
                            'print-balance-details-by-size',
                            'printBalanceDetailsBySize'
                        )->name('print_balance_details_by_size');
                        Route::get(
                            'export-sold-details-by-size/{filename}',
                            'exportSoldDetailsBySize'
                        )->name('export_sold_details_by_size');
                        Route::get(
                            'print-sold-details-by-size',
                            'printSoldDetailsBySize'
                        )->name('print_sold_details_by_size');
                        Route::get(
                            'export-received-details-by-size/{filename}',
                            'exportReceivedDetailsBySize'
                        )->name('export_received_details_by_size');
                        Route::get(
                            'print-received-details-by-size',
                            'printReceivedDetailsBySize'
                        )->name('print_received_details_by_size');
                        Route::get(
                            'export-balance-details-by-style/{filename}',
                            'exportBalanceDetailsByStyle'
                        )->name('export_balance_details_by_style');
                        Route::get(
                            'print-balance-details-by-style',
                            'printBalanceDetailsByStyle'
                        )->name('print_balance_details_by_style');
                        Route::get(
                            'export-sold-details-by-style/{filename}',
                            'exportSoldDetailsByStyle'
                        )->name('export_sold_details_by_style');
                        Route::get(
                            'print-sold-details-by-style',
                            'printSoldDetailsByStyle'
                        )->name('print_sold_details_by_style');
                        Route::get(
                            'export-received-details-by-style/{filename}',
                            'exportReceivedDetailsByStyle'
                        )->name('export_received_details_by_style');
                        Route::get(
                            'print-received-details-by-style',
                            'printReceivedDetailsByStyle'
                        )->name('print_received_details_by_style');

                        Route::get(
                            'export-balance-details-by-brand/{filename}',
                            'exportBalanceDetailsByBrand'
                        )->name('export_balance_details_by_brand');
                        Route::get(
                            'print-balance-details-by-brand',
                            'printBalanceDetailsByBrand'
                        )->name('print_balance_details_by_brand');
                        Route::get(
                            'export-sold-details-by-brand/{filename}',
                            'exportSoldDetailsByBrand'
                        )->name('export_sold_details_by_brand');
                        Route::get(
                            'print-sold-details-by-brand',
                            'printSoldDetailsByBrand'
                        )->name('print_sold_details_by_brand');
                        Route::get(
                            'export-received-details-by-brand/{filename}',
                            'exportReceivedDetailsByBrand'
                        )->name('export_received_details_by_brand');
                        Route::get(
                            'print-received-details-by-brand',
                            'printReceivedDetailsByBrand'
                        )->name('print_received_details_by_brand');
                        Route::get(
                            'export-balance-details-by-color/{filename}',
                            'exportBalanceDetailsByColor'
                        )->name('export_balance_details_by_color');
                        Route::get(
                            'print-balance-details-by-color',
                            'printBalanceDetailsByColor'
                        )->name('print_balance_details_by_color');
                        Route::get(
                            'export-sold-details-by-color/{filename}',
                            'exportSoldDetailsByColor'
                        )->name('export_sold_details_by_color');
                        Route::get(
                            'print-sold-details-by-color',
                            'printSoldDetailsByColor'
                        )->name('print_sold_details_by_color');
                        Route::get(
                            'export-received-details-by-color/{filename}',
                            'exportReceivedDetailsByColor'
                        )->name('export_received_details_by_color');
                        Route::get(
                            'print-received-details-by-color',
                            'printReceivedDetailsByColor'
                        )->name('print_received_details_by_color');
                        Route::get(
                            'export-balance-details-by-location/{filename}',
                            'exportBalanceDetailsByLocation'
                        )->name('export_balance_details_by_location');
                        Route::get(
                            'print-balance-details-by-location',
                            'printBalanceDetailsByLocation'
                        )->name('print_balance_details_by_location');
                        Route::get(
                            'export-sold-details-by-location/{filename}',
                            'exportSoldDetailsByLocation'
                        )->name('export_sold_details_by_location');
                        Route::get(
                            'print-sold-details-by-location',
                            'printSoldDetailsByLocation'
                        )->name('print_sold_details_by_location');
                        Route::get(
                            'export-received-details-by-location/{filename}',
                            'exportReceivedDetailsByLocation'
                        )->name('export_received_details_by_location');
                        Route::get(
                            'print-received-details-by-location',
                            'printReceivedDetailsByLocation'
                        )->name('print_received_details_by_location');
                        Route::get(
                            'export-balance-details-by-department/{filename}',
                            'exportBalanceDetailsByDepartment'
                        )->name('export_balance_details_by_department');
                        Route::get(
                            'print-balance-details-by-department',
                            'printBalanceDetailsByDepartment'
                        )->name('print_balance_details_by_department');
                        Route::get(
                            'export-sold-details-by-department/{filename}',
                            'exportSoldDetailsByDepartment'
                        )->name('export_sold_details_by_department');
                        Route::get(
                            'print-sold-details-by-department',
                            'printSoldDetailsByDepartment'
                        )->name('print_sold_details_by_department');
                        Route::get(
                            'export-received-details-by-department/{filename}',
                            'exportReceivedDetailsByDepartment'
                        )->name('export_received_details_by_department');
                        Route::get(
                            'print-received-details-by-department',
                            'printReceivedDetailsByDepartment'
                        )->name('print_received_details_by_department');
                        Route::get(
                            'export-balance-details-by-upc/{filename}',
                            'exportBalanceDetailsByUpc'
                        )->name('export_balance_details_by_upc');
                        Route::get(
                            'print-balance-details-by-upc',
                            'printBalanceDetailsByUpc'
                        )->name('print_balance_details_by_upc');
                        Route::get(
                            'export-sold-details-by-upc/{filename}',
                            'exportSoldDetailsByUpc'
                        )->name('export_sold_details_by_upc');
                        Route::get(
                            'print-sold-details-by-upc',
                            'printSoldDetailsByUpc'
                        )->name('print_sold_details_by_upc');
                        Route::get(
                            'export-received-details-by-upc/{filename}',
                            'exportReceivedDetailsByUpc'
                        )->name('export_received_details_by_upc');
                        Route::get(
                            'print-received-details-by-upc',
                            'printReceivedDetailsByUpc'
                        )->name('print_received_details_by_upc');

                        Route::get(
                            'export-balance-details-by-category/{filename}',
                            'exportBalanceDetailsByCategory'
                        )->name('export_balance_details_by_category');
                        Route::get(
                            'print-balance-details-by-category',
                            'printBalanceDetailsByCategory'
                        )->name('print_balance_details_by_category');
                        Route::get(
                            'export-sold-details-by-category/{filename}',
                            'exportSoldDetailsByCategory'
                        )->name('export_sold_details_by_category');
                        Route::get(
                            'print-sold-details-by-category',
                            'printSoldDetailsByCategory'
                        )->name('print_sold_details_by_category');
                        Route::get(
                            'export-received-details-by-category/{filename}',
                            'exportReceivedDetailsByCategory'
                        )->name('export_received_details_by_category');
                        Route::get(
                            'print-received-details-by-category',
                            'printReceivedDetailsByCategory'
                        )->name('print_received_details_by_category');

                        Route::get(
                            'export-received-details-by-attribute/{filename}',
                            'exportReceivedDetailsByAttribute'
                        )->name('export_received_details_by_attribute');
                        Route::get(
                            'print-received-details-by-attribute',
                            'printReceivedDetailsByAttribute'
                        )->name('print_received_details_by_attribute');
                        Route::get(
                            'export-balance-details-by-attribute/{filename}',
                            'exportBalanceDetailsByAttribute'
                        )->name('export_balance_details_by_attribute');
                        Route::get(
                            'print-balance-details-by-attribute',
                            'printBalanceDetailsByAttribute'
                        )->name('print_balance_details_by_attribute');
                        Route::get(
                            'export-sold-details-by-attribute/{filename}',
                            'exportSoldDetailsByAttribute'
                        )->name('export_sold_details_by_attribute');
                        Route::get(
                            'print-sold-details-by-attribute',
                            'printSoldDetailsByAttribute'
                        )->name('print_sold_details_by_attribute');
                    }
                );
                Route::get('fetch-sale-through-report-for-chart', 'fetchSellThroughDetailsForChart')->name(
                    'fetch_records_for_chart'
                );
            }
        );

        Route::controller(SaleAnalysisByGradeReportController::class)->name('sale_analysis_by_grade.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_analysis'))->group(
                    function (): void {
                        Route::get('sale-analysis-by-grade-report', 'index')->name('index');
                        Route::get('fetch-sale-analysis-by-grade-report', 'fetchSaleAnalysisByGradeReport')->name(
                            'fetch'
                        );
                        Route::get(
                            'fetch-total-sale-analysis-by-grade-report',
                            'fetchTotalSaleAnalysisByGradeReport'
                        )->name('fetch_total');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_analysis'))->group(
                    function (): void {
                        Route::get('print-sale-analysis-by-grade-report', 'printSaleAnalysisByGradeReport')->name(
                            'print_sale_analysis'
                        );
                        Route::get(
                            'export-sale-analysis-by-grade-report/{filename}',
                            'exportSaleAnalysisByGradeReport'
                        )->name('export_sale_analysis');
                    }
                );
            }
        );
        Route::controller(HappyHourDiscountController::class)->name('happy_hours.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('happy_hour'))->group(
                function (): void {
                    Route::get('happy-hours', 'index')->name('index');
                    Route::get('fetch-happy-hours', 'fetchHappyHours')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('happy_hour'))->group(
                function (): void {
                    Route::get('happy-hours/create', 'create')->name('create');
                    Route::post('happy-hours', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('happy_hour'))->group(
                function (): void {
                    Route::get('happy-hours/{happyHourDiscountId}/edit', 'edit')->name('edit')->where(
                        'happyHourDiscountId',
                        '[0-9]+'
                    );
                    Route::put('happy-hours/{happyHourDiscountId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('happy_hour'))->group(
                function (): void {
                    Route::get('export-happy-hours/{fileName}', 'exportHappyHours')->name('export_happy_hours');
                }
            );
        });
        Route::controller(SaleTargetController::class)->name('sale_targets.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_target'))->group(
                function (): void {
                    Route::get('sale-targets', 'index')->name('index');
                    Route::get('fetch-sale-targets', 'fetchSaleTargets')->name('fetch');
                    Route::get('fetch-sale-target/{saleTargetId}', 'fetchSaleTarget')->name('fetch_sale_target');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('sale_target'))->group(
                function (): void {
                    Route::get('sale-targets/create', 'create')->name('create');
                    Route::post('sale-targets', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('sale_target'))->group(
                function (): void {
                    Route::get('sale-targets/{saleTargetId}/edit', 'edit')->name('edit');
                    Route::put('sale-targets/{saleTargetId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_target'))->group(
                function (): void {
                    Route::get('export-sale-targets/{fileName}', 'exportSaleTargets')->name('export');
                }
            );
            Route::post('sale-targets/{saleTargetId}/set-status/{status}', 'setStatus')->name('set_status');
            Route::put('re-generate-target/{saleTargetId}', 'reGenerateTarget')->name('re_generate_target');
        });

        Route::controller(SaleTargetReportController::class)->name('sale_achieved_targets.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_achieved_target'))->group(
                function (): void {
                    Route::get('sale-achieved-targets', 'index')->name('index');
                    Route::get('fetch-sale-achieved-targets', 'fetchSaleAchievedTargets')->name('fetch');
                    Route::post('get-promoters', 'getStoreWisePromoters')->name('get_promoters');
                    Route::get(
                        'get-sales-and-sales-returns-for-sale-achieved-target/{saleAchievedTargetId}',
                        'getSalesAndSalesReturnsForSaleAchievedTarget'
                    )->name('fetch_sales_and_returns_for_sale_achieved_target');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_achieved_target'))->group(
                function (): void {
                    Route::get('export-sale-achieved-target/{fileName}', 'exportSaleAchievedTarget')->name(
                        'export_sale_achieved_target'
                    );
                }
            );
        });
        Route::controller(EmployeeSalesReportController::class)->name('employee_sales_report.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('employee_sale'))->group(
                    function (): void {
                        Route::get('employee-sales-report', 'index')->name('index');
                        Route::get('fetch-employee-sales-report', 'fetchEmployeeSales')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('employee_sale'))->group(
                    function (): void {
                        Route::get('export-employee-sales/{fileName}', 'exportEmployeeSales')->name(
                            'export_employee_sales'
                        );
                    }
                );
            }
        );
        Route::controller(StoreManagerRoleController::class)->name('store_manager_roles.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('store_manager_role'))->group(
                    function (): void {
                        Route::get('store-manager-role', 'index')->name('index');
                        Route::get('store-manager-role/fetch', 'fetch')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('store_manager_role'))->group(
                    function (): void {
                        Route::get('store-manager-role/create', 'create')->name('create');
                        Route::post('store-manager-role', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('store_manager_role'))->group(
                    function (): void {
                        Route::get('store-manager-role/{roleId}/edit', 'edit')->name('edit_roles_permissions');
                        Route::post('store-manager-role/{roleId}/update', 'update')->name('update_roles_permissions');
                        Route::get('store-manager-role/{roleId}/clone', 'clone')->name('clone');
                    }
                );
            }
        );
        Route::controller(WarehouseManagerRoleController::class)->name('warehouse_manager_roles.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('warehouse_manager_role')
                )->group(
                    function (): void {
                        Route::get('warehouse-manager-roles', 'index')->name('index');
                        Route::get('warehouse-manager-roles/fetch', 'fetch')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('warehouse_manager_role')
                )->group(
                    function (): void {
                        Route::get('warehouse-manager-role/create', 'create')->name('create');
                        Route::post('warehouse-manager-role', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('warehouse_manager_role')
                )->group(
                    function (): void {
                        Route::get('warehouse-manager-role/{roleId}/edit', 'edit')->name('edit_roles_permissions');
                        Route::post('warehouse-manager-role/{roleId}/update', 'update')->name(
                            'update_roles_permissions'
                        );
                        Route::get('warehouse-manager-role/{roleId}/clone', 'clone')->name('clone');
                    }
                );
            }
        );
        Route::controller(SaleSeasonsController::class)->name('sale_seasons.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_seasons'))->group(
                function (): void {
                    Route::get('sale-seasons', 'index')->name('index');
                    Route::get('fetch-sale-seasons', 'fetchSaleSeasons')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('sale_seasons'))->group(
                function (): void {
                    Route::get('sale-seasons/create', 'create')->name('create');
                    Route::post('sale-season', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('sale_seasons'))->group(
                function (): void {
                    Route::get('sale-season/{saleSeasonId}/edit', 'edit')->name('edit');
                    Route::put('sale-season/{saleSeasonId}/update', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('sale_seasons'))->group(
                function (): void {
                    Route::post('sale-season/{saleSeasonId}/delete', 'delete')->name('delete');
                }
            );
        });
        Route::controller(BatchExpiryController::class)->name('batch_expiry.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('batch_expiry'))->group(
                function (): void {
                    Route::get('batch-expiry', 'index')->name('index');
                    Route::get('fetch-batch-expiry', 'fetchBatchExpiry')->name('fetch_batch_expiry');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('batch_expiry'))->group(
                function (): void {
                    Route::get('export-batch-expiry/{fileName}', 'exportBatchExpiry')->name('export_batch_expiry');
                }
            );
        });
        Route::controller(OrderController::class)->name('orders.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('order'))->group(
                function (): void {
                    Route::get('b2b-orders', 'b2bOrders')->name('b2bOrders');
                    Route::get('fetch-b2b-orders', 'fetchB2bOrders')->name('fetch_b2b_orders');
                    Route::get('marketplaces-orders', 'marketplacesOrders')->name('marketplaces_orders');
                    Route::get('fetch-marketplaces-orders', 'fetchMarketplacesOrders')->name(
                        'fetch_marketplaces_orders'
                    );
                    Route::get('fetch-order-items', 'fetchOrderItemsByOrderId')->name('fetch_order_items');
                    Route::get('fetch-order-items-for-ecommerce', 'fetchOrderItemsEcommerceByOrderId')->name(
                        'fetch_order_items_for_ecommerce'
                    );
                    Route::get('print-order-receipt/{orderId}', 'printOrderReceipt')->name('print_order_receipt');
                    Route::get('print-order-tax-invoice/{orderId}', 'printOrderTaxInvoice')->name(
                        'print_order_tax_invoice'
                    );
                    Route::get('print-purchase-order/{orderId}', 'printPurchaseOrder')->name('print_purchase_order');
                    Route::get('print-layaway-order-report/{orderId}', 'printLayawayOrderReport')->name(
                        'print_layaway_order_report'
                    );
                    Route::get('print-credit-order-report/{orderId}', 'printCreditOrderReport')->name(
                        'print_credit_order_report'
                    );
                    Route::post('marketplaces-orders/{orderId}/accepted', 'accepted')->name('accepted');
                    Route::post('marketplaces-orders/{orderId}/cancelled', 'cancelled')->name('cancelled');
                    Route::get('print-b2b-order-digital-invoice/{orderId}', 'printDigitalInvoice')->name(
                        'print_b2b_order_digital_invoice'
                    );

                    Route::post('marketplaces-orders/{orderId}/ready-for-pickup', 'readyForPickup')->name(
                        'ready_for_pickup'
                    );
                    Route::get('fetch-order-address', 'fetchOrderAddress')->name('fetch_order_address');
                    Route::post('marketplaces-orders/{orderAddressId}/change-address', 'updateAddress')->name(
                        'update_address'
                    );
                    Route::get('print-ninja-van-way-bill/{orderId}', 'printNinjaVanWayBill')->name(
                        'print_ninja_van_way_bill'
                    );

                    Route::get('export-marketplace-orders/{fileName}', 'exportMarketplaceOrders')->name(
                        'export_marketplace_orders'
                    );

                    Route::get('print-marketplace-orders', 'printMarketplaceOrders')->name('print_marketplace_orders');
                }
            );
        });
        Route::controller(OrderReturnController::class)->name('order_returns.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('order_return'))->group(
                function (): void {
                    Route::get('order-returns', 'index')->name('index');
                    Route::get('fetch-order-returns', 'fetchOrderReturns')->name('fetch_order_returns');
                    Route::get('fetch-order-return-items/{orderReturnId}', 'fetchOrderReturnItems')->name(
                        'fetch_order_return_items'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('order_return'))->group(
                function (): void {
                    Route::get('print-order-return-receipt/{orderReturnId}', 'printOrderReturnReceipt')->name(
                        'print_order_return_receipt'
                    );
                    Route::get('print-order-return-digital-invoice/{orderReturnId}', 'printDigitalInvoice')->name(
                        'print_order_return_digital_invoice'
                    );
                }
            );
        });
        Route::controller(DraftProductController::class)->name('draft_products.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('draft_product'))->group(
                function (): void {
                    Route::get('draft-products', 'index')->name('index');
                    Route::get('fetch-draft-products', 'fetchDraftProducts')->name('fetch');
                    Route::post('draft-products/approved', 'approved')->name('approved');
                    Route::get('get-draft-product-ids', 'getDraftProductIdsByExceptLoginUser')->name(
                        'get_draft_product_ids'
                    );
                    Route::get('get-draft-product-details/{productId}', 'getDraftProductDetails')->name(
                        'get_draft_product_details'
                    );
                    Route::get('get-match-active-products/{productId}', 'getMatchActiveProducts')->name(
                        'get_match_active_products'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('draft_product'))->group(
                function (): void {
                    Route::get('draft-products/{productId}/edit', 'edit')->name('edit');
                    Route::put('draft-products/{productId}', 'update')->name('update');
                    Route::put('draft-products/master-product/{masterProductId}', 'updateMasterProduct')->name(
                        'update_master_product'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('draft_product'))->group(
                function (): void {
                    Route::post('draft-products/delete/products', 'deleteProducts')->name('delete');
                }
            );
        });
        Route::controller(ExternalLoginController::class)->name('external_logins.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('external_login'))->group(
                    function (): void {
                        Route::get('external-login', 'index')->name('index');
                        Route::get('get-external-login-details/{externalCompanyId}', 'getExternalLoginDetails')->name(
                            'get_external_login_details'
                        );
                    }
                );
            }
        );
        Route::controller(ProductCollectionController::class)->name('product_collections.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('product_collection'))->group(
                    function (): void {
                        Route::get('product-collection', 'index')->name('index');
                        Route::get('fetch-product-collections', 'fetchProductCollections')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('product_collection'))->group(
                    function (): void {
                        Route::get('product-collection-create', 'create')->name('create');
                        Route::post('product-collections-store', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('product_collection'))->group(
                    function (): void {
                        Route::post('product-collections-change-status', 'changeStatus')->name('change_status');
                        Route::get('product-collections/{productCollectionId}/edit', 'edit')->name('edit');
                        Route::put('product-collections/{productCollectionId}/update', 'update')->name('update');
                        Route::post('sync-product-collections', 'syncProductCollections')->name(
                            'sync_product_collections'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getRemovePermissionName('product_collection'))->group(
                    function (): void {
                        Route::post('product-collections/{productCollectionId}/delete', 'delete')->name('delete');
                    }
                );
                Route::post('upload-images/{productCollectionId}', 'uploadImages')->name('upload_images');
                Route::get('manage-media-view/{productCollectionId}', 'manageMediaView')->name('manage_media_view');
                Route::get('remove-portrait-image/{productCollectionId}/{mediaId}', 'removePortraitImage')->name(
                    'remove_portrait_image'
                );
                Route::get('remove-landscape-image/{productCollectionId}/{mediaId}', 'removeLandscapeImage')->name(
                    'remove_landscape_image'
                );
                Route::post('get-filtered-product-collection', 'getFilteredProductCollections')->name(
                    'get_filtered_product_collection'
                );
                Route::get('product-collections-sync-data', 'syncData')->name('sync_data');
            }
        );
        Route::controller(DriverController::class)->name('drivers.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('driver'))->group(
                    function (): void {
                        Route::get('drivers', 'index')->name('index');
                        Route::get('fetch-drivers', 'fetchDrivers')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('driver'))->group(
                    function (): void {
                        Route::inertia('drivers/create', 'drivers/Manage')->name('create');
                        Route::post('drivers-store', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('driver'))->group(
                    function (): void {
                        Route::post('drivers/{driverId}/change-status', 'changeStatus')->name('change_status');
                        Route::get('drivers/{driverId}/edit', 'edit')->name('edit');
                        Route::put('drivers/{driverId}/update', 'update')->name('update');
                    }
                );
            }
        );
        Route::controller(VehicleController::class)->name('vehicles.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('vehicle'))->group(
                    function (): void {
                        Route::get('vehicles', 'index')->name('index');
                        Route::get('fetch-vehicles', 'fetchVehicles')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('vehicle'))->group(
                    function (): void {
                        Route::inertia('vehicles/create', 'vehicles/Manage')->name('create');
                        Route::post('vehicles-store', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('vehicle'))->group(
                    function (): void {
                        Route::post('vehicles/{vehicleId}/change-status', 'changeStatus')->name('change_status');
                        Route::get('vehicles/{vehicleId}/edit', 'edit')->name('edit');
                        Route::put('vehicles/{vehicleId}/update', 'update')->name('update');
                    }
                );
            }
        );
        Route::controller(TemplateController::class)->name('templates.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('template'))->group(
                function (): void {
                    Route::get('templates', 'index')->name('index');
                    Route::get('fetch-templates', 'fetchTemplates')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('template'))->group(
                function (): void {
                    Route::inertia('templates/create', 'templates/Manage')->name('create');
                    Route::post('templates', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('template'))->group(
                function (): void {
                    Route::get('templates/{template}/edit', 'edit')->name('edit');
                    Route::put('templates/{templateId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('template'))->group(
                function (): void {
                    Route::post('templates/{templateId}/delete', 'delete')->name('delete');
                }
            );
        });

        Route::controller(TemplateAttributeController::class)->name('template_attributes.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('template_attribute'))->group(
                function (): void {
                    Route::get('template-attributes', 'index')->name('index');
                    Route::get('fetch-template-attributes', 'fetchTemplateAttributes')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('template_attribute'))->group(
                function (): void {
                    Route::get('template-attributes/create', 'create')->name('create');
                    Route::post('template-attributes', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('template_attribute'))->group(
                function (): void {
                    Route::get('template-attributes/{attribute}/edit', 'edit')->name('edit');
                    Route::put('template-attributes/{attributeId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('template_attribute'))->group(
                function (): void {
                    Route::post('template-attributes/{attributeId}/delete', 'delete')->name('delete');
                }
            );
        });
        Route::controller(AttributeController::class)->name('attributes.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('attribute'))->group(
                function (): void {
                    Route::get('templates/{templateId}/attributes', 'index')->name('index');
                    Route::get('templates/{templateId}/fetch-attributes', 'fetchAttributes')->name('fetch');
                    Route::get('templates/{attributeId}/fetch-attribute-options', 'fetchAttributeOptions')->name(
                        'fetch-attribute-options'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('attribute'))->group(
                function (): void {
                    Route::get('templates/{templateId}/attributes/create', 'create')->name('create');
                    Route::post('templates/{templateId}/attributes', 'store')->name('store');
                    Route::post('templates/{templateId}/old-attributes', 'storeOld')->name('store_old');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('attribute'))->group(
                function (): void {
                    Route::get('templates/{templateId}/attributes/{attributeId}', 'edit')->name('edit');
                    Route::put('templates/{templateId}/attributes/{attributeId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getRemovePermissionName('attribute'))->group(
                function (): void {
                    Route::post('templates/{templateId}/attributes/{attributeId}/delete', 'delete')->name('delete');
                }
            );
        });
        Route::controller(CustomFieldValueController::class)->name('custom_field_values.')->group(
            function (): void {
                Route::post('custom-field-values/fetch', 'fetch')->name('fetch');
            }
        );
        Route::controller(LoyaltyCampaignConfigurationController::class)->name(
            'loyalty_campaign_configurations.'
        )->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('loyalty_campaign_configuration')
                )->group(
                    function (): void {
                        Route::get('loyalty-campaign-configurations', 'index')->name('index');
                        Route::get('fetch-loyalty-campaign-configurations', 'fetchLoyaltyCampaignConfigurations')->name(
                            'fetch'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('loyalty_campaign_configuration')
                )->group(
                    function (): void {
                        Route::get('loyalty-campaign-configurations/create', 'create')->name('create');
                        Route::post('loyalty-campaign-configurations', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('loyalty_campaign_configuration')
                )->group(
                    function (): void {
                        Route::get(
                            'loyalty-campaign-configurations/{loyaltyCampaignConfigurationId}/edit',
                            'edit'
                        )->name('edit');
                        Route::put(
                            'loyalty-campaign-configurations/{loyaltyCampaignConfigurationId}/update',
                            'update'
                        )->name('update');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('loyalty_campaign_configuration')
                )->group(
                    function (): void {
                        Route::get(
                            'export-loyalty-campaign-configurations/{fileName}',
                            'exportLoyaltyCampaignConfigurations'
                        )->name('export_loyalty_campaigns');
                    }
                );
            }
        );
        Route::controller(RewardController::class)->name('rewards.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('rewards'))->group(
                    function (): void {
                        Route::get('rewards', 'index')->name('index');
                        Route::get('fetch-rewards', 'fetchRewards')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('rewards'))->group(
                    function (): void {
                        Route::get('rewards/create', 'create')->name('create');
                        Route::post('rewards', 'store')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('rewards'))->group(
                    function (): void {
                        Route::get('rewards/{rewardId}/edit', 'edit')->name('edit');
                        Route::put('rewards/{rewardId}/update', 'update')->name('update');
                        Route::post('rewards/{rewardId}/set-status/{status}', 'setStatus')->name('set_status');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('rewards'))->group(
                    function (): void {
                        Route::get('export-rewards/{fileName}', 'exportRewards')->name('export_rewards');
                    }
                );
            }
        );
        Route::controller(LocationController::class)->name('locations.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('location'))->group(
                function (): void {
                    Route::get('locations', 'index')->name('index');
                    Route::get('fetch-locations', 'fetchLocations')->name('fetch');
                    Route::get(
                        'fetch-stores-ioi-city-mall-configuration/{storeId}',
                        'fetchStoreIOICityMallConfiguration'
                    )->name('fetch_store_ioi_city_mall_configuration');
                    Route::get(
                        'fetch-stores-trx-mall-configuration/{storeId}',
                        'fetchStoreTRXMallConfiguration'
                    )->name('fetch_store_trx_mall_configuration');
                    Route::get('locations/generate-qrcode/{storeId}', 'generateQrCode')->name('generate_qr_code');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('location'))->group(
                function (): void {
                    Route::get('locations/create', 'create')->name('create');
                    Route::post('locations', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('location'))->group(
                function (): void {
                    Route::get('locations/{locationId}/edit', 'edit')->name('edit');
                    Route::post('locations/{locationId}/update', 'update')->name('update');
                    Route::post('stores-ioi-configuration/{storeId}', 'updateIOICityMallConfiguration')->name(
                        'update_ioi_city_mall_configuration'
                    );
                    Route::post('stores-trx-configuration/{storeId}', 'updateTRXMallConfiguration')->name(
                        'update_trx_mall_configuration'
                    );
                    Route::get('locations/{locationId}/resend-verification-email', 'resendVerificationEmail')->name(
                        'resend_verification_email'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('location'))->group(
                function (): void {
                    Route::get('export-locations/{fileName}', 'exportLocations')->name('export_locations');
                }
            );
            Route::get('get-location-sales-summary', 'getLocationSalesSummary')->name('get_location_sales_summary');
            Route::post('get-locations-of-regions', 'getLocationsOfRegions')->name('get_locations_of_regions');
            Route::post('get-locations-of-locations-name', 'getLocationsOfLocationsName')->name(
                'get_locations_of_locations_name'
            );
            Route::post('get-matching-code-locations', 'getMatchingCodeLocations')->name('get_matching_code_locations');
        });
        Route::controller(OnlineSalesChargesController::class)->name('online_sales_charges.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('online_sales_charges'))->group(
                    function (): void {
                        Route::get('online-sales-charges', 'index')->name('index');
                        Route::get('fetch-online-sales-charges', 'fetchOnlineSalesCharges')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('online_sales_charges')
                )->group(
                    function (): void {
                        Route::get('online-sales-charges/create', 'create')->name('create');
                        Route::post('online-sales-charge', 'store')->name('store');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('online_sales_charges')
                )->group(
                    function (): void {
                        Route::get('online-sales-charges/{onlineSalesChargeId}/edit', 'edit')->name('edit');
                        Route::put('online-sales-charges/{onlineSalesChargeId}/update', 'update')->name('update');
                        Route::post('toggle-status', 'toggleStatus')->name('toggleStatus');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getRemovePermissionName('online_sales_charges')
                )->group(
                    function (): void {
                        Route::post('online-sales-charges/{onlineSalesChargeId}/delete', 'delete')->name('delete');
                    }
                );
                Route::get('online-sales-charges-sync-data', 'syncData')->name('sync_data');
            }
        );
        Route::controller(StateController::class)->name('states.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('state'))->group(
                function (): void {
                    Route::get('get-states', 'index')->name('index');
                    Route::get('fetch-states', 'fetchStates')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('state'))->group(
                function (): void {
                    Route::get('states/create', 'create')->name('create');
                    Route::post('states', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('state'))->group(
                function (): void {
                    Route::get('states/{stateId}/edit', 'edit')->name('edit');
                    Route::put('states/{stateId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('state'))->group(
                function (): void {
                    Route::get('export-states/{fileName}', 'exportStates')->name('export_states');
                }
            );
            Route::get('get-states/{country_id}', 'getStatesByCountryId')->name('get_states');
        });
        Route::controller(CityController::class)->name('cities.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('city'))->group(
                function (): void {
                    Route::get('get-cities', 'index')->name('index');
                    Route::get('fetch-cities', 'fetchCities')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('city'))->group(
                function (): void {
                    Route::get('cities/create', 'create')->name('create');
                    Route::post('cities', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('city'))->group(
                function (): void {
                    Route::get('cities/{cityId}/edit', 'edit')->name('edit');
                    Route::put('cities/{cityId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('city'))->group(
                function (): void {
                    Route::get('export-cities/{fileName}', 'exportCities')->name('export_cities');
                }
            );
            Route::get('get-cities/{state_id}', 'getCitiesByStateId')->name('get_cities');
        });
        Route::controller(OrderPickingListController::class)->name('order_picking_lists.')->group(
            function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('order_picking_lists'))->group(
                    function (): void {
                        Route::get('order-picking-lists', 'index')->name('index');
                        Route::get('fetch-order-picking-lists', 'fetchOrderPickingLists')->name('fetch');
                        Route::get(
                            'fetch-order-picking-list-items/{orderPickingId}',
                            'fetchOrderItemsByOrderPickingId'
                        )->name('fetch_order_picking_list_items');
                        Route::post('order-picking-lists/{orderPickingId}/inprogress', 'inprogress')->name(
                            'inprogress'
                        );
                        Route::post('order-picking-lists/{orderPickingId}/cancel', 'cancel')->name('cancel');
                        Route::post('order-picking-lists/{orderPickingId}/completed', 'completed')->name('completed');
                        Route::get('print-ninja-van-way-bills/{orderPickingId}', 'printNinjaVanWayBills')->name(
                            'print_ninja_van_way_bills'
                        );
                    }
                );
                Route::get('print-order-packaging/{orderPickingListId}', 'printOrderPackaging')->name(
                    'print_order_packaging'
                );
                Route::get('print-order-packing-list/{orderPickingListId}', 'printOrderPackingList')->name(
                    'print_order_packing_list'
                );
                Route::post('order-picking-lists', 'store')->name('store');
            }
        );
        Route::controller(ExportRecordController::class)->name('export_records.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('export_record'))->group(
                function (): void {
                    Route::get('export-records/{id?}', 'index')->name('index');
                    Route::get('fetch-export-records', 'fetchExportRecords')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('export_record'))->group(
                function (): void {
                    Route::get('export-export-records/{fileName}', 'exportRecords')->name('export_records');
                }
            );
        });

        Route::controller(UserController::class)->name('users.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('user'))->group(function (): void {
                Route::get('users', 'index')->name('index');
                Route::get('users/fetch', 'fetchUsers')->name('fetch');
            });

            Route::middleware('permission:' . PermissionList::getWritePermissionName('user'))->group(
                function (): void {
                    Route::get('users/create', 'create')->name('create');
                    Route::post('users', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('user'))->group(
                function (): void {
                    Route::get('users/{userId}/edit', 'edit')->name('edit');
                    Route::put('users/{userId}/update', 'update')->name('update');
                    Route::get('users/{userId}/change-password', 'changePassword')->name('change_password');
                    Route::post('users/{userId}/update-password', 'updatePassword')->name('update_password');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('user'))->group(
                function (): void {
                    Route::get('export-users/{fileName}', 'exportUsers')->name('export_users');
                }
            );
        });

        Route::controller(MasterProductController::class)->name('master_products.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('master_product'))->group(
                function (): void {
                    Route::get('master-products', 'index')->name('index');
                    Route::get('fetch-master-products', 'fetchMasterProducts')->name('fetch');
                    Route::get('exists-master-product-upc/{upc}', 'existsMasterProductUpc')->name(
                        'exists_master_product_upc'
                    );
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('master_product'))->group(
                function (): void {
                    Route::get('master-products/create', 'create')->name('create');
                    Route::post('master-products', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('master_product'))->group(
                function (): void {
                    Route::get('master-products/{masterProductId}/edit', 'edit')->name('edit');
                    Route::put('master-products/{masterProductId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:master_product_' . PermissionList::MASTER_PRODUCT_UPLOAD_IMAGE->value)->group(
                function (): void {
                    Route::post('master-product-upload-image', 'uploadImage')->name('upload_image');
                    Route::get(
                        'remove-master-product-image/{masterProductId}/{mediaId}',
                        'removeMasterProductImage'
                    )->name('remove_master_product_image');
                    Route::get(
                        'remove-master-product-video/{masterProductId}/{mediaId}',
                        'removeMasterProductVideo'
                    )->name('remove_master_product_video');
                    Route::get(
                        'remove-master-product-thumbnail/{masterProductId}/remove-master-product-thumbnail',
                        'removeMasterProductThumbnail'
                    )->name('remove_master_product_thumbnail');
                    Route::get(
                        'remove-product-variant-image/{productVariantId}/{mediaId}',
                        'removeProductVariantImage'
                    )->name('remove_product_variant_image');
                    Route::get(
                        'remove-product-variant-video/{productVariantId}/{mediaId}',
                        'removeProductVariantVideo'
                    )->name('remove_product_variant_video');
                    Route::get(
                        'remove-product-variant-thumbnail/{productVariantId}/remove-product-variant-thumbnail',
                        'removeProductVariantThumbnail'
                    )->name('remove_product_variant_thumbnail');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('master_product'))->group(
                function (): void {
                    Route::get('export-master-products/{fileName}', 'exportMasterProducts')->name(
                        'export_master_products'
                    );
                }
            );
            Route::get('master-products-sync-data', 'syncData')->name('sync_data');
            Route::post('remove-master-product-variants/{masterProductId}', 'removeMasterProductVariants')->name(
                'remove_master_product_variants'
            );
            Route::post('remove-master-product-variant/{productVariantId}', 'removeMasterProductVariant')->name(
                'remove_master_product_variant'
            );
            Route::post('get-master-products-article-numbers', 'getFilteredArticleNumber')->name(
                'get_filtered_master_product_article_number'
            );
        });

        Route::controller(MasterProductFilterController::class)->group(function (): void {
            Route::get('get-filtered-regular-master-products', 'getFilteredRegularMasterProducts')->name(
                'get_filtered_regular_master_products'
            );
            Route::get('get-filtered-regular-master-products-list', 'getFilteredRegularMasterProductsList')->name(
                'get_filtered_regular_master_products_list'
            );
            Route::get('master-products/{masterProductId}', 'getMasterProduct')->name('get_master_product');
        });

        Route::controller(EmailTemplateController::class)->name('email_templates.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('email_template'))->group(
                function (): void {
                    Route::get('email-templates', 'index')->name('index');
                    Route::get('fetch-email-templates', 'fetch')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('email_template'))->group(
                function (): void {
                    Route::get('email-templates/create', 'create')->name('create');
                    Route::post('email-templates', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('email_template'))->group(
                function (): void {
                    Route::get('email-templates/{emailTemplateId}/edit', 'edit')->name('edit');
                    Route::post('email-templates/{emailTemplateId}/update', 'update')->name('update');
                }
            );

            Route::get('get-email-templates', 'getAll')->name('get_all');
        });

        Route::controller(OnlineProductsReportController::class)->name('online_products_report.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('online_product_report')
                )->group(
                    function (): void {
                        Route::get('online-products-report', 'index')->name('index');
                        Route::get('fetch-online-products-report', 'fetchOnlineProductsReport')->name('fetch');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('online_product_report')
                )->group(
                    function (): void {
                        Route::get('export-online-products-report/{fileName}', 'exportOnlineProductsReport')->name(
                            'export_online_products_report'
                        );
                        Route::get('print-online-products-report', 'printOnlineProducts')->name(
                            'print_online_products_report'
                        );
                    }
                );
            }
        );

        Route::controller(PurchasePlanController::class)->name('purchase_plans.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('purchase_plan'))->group(
                function (): void {
                    Route::get('purchase-plans', 'index')->name('index');
                    Route::get('fetch-purchase-plans', 'fetchPurchasePlans')->name('fetch');
                    Route::get(
                        'fetch-purchase-plan-items/{purchasePlanId}',
                        'fetchPurchasePlanItemByPurchasePlanId'
                    )->name('fetch_purchase_plan_items');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('purchase_plan'))->group(
                function (): void {
                    Route::get('purchase-plans/create', 'create')->name('create');
                    Route::post('purchase-plans', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('purchase_plan'))->group(
                function (): void {
                    Route::get('purchase-plans/{purchasePlanId}/edit', 'edit')->name('edit');
                    Route::post('purchase-plans/{purchasePlanId}/update', 'update')->name('update');
                    Route::post('purchase-plans/{purchasePlanId}/cancel', 'cancel')->name('cancel');
                    Route::post('purchase-plans/{purchasePlanId}/approve', 'approve')->name('approve');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('purchase_plan'))->group(
                function (): void {
                    Route::get('purchase-plan/{purchasePlanId}/print', 'print')->name('print');
                    Route::get('export-purchase-plans/{fileName}', 'exportPurchasePlans');
                    Route::get('export-purchase-plan-items/{purchasePlanId}/{fileName}', 'exportPurchasePlanItems');
                }
            );
        });

        Route::controller(ExternalPurchaseOrderController::class)->name('external_purchase_orders.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('external_purchase_order')
                )->group(
                    function (): void {
                        Route::get('external-purchase-orders/{purchasePlanId}', 'index')->name('index');
                        Route::get('fetch-external-purchase-orders', 'fetchExternalPurchaseOrders')->name('fetch');
                        Route::get(
                            'fetch-external-purchase-order-items/{externalPurchaseOrderId}',
                            'fetchExternalPurchaseOrderItemById'
                        )->name('fetch_external_purchase_order_items');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('external_purchase_order')
                )->group(
                    function (): void {
                        Route::get('external-purchase-orders/{purchasePlanId}/create', 'create')->name('create');
                        Route::post('external-purchase-orders/{purchasePlanId}', 'store')->name('store');
                    }
                );

                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('external_purchase_order')
                )->group(
                    function (): void {
                        Route::get('external-purchase-orders/{externalPurchaseOrderId}/edit', 'edit')->name('edit');
                        Route::post('external-purchase-orders/{externalPurchaseOrderId}/update', 'update')->name(
                            'update'
                        );
                    }
                );

                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('external_purchase_order')
                )->group(
                    function (): void {
                        Route::get(
                            '/export-external-purchase-order-items/{externalPurchaseOrderId}/{fileName}',
                            'exportExternalPurchaseOrderItems'
                        );
                        Route::get('export-external-purchase-orders/{externalPurchaseOrderId}/print', 'print')->name(
                            'print'
                        );
                    }
                );

                Route::post(
                    'external-purchase-orders/{externalPurchaseOrderId}/mark-as-cancel',
                    'markAsCancel'
                )->name('mark_as_cancel');
                Route::post(
                    'external-purchase-orders/{externalPurchaseOrderId}/mark-as-approve',
                    'markAsApprove'
                )->name('mark_as_approve');
            }
        );

        Route::controller(ExternalPurchaseOrderReceiveController::class)->name(
            'external_purchase_order_receives.'
        )->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('external_purchase_order_receive')
                )->group(
                    function (): void {
                        Route::get('external-purchase-order-receives/{externalPurchaseOrderId}', 'index')->name(
                            'index'
                        );
                        Route::get(
                            'fetch-external-purchase-order-receives',
                            'fetchExternalPurchaseOrderReceives'
                        )->name('fetch');
                        Route::get(
                            'fetch-external-purchase-order-receive-items/{externalPurchaseOrderReceiveId}',
                            'fetchExternalPurchaseOrderReceiveItemById'
                        )->name('fetch_external_purchase_order_receive_items');
                        Route::post(
                            'external-purchase-order-receives/{externalPurchaseOrderReceiveId}/completed',
                            'completed'
                        )->name('completed');
                        Route::post(
                            'external-purchase-order-receives/{externalPurchaseOrderReceiveId}/add-grn',
                            'addGrn'
                        )->name('add_grn');

                        Route::post(
                            'external-purchase-order-receives/{externalPurchaseOrderReceiveId}/cancel',
                            'markAsCancel'
                        )->name('mark_as_cancel');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getWritePermissionName('external_purchase_order_receive')
                )->group(
                    function (): void {
                        Route::get('external-purchase-order-receives/{externalPurchaseOrderId}/create', 'create')->name(
                            'create'
                        );
                        Route::post('external-purchase-order-receives/{externalPurchaseOrderId}', 'store')->name(
                            'store'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getModifyPermissionName('external_purchase_order_receive')
                )->group(
                    function (): void {
                        Route::get(
                            'external-purchase-order-receives/{externalPurchaseOrderReceiveId}/edit',
                            'edit'
                        )->name('edit');
                        Route::post(
                            'external-purchase-order-receives/{externalPurchaseOrderReceiveId}/update',
                            'update'
                        )->name('update');
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('external_purchase_order_receive')
                )->group(
                    function (): void {
                        Route::get(
                            'export-external-purchase-order-partial-receive-items/{partialReceiveId}/{fileName}',
                            'exportExternalPurchaseOrderPartialReceiveItems'
                        );
                        Route::get(
                            'print-external-purchase-order-partial-receive/{partialReceiveId}/print',
                            'print'
                        )->name('print');
                    }
                );
            }
        );

        Route::controller(CountryController::class)->name('countries.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('country'))->group(
                function (): void {
                    Route::get('get-countries', 'index')->name('index');
                    Route::get('fetch-countries', 'fetchCountries')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('country'))->group(
                function (): void {
                    Route::inertia('countries/create', 'countries/Manage')->name('create');
                    Route::post('countries', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('country'))->group(
                function (): void {
                    Route::get('countries/{countryId}/edit', 'edit')->name('edit');
                    Route::put('countries/{countryId}', 'update')->name('update');
                }
            );
            Route::middleware('permission:' . PermissionList::getExportPermissionName('country'))->group(
                function (): void {
                    Route::get('export-countries/{fileName}', 'exportCountries')->name('export_countries');
                }
            );
        });

        Route::controller(ShippingZoneController::class)->name('shipping_zones.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('shipping_zone'))->group(
                function (): void {
                    Route::get('shipping-zones', 'index')->name('index');
                    Route::get('fetch-shipping-zones', 'fetchShippingZones')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('shipping_zone'))->group(
                function (): void {
                    Route::get('shipping-zones/create', 'create')->name('create');
                    Route::post('shipping-zones', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('shipping_zone'))->group(
                function (): void {
                    Route::get('shipping-zones/{shippingZoneId}/edit', 'edit')->name('edit');
                    Route::post('shipping-zones/{shippingZoneId}', 'update')->name('update');
                }
            );
        });

        Route::controller(DynamicMenuController::class)->name('dynamic_menus.')->group(function (): void {
            Route::middleware('permission:' . PermissionList::getReadPermissionName('dynamic_menus'))->group(
                function (): void {
                    Route::get('dynamic-menus', 'index')->name('index');
                    Route::get('fetch-dynamic-menus', 'fetchDynamicMenus')->name('fetch');
                }
            );
            Route::middleware('permission:' . PermissionList::getWritePermissionName('dynamic_menus'))->group(
                function (): void {
                    Route::get('dynamic-menus/create/{parentId?}', 'create')->name('create');
                    Route::post('dynamic-menus', 'store')->name('store');
                }
            );
            Route::middleware('permission:' . PermissionList::getModifyPermissionName('dynamic_menus'))->group(
                function (): void {
                    Route::get('dynamic-menus/{dynamicMenuId}/edit', 'edit')->name('edit');
                    Route::put('dynamic-menus/{dynamicMenuId}', 'update')->name('update');
                }
            );
        });

        Route::controller(StockMovementSummaryReportController::class)->name('stock_movement_summary_reports.')->group(
            function (): void {
                Route::middleware(
                    'permission:' . PermissionList::getReadPermissionName('stock_movement_summary')
                )->group(
                    function (): void {
                        Route::get('stock-movement-summary-report', 'index')->name('index');
                        Route::get('fetch-stock-movement-summary-report', 'fetchStockMovementSummaryDetails')->name(
                            'fetch_details'
                        );
                        Route::get('stock-movement-summary-latest-data-sync', 'getLatestDataSync')->name(
                            'get_latest_data_sync'
                        );
                    }
                );
                Route::middleware(
                    'permission:' . PermissionList::getExportPermissionName('stock_movement_summary')
                )->group(
                    function (): void {
                        Route::get('print-stock-movement-report', 'printStockMovementDetails')->name(
                            'print_details'
                        );
                        Route::get(
                            'export-stock-movement-report/{filename}',
                            'exportStockMovementDetails'
                        )->name('export_details');
                    }
                );
            }
        );

        if ($retailPlanningService->isConfigured()) {
            Route::group([], base_path('routes/retail_planning_api.php'));
        }
    });
});
