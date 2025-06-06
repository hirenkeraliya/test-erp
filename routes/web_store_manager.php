<?php

declare(strict_types=1);

use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\StoreManager\Auth\ForgotPasswordController;
use App\Http\Controllers\StoreManager\Auth\LoginController;
use App\Http\Controllers\StoreManager\Auth\ResetPasswordController;
use App\Http\Controllers\StoreManager\BarCodeController;
use App\Http\Controllers\StoreManager\BatchExpiryController;
use App\Http\Controllers\StoreManager\BookingPaymentReportController;
use App\Http\Controllers\StoreManager\BrandController;
use App\Http\Controllers\StoreManager\CancelLayawaySaleController;
use App\Http\Controllers\StoreManager\CashierController;
use App\Http\Controllers\StoreManager\CashierGroupController;
use App\Http\Controllers\StoreManager\CashMovementReportController;
use App\Http\Controllers\StoreManager\CategoryController;
use App\Http\Controllers\StoreManager\CityController;
use App\Http\Controllers\StoreManager\ClosedCounterReportController;
use App\Http\Controllers\StoreManager\ColorController;
use App\Http\Controllers\StoreManager\CounterController;
use App\Http\Controllers\StoreManager\CreditNoteController;
use App\Http\Controllers\StoreManager\CreditSaleController;
use App\Http\Controllers\StoreManager\CustomReportController;
use App\Http\Controllers\StoreManager\DashboardController;
use App\Http\Controllers\StoreManager\DayCloseController;
use App\Http\Controllers\StoreManager\DayCloseReportController;
use App\Http\Controllers\StoreManager\DepartmentController;
use App\Http\Controllers\StoreManager\DesignationController;
use App\Http\Controllers\StoreManager\DifferentStoreReturnsController;
use App\Http\Controllers\StoreManager\DirectorController;
use App\Http\Controllers\StoreManager\EmployeeController;
use App\Http\Controllers\StoreManager\EmployeeGroupController;
use App\Http\Controllers\StoreManager\EmployeeSalesReportController;
use App\Http\Controllers\StoreManager\ExportRecordController;
use App\Http\Controllers\StoreManager\ExternalInventoryReportController;
use App\Http\Controllers\StoreManager\ExternalLocationController;
use App\Http\Controllers\StoreManager\GoodsReceivedNoteController;
use App\Http\Controllers\StoreManager\ImportRecordController;
use App\Http\Controllers\StoreManager\InventoryController;
use App\Http\Controllers\StoreManager\InventoryReportController;
use App\Http\Controllers\StoreManager\LayawaySaleController;
use App\Http\Controllers\StoreManager\MemberController;
use App\Http\Controllers\StoreManager\MemberReportController;
use App\Http\Controllers\StoreManager\MemberSalesReportController;
use App\Http\Controllers\StoreManager\NotificationController;
use App\Http\Controllers\StoreManager\OnlineProductsReportController;
use App\Http\Controllers\StoreManager\OrderController;
use App\Http\Controllers\StoreManager\OrderPickingListController;
use App\Http\Controllers\StoreManager\OrderReturnController;
use App\Http\Controllers\StoreManager\PaymentTypeReportController;
use App\Http\Controllers\StoreManager\PosAdminController;
use App\Http\Controllers\StoreManager\ProductAgeingReportController;
use App\Http\Controllers\StoreManager\ProductCollectionController;
use App\Http\Controllers\StoreManager\ProductController;
use App\Http\Controllers\StoreManager\ProductFilterController;
use App\Http\Controllers\StoreManager\ProductsReportController;
use App\Http\Controllers\StoreManager\PromoterCommissionController;
use App\Http\Controllers\StoreManager\PromoterController;
use App\Http\Controllers\StoreManager\PromoterGroupController;
use App\Http\Controllers\StoreManager\PurchaseOrderController;
use App\Http\Controllers\StoreManager\PurchaseOrderFulfillmentController;
use App\Http\Controllers\StoreManager\PurchaseOrderInvoiceController;
use App\Http\Controllers\StoreManager\ReservedInventoryReportController;
use App\Http\Controllers\StoreManager\SaleController;
use App\Http\Controllers\StoreManager\SaleExchangesReportController;
use App\Http\Controllers\StoreManager\SaleReturnController;
use App\Http\Controllers\StoreManager\SalesByPromoterController;
use App\Http\Controllers\StoreManager\SaleTargetController;
use App\Http\Controllers\StoreManager\SaleTargetReportController;
use App\Http\Controllers\StoreManager\SizeController;
use App\Http\Controllers\StoreManager\StateController;
use App\Http\Controllers\StoreManager\StockAdjustmentController;
use App\Http\Controllers\StoreManager\StockMovementLedgerReportController;
use App\Http\Controllers\StoreManager\StockTakeController;
use App\Http\Controllers\StoreManager\StockTransferController;
use App\Http\Controllers\StoreManager\StoreController;
use App\Http\Controllers\StoreManager\StoreManagerProfileController;
use App\Http\Controllers\StoreManager\StyleController;
use App\Http\Controllers\StoreManager\TagController;
use App\Http\Controllers\StoreManager\TransitInventoryReportController;
use App\Http\Controllers\StoreManager\TwoFactorController;
use App\Http\Controllers\StoreManager\VendorController;
use App\Http\Controllers\StoreManager\VoidSaleController;
use App\Http\Controllers\StoreManager\VoucherReportController;
use App\Http\Middleware\RedirectIfStoreIsNotSelected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('store-manager')->name('store_manager.')->group(function (): void {
    Route::inertia('menu/{pageUrl}', 'menu/Index')->name('menu_page');
    Route::group([
        'middleware' => 'guest',
    ], function (): void {
        Route::post(
            'login',
            fn (Request $request): RedirectResponse => (new LoginController())->login($request)
        )->name('login_user');
        Route::inertia('', 'guest/Login')->name('login');
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

    Route::group([
        'middleware' => ['auth:store_manager', 'twoFactor'],
    ], function (): void {
        Route::post('/generate2fa/{storeManagerId}', [TwoFactorController::class, 'generate2FA'])->name(
            'generate2fa'
        );
        Route::post('/disable2fa/{storeManagerId}', [TwoFactorController::class, 'disable2FA'])->name('disable2fa');

        Route::get('edit-profile', [StoreManagerProfileController::class, 'editProfile'])->name('edit_profile');
        Route::put('{storeManagerId}/update-profile', [StoreManagerProfileController::class, 'updateProfile'])->name(
            'update_profile'
        );

        Route::controller(StoreController::class)->group(function (): void {
            Route::get('store-selection', 'storeSelection')->name('store_selection');
            Route::post('set-store-selection', 'setSelectedStore')->name('set_selected_store');
            Route::get('get-stores', 'getAuthorizedStores')->name('get_authorized_stores');
        });
        Route::middleware([RedirectIfStoreIsNotSelected::class])->group(function (): void {
            Route::controller(OrderController::class)->name('orders.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getWritePermissionName('order'))->group(
                    function (): void {
                        Route::post('save-orders', 'saveDetails')->name('store');
                    }
                );
                Route::middleware('permission:' . PermissionList::getReadPermissionName('order'))->group(
                    function (): void {
                        Route::get('b2b-orders', 'b2bOrders')->name('b2bOrders');
                        Route::get('fetch-b2b-orders', 'fetchB2bOrders')->name('fetch_b2b_orders');
                        Route::get('marketplaces-orders', 'marketplacesOrders')->name('marketplaces_orders');
                        Route::get('fetch-marketplaces-orders', 'fetchMarketplacesOrders')->name(
                            'fetch_marketplaces_orders'
                        );
                        Route::get('order', 'create')->name('create');
                        Route::get('fetch-order-return-details/{orderId}', 'fetchOrderReturnDetails')->name(
                            'fetch_order_return_details'
                        );
                        Route::get('fetch-orders', 'fetchOrders')->name('fetch_orders');
                        Route::get('fetch-order-items', 'fetchOrderItemsByOrderId')->name('fetch_order_items');
                        Route::get('fetch-order-items-for-ecommerce', 'fetchOrderItemsEcommerceByOrderId')->name(
                            'fetch_order_items_for_ecommerce'
                        );
                        Route::get('fetch-order-return-details/{orderId}', 'fetchOrderReturnDetails')->name(
                            'fetch_order_return_details'
                        );
                        Route::get('fetch-order-address', 'fetchOrderAddress')->name('fetch_order_address');
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('order'))->group(
                    function (): void {
                        Route::post('cancel-order', 'cancelOrder')->name('cancel_order');
                        Route::post('complete-layaway-order', 'completeLayawayOrder')->name('complete_layaway_order');
                        Route::post('complete-credit-order', 'completeCreditOrder')->name('complete_credit_order');
                        Route::post('marketplaces-orders/{orderId}/accepted', 'accepted')->name('accepted');
                        Route::post('marketplaces-orders/{orderId}/cancelled', 'cancelled')->name('cancelled');
                        Route::post('marketplaces-orders/{orderId}/ready-for-pickup', 'readyForPickup')->name(
                            'ready_for_pickup'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('order'))->group(
                    function (): void {
                        Route::get('print-order-receipt/{orderId}', 'printOrderReceipt')->name('print_order_receipt');
                        Route::get('print-layaway-order-report/{orderId}', 'printLayawayOrderReport')->name(
                            'print_layaway_order_report'
                        );
                        Route::get('print-credit-order-report/{orderId}', 'printCreditOrderReport')->name(
                            'print_credit_order_report'
                        );
                        Route::get('print-order-tax-invoice/{orderId}', 'printOrderTaxInvoice')->name(
                            'print_order_tax_invoice'
                        );
                        Route::get('print-purchase-order/{orderId}', 'printPurchaseOrder')->name(
                            'print_purchase_order'
                        );
                        Route::get('print-ninja-van-way-bill/{orderId}', 'printNinjaVanWayBill')->name(
                            'print_ninja_van_way_bill'
                        );
                        Route::get('export-marketplace-orders/{fileName}', 'exportMarketplaceOrders')->name(
                            'export_marketplace_orders'
                        );
                        Route::get('print-marketplace-orders', 'printMarketplaceOrders')->name(
                            'print_marketplace_orders'
                        );
                    }
                );
            });
            Route::controller(OrderReturnController::class)->name('order_returns.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('order_return'))->group(
                    function (): void {
                        Route::get('order-returns', 'index')->name('index');
                        Route::get('fetch-order-returns', 'fetchOrderReturns')->name('fetch_order_returns');
                        Route::get('get-order-return-items/{orderReturnId}', 'fetchOrderReturnItems')->name(
                            'fetch_order_return_items'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('order_return'))->group(
                    function (): void {
                        Route::get(
                            'fetch-order-returns-for-receipt/{orderReturnId}',
                            'fetchOrderReturnsForReceipt'
                        )->name('fetch_order_return_for_receipt');
                    }
                );
                Route::post('order-return-details', 'store')->name('store');
            });
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
                    Route::get('store-revenue-view', 'storeRevenueView')->name('store_revenue');
                    Route::get('print-store-revenue', 'printStoreRevenue')->name('print_store_revenue');
                    Route::get('export-store-revenue/{fileName}', 'exportStoreRevenue')->name('export_store_revenue');
                });
                Route::middleware(
                    'permission:dashboard_' . PermissionList::DASHBOARD_STOCK_OVERVIEW->value
                )->group(function (): void {
                    Route::get('stock-overview', 'stockOverview')->name('stock_overview');
                    Route::get('get-this-month-top-selling-products', 'getThisMonthTopSellingProducts')->name(
                        'get_this_month_top_selling_products'
                    );
                    Route::get('get-this-year-top-selling-products', 'getThisYearTopSellingProducts')->name(
                        'get_this_year_top_selling_products'
                    );
                    Route::get('get-this-month-worst-selling-products', 'getThisMonthWorstSellingProducts')->name(
                        'get_this_month_worst_selling_products'
                    );
                    Route::get('get-this-year-worst-selling-products', 'getThisYearWorstSellingProducts')->name(
                        'get_this_year_worst_selling_products'
                    );
                    Route::get('get-transfer-order', 'getTransferOrder')->name('get_transfer_order');
                    Route::get('get-purchase-request', 'getPurchaseRequest')->name('get_purchase_request');
                    Route::get('get-transfer-request', 'getTransferRequest')->name('get_transfer_request');
                    Route::get('get-sales-order', 'getSalesOrder')->name('get_sales_order');
                    Route::get('get-purchase-order', 'getPurchaseOrder')->name('get_purchase_order');
                    Route::get('get-this-month-top-selling-colors', 'getThisMonthTopSellingColors')->name(
                        'get_this_month_top_selling_colors'
                    );
                    Route::get('get-this-year-top-selling-colors', 'getThisYearTopSellingColors')->name(
                        'get_this_year_top_selling_colors'
                    );
                    Route::get('get-transfer-order', 'getTransferOrder')->name('get_transfer_order');
                    Route::get('get-request-order', 'getRequestOrder')->name('get_request_order');
                    Route::get('get-transfer-out', 'getTransferOut')->name('get_transfer_out');
                    Route::get('get-transfer-in', 'getTransferIn')->name('get_transfer_in');
                    Route::get('get-low-stock-overview', 'getLowStockOverview')->name('get_low_stock_overview');
                    Route::get('get-no-stock-stock-overview', 'getNoStockStockOverview')->name(
                        'get_no_stock_stock_overview'
                    );
                    Route::get('get-negative-stock-stock-overview', 'getNegativeStockStockOverview')->name(
                        'get_negative_stock_stock_overview'
                    );
                    Route::get('get-top-ranking-products', 'getTopRankingProducts')
                        ->name('get_top_ranking_products');
                });
            });
            Route::controller(StockAdjustmentController::class)->name('stock_adjustments.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_adjustment'))->group(
                        function (): void {
                            Route::get('stock-adjustments', 'index')->name('index');
                            Route::get('fetch-stock-adjustments', 'fetchStockAdjustments')->name('fetch');
                            Route::get('fetch-items/{stockAdjustmentId}', 'fetchItems')->name('fetch_items');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('stock_adjustment')
                    )->group(
                        function (): void {
                            Route::get(
                                'export-stock-adjustment-items/{stockAdjustmentId}/{fileName}',
                                'exportItems'
                            )->name('export_items');
                            Route::get('export-stock-adjustment/{fileName}', 'exportStockAdjustments')->name(
                                'export_stock_adjustment'
                            );
                        }
                    );
                }
            );
            Route::controller(GoodsReceivedNoteController::class)->name('goods_received_notes.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('goods_received_note')
                    )->group(
                        function (): void {
                            Route::get('goods-received-notes', 'index')->name('index');
                            Route::get('fetch-goods-received-notes', 'fetchGoodsReceivedNotes')->name('fetch');
                            Route::get(
                                'get-goods-received-note-products/{goodsReceivedNoteId}',
                                'getGoodsReceivedNoteProducts'
                            )->name('products');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getWritePermissionName('goods_received_note')
                    )->group(
                        function (): void {
                            Route::get('goods-received-notes/create', 'create')->name('create');
                            Route::post('goods-received-notes', 'store')->name('store');
                            Route::put('re-upload-import-records/{goodsReceivedNoteId}', 'reUploadFailedRecord')->name(
                                're_upload_goods_received_note_record'
                            );
                            Route::put('goods-received-notes/{goodsReceivedNoteId}/cancel', 'markAsCancel')->name(
                                'mark_as_cancel'
                            );
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('goods_received_note')
                    )->group(
                        function (): void {
                            Route::get(
                                'goods-received-note-print/{goodsReceivedNoteId}',
                                'goodsReceivedNotePrint'
                            )->name('goods_received_note_print');
                            Route::get('export-goods-received-note/{fileName}', 'exportGoodReceivedNote')
                                ->name('export_goods_received_note');
                            Route::get(
                                'export-goods-received-note-products/{goodsReceivedNoteId}/{fileName}',
                                'exportGoodReceivedNoteProducts'
                            );
                        }
                    );
                }
            );
            Route::controller(StockTransferController::class)->name('stock_transfers.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_transfer'))->group(
                        function (): void {
                            Route::get('stock-transfers', 'index')->name('index');
                            Route::get('fetch-stock-transfers', 'fetchStockTransfers')->name('fetch');
                            Route::get('get-stock-transfer-types', 'getStockTransferTypes')->name(
                                'get_stock_transfer_types'
                            );
                            Route::get(
                                'fetch-stock-transfer-items/{stockTransferId}',
                                'fetchStockTransferItemByStockTransferId'
                            )->name('fetch_stock_transfer_items');
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getWritePermissionName('stock_transfer'))->group(
                        function (): void {
                            Route::get('fetch-aggregate-average-days', 'fetchAggregateAverageDays')->name(
                                'aggregate_average_days'
                            );
                            Route::get('stock-transfers/create/{transferType}', 'create')->name('create');
                            Route::post('stock-transfers', 'store')->name('store');
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_transfer'))->group(
                        function (): void {
                            Route::get('export-stock-transfers/{fileName}', 'exportStockTransfers')->name(
                                'export_stock_transfers'
                            );
                            Route::get(
                                'export-stock-transfer-items/{stockTransferId}/{fileName}',
                                'exportStockTransferItems'
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
                            )
                                ->name('update_shipping_details_and_mark_as_approved');
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
                            Route::put(
                                'stock-transfers/{stockTransferId}/update-additional-items',
                                'updateAdditionalItems'
                            )->name('update_additional_items');
                            Route::get('remove-additional-item/{stockTransferItemId}', 'removeAdditionalItem')->name(
                                'remove_additional_item'
                            );
                            Route::post(
                                'add-delivery-note-item-remarks/{stockTransferItemId}',
                                'deliveryNoteItemRemarks'
                            )
                                ->name('add_delivery_note_item_remarks');
                            Route::post('update-shipped-type/{stockTransferId}', 'markAsShippedOrTransit')
                                ->name('mark_as_shipped_or_transit');
                        }
                    );
                    Route::get('stock-transfers/{stockTransferId}/{transferType}/print', 'printStockTransfer')
                        ->name('print_stock_transfer');
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('stock_transfer_overview')
                    )->group(
                        function (): void {
                            Route::get('stock-transfers-overview', 'stockTransfersOverview')->name('overview');
                        }
                    );
                }
            );
            Route::controller(ProductFilterController::class)->group(function (): void {
                Route::get('get-filtered-products', 'getFilteredProducts')->name('get_filtered_products');
                Route::get('get-filtered-products-list', 'getFilteredProductsList')->name('get_filtered_products_list');
                Route::get('products/{productId}', 'getProduct')->name('get_product');
                Route::get('get-filtered-inventory-products-list', 'getFilteredInventoryProductsList')->name(
                    'get_filtered_inventory_products_list'
                );
                Route::get('get-filtered-inventory-products', 'getFilteredInventoryProducts')->name(
                    'get_filtered_inventory_products'
                );
                Route::get('get-product-lists', 'getInventoryProductsList')->name('get_product_lists');
            });
            Route::controller(InventoryController::class)->group(function (): void {
                Route::get('get-stocks', 'getStocks')->name('get_inventory_stocks');
                Route::get('get-location-stocks', 'getLocationStocksForPurchaseOrder')->name(
                    'get_location_inventory_stocks'
                );
            });
            Route::get(
                'get-stocks',
                fn (Request $request): array => (new InventoryController())->getStocks($request)
            )->name('get_inventory_stocks');
            Route::controller(ProductController::class)->name('products.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('product'))->group(
                    function (): void {
                        Route::get('products', 'index')->name('index');
                        Route::get('fetch-products', 'fetchProducts')->name('fetch');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('product'))->group(
                    function (): void {
                        Route::get('export-products/{fileName}', 'exportProducts')->name('export_products');
                        Route::get('print-products', 'printProducts')->name('print_products');
                        Route::get('export-loyalty-point-products/{fileName}', 'exportLoyaltyPointProducts')->name(
                            'export_loyalty_point_products'
                        );
                        Route::get('export-box-products/{fileName}', 'exportBoxProducts')->name(
                            'export_box_products'
                        );
                        Route::get('check-product-export-limit', 'checkProductExportLimit')->name(
                            'check_product_export_limit');
                        Route::get(
                            'check-product-loyalty-point-export-limit',
                            'checkProductLoyaltyPointExportLimit'
                        )->name('check_product_loyalty_export_limit');
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
                Route::post('get-matching-upc-inventory-products', 'getActiveInventoryProductsByUpcs')->name(
                    'get_matching_upc_inventory_products'
                );
                Route::post('search-by-article-number', 'searchByArticleNumber')->name('search_by_article_number');
                Route::post('search-by-article-number-with-stock', 'searchByArticleNumberWithStock')->name(
                    'search_by_article_number_with_stock'
                );
                Route::post(
                    'get-matching-upc-inventory-products-with-derivatives',
                    'getActiveInventoryProductsByUpcsWithDerivatives'
                )->name('get_matching_upc_inventory_products_with_derivatives');
                Route::post('get-products-article-numbers', 'getFilteredArticleNumber')->name(
                    'get_filtered_article_number'
                );
                Route::post('search-products-by-article-number', 'searchProductsByOnlyArticleNumber')->name(
                    'search_products_by_article_number'
                );
                Route::middleware('permission:product_' . PermissionList::PRODUCT_UPLOAD_IMAGE->value)->group(
                    function (): void {
                        Route::post('product-upload-image', 'uploadImage')->name('upload_image');
                    }
                );
            });
            Route::controller(CategoryController::class)->name('categories.')->group(function (): void {
                Route::post('get-filtered-categories', 'getFilteredCategories')->name('get_filtered_categories');
                Route::get('get-categories-list', 'getCategoriesList')->name('get_categories_list');
                Route::get('get-parent-categories', 'getParentCategories')->name('get_parent_categories');
            });
            Route::controller(BrandController::class)->name('brands.')->group(function (): void {
                Route::post('get-filtered-brands', 'getFilteredBrands')->name('get_filtered_brands');
                Route::post('get-brands', 'getBrands')->name('get_brands');
            });
            Route::controller(DayCloseController::class)->name('day_close_counters.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('day_close'))->group(
                    function (): void {
                        Route::get('day-close-counters', 'index')->name('index');
                        Route::get('day-close-counter-closing-details/{counterUpdateId}', 'counterClosingDetails')
                            ->name('counter_closing_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('day_close'))->group(
                    function (): void {
                        Route::post('close-counter/{counterUpdateId}', 'closeCounter')
                            ->name('close_counter');
                        Route::post('day-close', 'dayClose')->name('day_close');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('day_close'))->group(
                    function (): void {
                        Route::get('export-day-close/{fileName}', 'exportDayClose')->name('export_day_close');
                    }
                );
            });
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
                        Route::post('barcodes-print', 'productsBarcodePrint')->name('products_barcode_print');
                        Route::post('barcodes-print-download', 'downloadPdfEntry')->name('download_pdf_entry');
                        Route::get('export-barcode-records/{fileName}', 'ExportBarcodeRecords')->name('export_barcode');
                        Route::post('barcodes-print-manual', 'printTheBarcodeByManualProcess')->name(
                            'products_barcode_print_manual'
                        );
                        Route::get('view-print/{fileName}', 'viewPdf')->name('view_pdf');
                        Route::get('verify-file/{fileName}', 'isPDFFileExists')->name('is_pdf_file_exists');
                    }
                );
            });
            Route::controller(StockTakeController::class)->name('stock_takes.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_take'))->group(
                    function (): void {
                        Route::get('stock-takes', 'index')->name('index');
                        Route::get('get-stock-takes', 'fetchStockTake')->name('fetch');
                        Route::get(
                            'get-pending-stock-product-submission-count/{stockTakeId}',
                            'getPendingStockProductsSubmissionCount'
                        )->name('get_pending_stock_product_submission_count');
                        Route::get('stock-takes/{stockTakeId}/fetch-stock-take-products', 'fetchStockTakeProducts')
                            ->name('fetch_stock_take_products');
                        Route::get('stock-takes/{stockTakeId}/grand-total-submitted-stock', 'grandTotalSubmittedStock')
                            ->name('grand_total_submitted_stock');
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('stock_take'))->group(
                    function (): void {
                        Route::post('stock-takes/add', 'addStockTake')->name('add_stock_take');
                        Route::get('stock-takes/{stockTakeId}/products', 'stockTakeProducts')->name(
                            'stock_take_products'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('stock_take'))->group(
                    function (): void {
                        Route::post('stock-takes/{stockTakeId}/update-submitted-stocks', 'updateSubmittedStock')
                            ->name('update_submitted_stock');
                        Route::post(
                            'stock-takes/{stockTakeId}/update-submitted-stocks-by-id',
                            'updateSubmittedStockByStockId'
                        )->name('update_submitted_stock_by_stock_id');
                        Route::post('stock-takes/{stockTakeId}/submit/', 'submitStockTake')
                            ->name('submit');
                        Route::post('stock-takes/{stockTakeId}/bulk-updates-stocks', 'bulkUpdateStocks')
                            ->name('bulk_update_stocks');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_take'))->group(
                    function (): void {
                        Route::get('export-stock-takes/{fileName}', 'exportStockTakes')->name('export_stock_takes');
                        Route::get('export-stock-take-products/{stockTakeId}/{fileName}', 'exportStockTakeProducts');
                        Route::get(
                            'download-stock-take-products/{stockTakeId}/{fileName}',
                            'downloadStockTakeProducts'
                        );
                    }
                );
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
                    }
                );
            });
            Route::controller(CustomReportController::class)->name('custom_reports.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('custom_report'))->group(
                    function (): void {
                        Route::get('custom-reports', 'index')->name('index');
                        Route::get('stock-movement-report-print', 'stockMovementReportPrint')->name(
                            'stock_movement_report_print'
                        );
                        Route::get('sale-hour-print', 'saleHourPrint')->name('sale_hour_print');
                        Route::get('export-sale-hour/{filename}', 'exportSaleHour')->name('sale_hour_export');
                        Route::get('print-sales-collection', 'print')->name('print_sales_collection');
                        Route::get('export-sales-collection/{filename}', 'exportSaleCollection')->name(
                            'export_sales_collection'
                        );
                        Route::get('export-custom-stock-movement/{filename}', 'exportStockMovementReport')->name(
                            'stock_movement_report_export'
                        );
                        Route::get('print-sales-exchange', 'printExchange')->name('print_sales_exchange');
                        Route::get('print-void-report', 'printVoidReport')->name('print_void_report');
                        Route::get('export-void-report/{filename}', 'exportVoidReport');
                        Route::get('print-general-sales', 'printGeneralSale')->name('print_general_sales_report');
                        Route::get('export-general-sales-report/{filename}', 'exportGeneralSalesReport');
                        Route::get('print-top-twenty', 'printTopTwenty')->name('print_top_twenty');
                        Route::get('print-worst-twenty', 'printWorstTwenty')->name('print_worst_twenty');
                        Route::get('print-stock-card', 'printStockCard')->name('print_stock_card');
                        Route::get('print-stock-summary', 'printStockSummary')->name('print_stock_summary');
                        Route::get('print-cash-movement', 'printCashMovement')->name('print_cash_movements');
                        Route::get('export-cash-movement-report/{filename}', 'exportCashMovementsReport');
                        Route::get('print-sales-by-promoter', 'printSalesByPromoter')->name('print_sales_by_promoter');
                        Route::get('print-stock-transfer', 'printStockTransfer')->name('print_stock_transfer');
                        Route::get('print-stock-transfer-discrepancy', 'printStockTransferDiscrepancy')->name(
                            'print_stock_transfer_discrepancy'
                        );
                        Route::get('export-stock-transfer/{filename}', 'exportStockTransfer')->name(
                            'export_stock_transfer'
                        );
                        Route::get(
                            'export-stock-transfer-discrepancy/{filename}',
                            'exportStockTransferDiscrepancy'
                        )->name('export_stock_transfer_discrepancy');
                        Route::get('print-goods-received-note', 'printGoodsReceivedNote')->name(
                            'print_goods_received_note'
                        );
                        Route::get('print-sale-return', 'printSaleReturn')->name('print_sale_return');
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
                        Route::get('export-stock-summary-report/{filename}', 'exportStockSummaryReport');
                        Route::get('print-suspend-and-resume', 'printSuspendAndResume')->name(
                            'print_suspend_and_resume'
                        );
                        Route::get('export-suspend-and-resume/{filename}', 'exportSuspendAndResume');
                        Route::get('export-sale-return/{filename}', 'exportSaleReturn')->name('export_sale_return');
                        Route::get('export-sales-exchange/{filename}', 'exportExchange')->name('export_sales_exchange');
                        Route::get('print-discount_report', 'printDiscount')->name('print_discount_report');
                        Route::get('export-discount-report/{filename}', 'exportDiscountReport');
                        Route::get('stock-adjustment-report', 'printStockAdjustment')->name('print_stock_adjustment');
                        Route::get('export-stock-adjustment-report/{filename}', 'exportStockAdjustment');
                        Route::get('get-discount-type-reports', 'getDiscountTypeReports')->name(
                            'get_discount_type_reports'
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
                        Route::get('export-inter-company/{filename}', 'exportInterCompany')->name(
                            'export_inter_company'
                        );
                        Route::get('print-order-report', 'printOrderReport')->name('print_order_report');
                        Route::get('export-order-report/{filename}', 'exportOrderReport')->name('export_order_report');
                        Route::get('print-inter-company-invoice', 'printInterCompanyInvoiceReport')->name(
                            'print_inter_company_invoice'
                        );
                        Route::get('export-inter-company-invoice/{filename}', 'exportInterCompanyInvoiceReport')->name(
                            'export_inter_company_invoice'
                        );
                        Route::get('credit-sales-print', 'creditSalesPrint')->name('credit_sales_print');
                        Route::get('credit-sales-export/{filename}', 'creditSalesExport')->name('credit_sales_export');
                        Route::get('layaway-sales-print', 'layawaySalesPrint')->name('layaway_sales_print');
                        Route::get('layaway-sales-export/{filename}', 'layawaySalesExport')->name(
                            'layaway_sales_export'
                        );
                        Route::get('get-stores-and-warehouses', 'getStoresAndWareHouses')->name(
                            'get_stores_and_warehouses'
                        );
                        Route::get('get-sale-discount-type-reports', 'getSaleDiscountTypeReports')->name(
                            'get_sale_discount_type_reports'
                        );
                    });
            });
            Route::controller(CashierController::class)->name('cashiers.')->group(function (): void {
                Route::get('get-store-cashiers', 'getStoreCashiers')->name('get_store_cashiers');
                Route::post('get-specific-stores-cashiers', 'getSpecificStoresCashiers')->name(
                    'get_specific_stores_cashiers'
                );
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
            });
            Route::controller(PromoterGroupController::class)->name('promoter_groups.')->group(
                function (): void {
                    Route::get('get-promoter-groups-list', 'getPromoterGroupsList')->name('get_promoter_groups_list');
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
                }
            );
            Route::controller(CounterController::class)->name('counters.')->group(function (): void {
                Route::get('get-location-counters', 'getLocationCounters')->name('get_location_counters');
                Route::post('get-specific-locations-counters', 'getSpecificLocationsCounters')->name(
                    'get_specific_locations_counters'
                );
            });
            Route::controller(DepartmentController::class)->name('departments.')->group(function (): void {
                Route::post('get-filtered-departments', 'getFilteredDepartments')->name('get_filtered_departments');
                Route::get('get-departments-list', 'getDepartmentsList')->name('get_departments_list');
            });
            Route::controller(VendorController::class)->name('vendors.')->group(function (): void {
                Route::get('get-vendors-list', 'getVendorsList')->name('get_vendors_list');
            });
            Route::controller(ColorController::class)->name('colors.')->group(function (): void {
                Route::post('get-filtered-colors', 'getFilteredColors')->name('get_filtered_colors');
            });
            Route::controller(SizeController::class)->name('sizes.')->group(function (): void {
                Route::post('get-filtered-sizes', 'getFilteredSizes')->name('get_filtered_sizes');
            });
            Route::post(
                'logout',
                fn (Request $request): RedirectResponse => (new LoginController())->logout($request)
            )->name('logout');
            Route::controller(NotificationController::class)->name('notifications.')->group(function (): void {
                Route::get('fetch-notifications', 'fetchNotifications')->name('fetch');
                Route::get('fetch-read-notifications', 'fetchReadNotifications')->name('fetch_read_notification');
                Route::post('mark-all-as-read', 'markAllAsRead')->name('mark_all_as_read');
                Route::post('mark-as-read', 'markAsRead')->name('mark_as_read');
                Route::post('mark-as-unread', 'markAsUnRead')->name('mark_as_unread');
            });
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
            Route::controller(ReservedInventoryReportController::class)->name('reserved_inventory_reports.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('reserved_inventory')
                    )->group(
                        function (): void {
                            Route::get('reserved-inventory-reports', 'index')->name('index');
                            Route::get('fetch-reserved-inventory-report', 'fetchReservedInventoryReport')->name(
                                'fetch'
                            );
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('reserved_inventory')
                    )->group(
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
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('transit_inventory')
                    )->group(
                        function (): void {
                            Route::get('transit-inventory-reports', 'index')->name('index');
                            Route::get('fetch-transit-inventory-report', 'fetchTransitInventoryReport')->name('fetch');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('transit_inventory')
                    )->group(
                        function (): void {
                            Route::get('export-transit-inventory/{fileName}', 'exportTransitInventory')->name(
                                'export_transit_inventories'
                            );
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
                            Route::get(
                                'fetch-closed-counter-details/{counterUpdateId}',
                                'fetchClosedCounterDetails'
                            )->name('fetch_closed_counter_details');
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
            Route::controller(DayCloseReportController::class)->name('day_close_report.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('day_close_report'))->group(
                        function (): void {
                            Route::get('day-close-report', 'index')->name('index');
                            Route::get('fetch-day-close-report', 'fetchDayCloseReport')->name('fetch');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('day_close_report')
                    )->group(
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
                }
            );
            Route::controller(ProductsReportController::class)->name('products_report.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('product_report'))->group(
                        function (): void {
                            Route::get('products-report', 'index')->name('index');
                            Route::get('fetch-products-report', 'fetchProductsReport')->name('fetch');
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getExportPermissionName('product_report'))->group(
                        function (): void {
                            Route::get('export-products-report/{fileName}', 'exportProductsReport')->name(
                                'export_products_report'
                            );
                            Route::get('print-products-report', 'printProducts')->name('print_products_report');
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
                            Route::get('export-products-ageing-report/{fileName}', 'exportProductsAgeingReport')->name(
                                'export_products_ageing_report'
                            );
                            Route::get(
                                'print-products-ageing-report-by-article-number',
                                'printProductsAgeingReportByArticleNumber'
                            )->name('print_products_ageing_report_by_article_number');
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
                        'export-products-ageing-report-by-month-and-year/{fileName}',
                        'exportProductsAgeingReportByMonthAndYear'
                    )->name('export_products_ageing_report_by_month_and_year');
                    Route::get(
                        'print-products-ageing-report-by-month-and-year',
                        'printProductsAgeingByMonthAndYear'
                    )->name('print_products_ageing_report_by_month_and_year');
                    Route::get(
                        'fetch-consolidate-products-ageing-report-by-month-and-year',
                        'fetchConsolidateProductsAgeingReportByMonthAndYear'
                    )->name('fetch_consolidate_by_month_and_year');
                }
            );
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
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('booking_payment')
                    )->group(
                        function (): void {
                            Route::get('export-booking-payments/{fileName}', 'exportBookingPayments')->name(
                                'export_booking_payments'
                            );
                            Route::get('print-booking-payment/{bookingPaymentId}', 'printBookingPayment')->name(
                                'print_booking_payment'
                            );
                        }
                    );
                }
            );
            Route::controller(SaleController::class)->name('sales.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sale'))->group(
                    function (): void {
                        Route::get('sales', 'index')->name('index');
                        Route::get('fetch-sales', 'fetchRegularSales')->name('fetch');
                        Route::get('fetch-sale-items/{saleId}', 'fetchSaleItemsBySaleId')->name('fetch_sale_items');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sale'))->group(
                    function (): void {
                        Route::get('export-sales/{fileName}', 'exportSales');
                    }
                );
            });
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
            Route::controller(MemberController::class)->name('members.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('member'))->group(
                    function (): void {
                        Route::get('members', 'index')->name('index');
                        Route::get('members-purchase-details/{memberId}', 'memberDetails')->name('member_details');
                        Route::get('fetch-members-purchase-details/{memberId}', 'fetchMemberDetails')
                            ->name('fetch_member_details');
                        Route::get('fetch-members', 'fetchMembers')->name('fetch');
                        Route::get('fetch-member-sale-details', 'fetchMemberSaleDetails')->name(
                            'fetch_member_sale_details'
                        );
                        Route::get('fetch-member-sale-return-details', 'fetchMemberSaleReturnDetails')->name(
                            'fetch_member_sale_return_details'
                        );
                        Route::get('member-registration', 'memberRegistration')->name('member_registration');
                        Route::get('fetch-member-addresses/{memberId}', 'fetchMemberAddresses')->name(
                            'fetch_member_addresses'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getWritePermissionName('member'))->group(
                    function (): void {
                        Route::get('members/create', 'create')->name('create');
                        Route::post('members', 'store')->name('store');
                        Route::post('create-member-for-new-order', 'createMemberForNewOrder')->name(
                            'add_new_member_for_order'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getModifyPermissionName('member'))->group(
                    function (): void {
                        Route::get('members/{memberId}/edit', 'edit')->name('edit');
                        Route::put('members/{memberId}', 'update')->name('update');
                        Route::put('update-member-addresses/{memberId}', 'updateMemberAddresses')->name(
                            'update_member_addresses'
                        );
                        Route::get('delete-member-address/{memberAddressId}', 'deleteMemberAddress')->name(
                            'delete_member_address'
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
                Route::get('get-filtered-members', 'getFilteredMembers')->name('get_filtered_members');
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
            Route::controller(SalesByPromoterController::class)->name('sales_by_promoters.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('sales_by_promoter')
                    )->group(
                        function (): void {
                            Route::get('sales-by-promoters', 'index')->name('index');
                            Route::get('fetch-sales-by-promoters', 'fetchSalesByPromoters')->name('fetch');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('sales_by_promoter')
                    )->group(
                        function (): void {
                            Route::get('sales-by-promoters/{fileName}', 'exportSalesByPromoters')->name(
                                'export_sales_by_promoters'
                            );
                        }
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
                            Route::get('export-member-sales/{fileName}', 'exportMemberSales')->name(
                                'export_member_sales'
                            );
                        }
                    );
                }
            );
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
                    }
                );
            });
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
                        Route::get('export-layaway-sales/{fileName}', 'exportLayawaySales')->name(
                            'export_layaway_sales'
                        );
                        Route::get('print-layaway-sale/{saleId}', 'printLayawaySale')->name('print_layaway_sale');
                        Route::get('print-layaway-sale-tax-invoice/{saleId}', 'printSaleTaxInvoice')->name(
                            'print_sale_tax_invoice'
                        );
                    }
                );
            });
            Route::controller(CancelLayawaySaleController::class)->name('cancel_layaway_sales.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('cancel_layaway_sale')
                    )->group(
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
                            Route::get(
                                'export-different-store-returns/{fileName}',
                                'exportDifferentStoreReturns'
                            )->name('export_sale_returns');
                        }
                    );
                }
            );

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
            });

            Route::controller(CreditNoteController::class)->name('credit_notes.')->group(function (): void {
                Route::get('credit-notes', 'index')->name('index');
                Route::get('fetch-credit-notes', 'fetchCreditNotes')->name('fetch');
                Route::get('export-credit-notes/{fileName}', 'exportCreditNotes')->name('export_credit_notes');
            });
            Route::controller(PaymentTypeReportController::class)->name('payment_type_report.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('payment_type_report')
                    )->group(
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
            Route::controller(PromoterController::class)->name('promoters.')->group(function (): void {
                Route::get('get-store-promoters', 'getStorePromoters')->name('get_store_promoters');
                Route::get('get-store-active-promoters', 'getStoreActivePromoters')->name('get_store_active_promoters');
                Route::middleware('permission:' . PermissionList::getReadPermissionName('promoter'))->group(
                    function (): void {
                        Route::get('promoters', 'index')->name('index');
                        Route::get('fetch-promoters', 'fetchPromoters')->name('fetch');
                        Route::get('promoters/{promoterId}/change-password', 'changePassword')->name('change_password');
                    }
                );
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
                        Route::put('promoters/{promoterId}/update-password', 'updatePassword')->name(
                            'update_password'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('promoter'))->group(
                    function (): void {
                        Route::get('export-promoters/{fileName}', 'exportPromoters')->name('export_promoters');
                    }
                );
            });
            Route::controller(EmployeeController::class)->name('employees.')->group(function (): void {
                Route::get('get-filtered-employees', 'getFilteredEmployees')->name('get_filtered_employees');
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
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('employee'))->group(
                    function (): void {
                        Route::get('export-employees/{fileName}', 'exportEmployees')->name('export_employees');
                    }
                );
                Route::post('employees/{employeeId}/set-status/{status}', 'setStatus')->name('set_status');
            });
            Route::controller(PosAdminController::class)->name('pos_admin.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('app_releases'))->group(
                    function (): void {
                        Route::get('app-releases', 'index')->name('index');
                    }
                );
            });
            Route::controller(TagController::class)->name('tags.')->group(function (): void {
                Route::post('get-filtered-tags', 'getFilteredTags')->name('get_filtered_tags');
                Route::get('get-tag-list', 'getTagsList')->name('get_tags_list');
            });
            Route::controller(StyleController::class)->name('styles.')->group(function (): void {
                Route::post('get-filtered-styles', 'getFilteredStyles')->name('get_filtered_styles');
                Route::get('get-style-list', 'getStylesList')->name('get_styles_list');
            });
            Route::controller(ProductCollectionController::class)->name('product_collections.')->group(
                function (): void {
                    Route::post('get-filtered-product-collection', 'getFilteredProductCollections')->name(
                        'get_filtered_product_collection'
                    );
                }
            );
            Route::controller(SaleTargetController::class)->name('sale_targets.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('sale_target'))->group(
                    function (): void {
                        Route::get('sale-targets', 'index')->name('index');
                        Route::get('fetch-sale-targets', 'fetchSaleTargets')->name('fetch');
                        Route::get('fetch-sale-target/{saleTargetId}', 'fetchSaleTarget')->name('fetch_sale_target');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('sale_target'))->group(
                    function (): void {
                        Route::get('export-sale-targets/{fileName}', 'exportSaleTargets')->name('export');
                    }
                );
            });
            Route::controller(VoucherReportController::class)->name('vouchers.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('voucher'))->group(
                    function (): void {
                        Route::get('vouchers', 'index')->name('index');
                        Route::get('fetch-vouchers', 'fetchVouchers')->name('fetch');
                        Route::get('fetch-voucher-details/{voucherId}', 'fetchVoucherDetails')->name(
                            'fetch_voucher_details'
                        );
                        Route::get(
                            'fetch-voucher-transaction-details/{voucherId}',
                            'fetchVoucherTransactionDetails'
                        )->name('fetch_voucher_transaction_details');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('voucher'))->group(
                    function (): void {
                        Route::get('export-vouchers/{fileName}', 'exportVouchers')->name('export_vouchers');
                        Route::get(
                            'print-voucher-transaction-details/{voucherId}',
                            'printVoucherTransactionDetails'
                        )->name('print_voucher_transaction_details');
                    }
                );
            });
            Route::controller(PurchaseOrderController::class)->name('purchase_orders.')->group(
                function (): void {
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
                            Route::get(
                                'export-purchase-order-items/{purchaseOrderId}/{fileName}',
                                'exportPurchaseOrderItems'
                            );
                            Route::get('purchase-order/{purchaseOrderId}/print', 'print')->name('print');
                            Route::get('export-purchase-orders/{fileName}', 'exportPurchaseOrders');
                        }
                    );
                }
            );
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
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getWritePermissionName('purchase_order'))->group(
                        function (): void {
                            Route::get(
                                'purchase-order-fulfillments/{purchaseOrderId}/shipping-details',
                                'shippingDetails'
                            )
                            ->name('shipping_details');
                            Route::put(
                                'purchase-order-fulfillments/{purchaseOrderId}/add-shipping-details',
                                'addShippingDetails'
                            )->name('add_shipping_details');
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getModifyPermissionName('purchase_order'))->group(
                        function (): void {
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
                            Route::post(
                                'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/mark-as-open',
                                'markAsOpen'
                            )->name('mark_as_open');
                            Route::post(
                                'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/closed',
                                'closed'
                            )->name('closed');
                            Route::post(
                                'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/discrepancy',
                                'discrepancy'
                            )->name('discrepancy');
                            Route::get('purchase-order-fulfillments/{purchaseOrderFulfillmentId}/edit', 'edit')->name(
                                'edit'
                            );
                            Route::put(
                                'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/update',
                                'update'
                            )->name('update');
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
                        'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/remove-discrepancy-proof',
                        'removeDiscrepancyProof'
                    )->name('remove_discrepancy_proof');
                    Route::get(
                        'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/remove-additional-item',
                        'removeAdditionalItem'
                    )->name('remove_additional_item');
                    Route::get(
                        '/export-purchase-order-fulfillment-items/{purchaseOrderFulfillmentId}/{fileName}',
                        'exportPurchaseOrderFulfillmentItems'
                    );
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
                    Route::post(
                        'purchase-order-fulfillments/{purchaseOrderFulfillmentId}/partial-receive',
                        'partialReceive'
                    )->name('partial_receive');
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
                            Route::get('refresh-prices/{purchaseOrderId}', 'refreshPrice')->name(
                                'refresh_purchase_cost'
                            );
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
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('purchase_order_invoice')
                    )->group(
                        function (): void {
                            Route::get('purchase-order-invoices/{purchaseOrderInvoiceId}/print', 'print')->name(
                                'print'
                            );
                        }
                    );
                }
            );
            Route::controller(SaleTargetReportController::class)->name('sale_achieved_targets.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('sale_achieved_target')
                    )->group(
                        function (): void {
                            Route::get('sale-achieved-targets', 'index')->name('index');
                            Route::get('fetch-sale-achieved-targets', 'fetchSaleAchievedTargets')->name('fetch');
                            Route::get(
                                'get-sales-and-sales-returns-for-sale-achieved-target/{saleAchievedTargetId}',
                                'getSalesAndSalesReturnsForSaleAchievedTarget'
                            )->name('fetch_sales_and_returns_for_sale_achieved_target');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('sale_achieved_target')
                    )->group(
                        function (): void {
                            Route::get('export-sale-achieved-target/{fileName}', 'exportSaleAchievedTarget')->name(
                                'export_sale_achieved_target'
                            );
                        }
                    );
                }
            );
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
            Route::controller(EmployeeGroupController::class)->name('employee_groups.')->group(
                function (): void {
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
                        Route::put('directors/{directorId}/update-passcode', 'updatePasscode')->name(
                            'update_passcode'
                        );
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('director'))->group(
                    function (): void {
                        Route::get('export-directors/{fileName}', 'exportDirectors')->name('export_directors');
                    }
                );
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
            Route::controller(PromoterCommissionController::class)->name('promoter_commission.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('commission'))->group(
                        function (): void {
                            Route::get('promoter-commission', 'index')->name('index');
                            Route::get('fetch-promoter-commission', 'fetCommissionsByPromoters')->name('fetch');
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
            Route::controller(BatchExpiryController::class)->name('batch_expiry.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('batch_expiry'))->group(
                    function (): void {
                        Route::get('batch-expiry', 'index')->name('index');
                        Route::get('fetch-batch-expiry', 'fetchBatchExpiry')->name('fetch_batch_expiry');
                    }
                );
                Route::middleware('permission:' . PermissionList::getExportPermissionName('cashier_group'))->group(
                    function (): void {
                        Route::get('export-batch-expiry/{fileName}', 'exportBatchExpiry')->name('export_batch_expiry');
                    }
                );
            });
            Route::controller(ExternalInventoryReportController::class)->name('external_inventory_reports.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('external_inventory')
                    )->group(
                        function (): void {
                            Route::get('external-inventory-reports', 'index')->name('index');
                            Route::get('fetch-external-inventories', 'fetchExternalInventories')->name('fetch');
                            Route::get('get-stores-and-regions', 'getStoresAndRegions')->name(
                                'get_stores_and_regions'
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
                    Route::middleware(
                        'permission:' . PermissionList::getExportPermissionName('external_inventory')
                    )->group(
                        function (): void {
                            Route::get('export-external-inventories/{fileName}', 'exportExternalInventories')->name(
                                'export_external_inventories'
                            );
                        }
                    );
                }
            );
            Route::controller(OrderPickingListController::class)->name('order_picking_lists.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('order_picking_lists')
                    )->group(
                        function (): void {
                            Route::get('order-picking-lists', 'index')->name('index');
                            Route::get('fetch-order-picking-lists', 'fetchOrderPickingLists')->name('fetch');
                            Route::get(
                                'fetch-order-picking-list-items/{orderPickingId}',
                                'fetchOrderItemsByOrderPickingId'
                            )->name('fetch_order_picking_list_items');
                        }
                    );
                    Route::get('print-order-packaging/{orderPickingListId}', 'printOrderPackaging')->name(
                        'print_order_packaging'
                    );
                    Route::get('print-order-packing-list/{orderPickingListId}', 'printOrderPackingList')->name(
                        'print_order_packing_list'
                    );
                    Route::post('order-picking-lists', 'store')->name('store');
                    Route::post('order-picking-lists/{orderPickingId}/completed', 'completed')->name('completed');
                    Route::post('order-picking-lists/{orderPickingId}/inprogress', 'inprogress')->name('inprogress');
                    Route::post('order-picking-lists/{orderPickingId}/cancel', 'cancel')->name('cancel');
                    Route::get('print-ninja-van-way-bills/{orderPickingId}', 'printNinjaVanWayBills')->name(
                        'print_ninja_van_way_bills'
                    );
                }
            );
        });
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
        Route::controller(StateController::class)->name('states.')->group(function (): void {
            Route::get('get-states/{country_id}', 'getStatesByCountryId')->name('get_states');
        });

        Route::controller(CityController::class)->name('cities.')->group(function (): void {
            Route::get('get-cities/{state_id}', 'getCitiesByStateId')->name('get_cities');
        });
    });
});
