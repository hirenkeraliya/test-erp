<?php

declare(strict_types=1);

use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\WarehouseManager\Auth\ForgotPasswordController;
use App\Http\Controllers\WarehouseManager\Auth\LoginController;
use App\Http\Controllers\WarehouseManager\Auth\ResetPasswordController;
use App\Http\Controllers\WarehouseManager\BarCodeController;
use App\Http\Controllers\WarehouseManager\BatchExpiryController;
use App\Http\Controllers\WarehouseManager\BrandController;
use App\Http\Controllers\WarehouseManager\CategoryController;
use App\Http\Controllers\WarehouseManager\ColorController;
use App\Http\Controllers\WarehouseManager\CustomReportController;
use App\Http\Controllers\WarehouseManager\DashboardController;
use App\Http\Controllers\WarehouseManager\DepartmentController;
use App\Http\Controllers\WarehouseManager\ExportRecordController;
use App\Http\Controllers\WarehouseManager\ExternalInventoryReportController;
use App\Http\Controllers\WarehouseManager\ExternalLocationController;
use App\Http\Controllers\WarehouseManager\ExternalLoginController;
use App\Http\Controllers\WarehouseManager\GoodsReceivedNoteController;
use App\Http\Controllers\WarehouseManager\ImportRecordController;
use App\Http\Controllers\WarehouseManager\InventoryController;
use App\Http\Controllers\WarehouseManager\InventoryReportController;
use App\Http\Controllers\WarehouseManager\NotificationController;
use App\Http\Controllers\WarehouseManager\ProductCollectionController;
use App\Http\Controllers\WarehouseManager\ProductController;
use App\Http\Controllers\WarehouseManager\ProductFilterController;
use App\Http\Controllers\WarehouseManager\PurchaseOrderController;
use App\Http\Controllers\WarehouseManager\PurchaseOrderFulfillmentController;
use App\Http\Controllers\WarehouseManager\PurchaseOrderInvoiceController;
use App\Http\Controllers\WarehouseManager\ReservedInventoryReportController;
use App\Http\Controllers\WarehouseManager\SizeController;
use App\Http\Controllers\WarehouseManager\StockAdjustmentController;
use App\Http\Controllers\WarehouseManager\StockMovementLedgerReportController;
use App\Http\Controllers\WarehouseManager\StockTakeController;
use App\Http\Controllers\WarehouseManager\StockTransferController;
use App\Http\Controllers\WarehouseManager\StyleController;
use App\Http\Controllers\WarehouseManager\TagController;
use App\Http\Controllers\WarehouseManager\TransitInventoryReportController;
use App\Http\Controllers\WarehouseManager\TwoFactorController;
use App\Http\Controllers\WarehouseManager\VendorController;
use App\Http\Controllers\WarehouseManager\WarehouseController;
use App\Http\Controllers\WarehouseManager\WarehouseManagerProfileController;
use App\Http\Middleware\RedirectIfWarehouseIsNotSelected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('warehouse-manager')->name('warehouse_manager.')->group(function (): void {
    Route::inertia('menu/{pageUrl}', 'menu/Index')->name('menu_page');
    Route::get(
        'logging',
        fn (Request $request): RedirectResponse => (new ExternalLoginController())->logging($request)
    )->name('logging');
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
        'middleware' => ['auth:warehouse_manager', 'twoFactor'],
    ], function (): void {
        Route::controller(WarehouseController::class)->group(function (): void {
            Route::get('warehouse-selection', 'warehouseSelection')->name('warehouse_selection');
            Route::post('set-warehouse-selection', 'setSelectedWarehouse')->name('set_selected_warehouse');
            Route::get('get-warehouses', 'getAuthorizedWarehouses')->name('get_authorized_warehouses');
        });

        Route::post(
            '/generate2fa/{warehouseManagerId}',
            [TwoFactorController::class, 'generate2FA']
        )->name('generate2fa');
        Route::post(
            '/disable2fa/{warehouseManagerId}',
            [TwoFactorController::class, 'disable2FA']
        )->name('disable2fa');

        Route::get(
            '/edit-profile',
            [WarehouseManagerProfileController::class, 'editProfile']
        )->name('edit_profile');

        Route::put(
            '{warehouseManagerId}/update-profile',
            [WarehouseManagerProfileController::class, 'updateProfile']
        )->name('update');

        Route::middleware([RedirectIfWarehouseIsNotSelected::class])->group(function (): void {
            Route::controller(DashboardController::class)->group(function (): void {
                Route::get('dashboard', 'index')->name('dashboard');
                Route::middleware('permission:dashboard_' . PermissionList::DASHBOARD_STOCK_OVERVIEW->value)->group(
                    function (): void {
                        Route::get('get-transfer-order', 'getTransferOrder')->name('get_transfer_order');
                        Route::get('get-purchase-request', 'getPurchaseRequest')->name('get_purchase_request');
                        Route::get('get-transfer-request', 'getTransferRequest')->name('get_transfer_request');
                        Route::get('get-sales-order', 'getSalesOrder')->name('get_sales_order');
                        Route::get('get-purchase-order', 'getPurchaseOrder')->name('get_purchase_order');
                        Route::get('get-request-order', 'getRequestOrder')->name('get_request_order');
                        Route::get('get-transfer-out', 'getTransferOut')->name('get_transfer_out');
                        Route::get('get-transfer-in', 'getTransferIn')->name('get_transfer_in');
                        Route::get('stock-overview', 'stockOverview')->name('stock_overview');
                        Route::get('get-low-stock-overview', 'getLowStockOverview')->name('get_low_stock_overview');
                        Route::get('get-no-stock-stock-overview', 'getNoStockStockOverview')->name(
                            'get_no_stock_stock_overview'
                        );
                        Route::get('get-negative-stock-stock-overview', 'getNegativeStockStockOverview')->name(
                            'get_negative_stock_stock_overview'
                        );
                    }
                );
            });
            Route::post(
                'logout',
                fn (Request $request): RedirectResponse => (new LoginController())->logout($request)
            )->name('logout');
            Route::controller(NotificationController::class)->name('notifications.')->group(function (): void {
                Route::get('fetch-notifications', 'fetchNotifications')->name('fetch');
                Route::post('mark-all-as-read', 'markAllAsRead')->name('mark_all_as_read');
                Route::get('fetch-read-notifications', 'fetchReadNotifications')->name('fetch_read_notification');
                Route::post('mark-as-read', 'markAsRead')->name('mark_as_read');
                Route::post('mark-as-unread', 'markAsUnRead')->name('mark_as_unread');
            });
            Route::controller(StockTransferController::class)->name('stock_transfers.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('stock_transfer'))->group(
                        function (): void {
                            Route::get('stock-transfers', 'index')->name('index');
                            Route::get('fetch-stock-transfers', 'fetchStockTransfers')->name('fetch');
                            Route::get('fetch-aggregate-average-days', 'fetchAggregateAverageDays')->name(
                                'aggregate_average_days'
                            );
                            Route::get(
                                'fetch-stock-transfer-items/{stockTransferId}',
                                'fetchStockTransferItemByStockTransferId'
                            )->name('fetch_stock_transfer_items');
                            Route::get('get-stock-transfer-types', 'getStockTransferTypes')->name(
                                'get_stock_transfer_types'
                            );
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getWritePermissionName('stock_transfer'))->group(
                        function (): void {
                            Route::get('stock-transfers/create/{transferType}', 'create')->name('create');
                            Route::post('stock-transfers', 'store')->name('store');
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getExportPermissionName('stock_transfer'))->group(
                        function (): void {
                            Route::get(
                                'export-stock-transfer-items/{stockTransferId}/{fileName}',
                                'exportStockTransferItems'
                            );
                            Route::get('export-stock-transfers/{fileName}', 'exportStockTransfers')->name(
                                'export_stock_transfers'
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
                            )
                                ->name('update_additional_items');
                            Route::get('remove-additional-item/{stockTransferItemId}', 'removeAdditionalItem')
                                ->name('remove_additional_item');
                            Route::post(
                                'add-delivery-note-item-remarks/{stockTransferItemId}',
                                'deliveryNoteItemRemarks'
                            )
                                ->name('add_delivery_note_item_remarks');
                            Route::post('update-shipped-type/{stockTransferId}', 'markAsShippedOrTransit')
                                        ->name('mark_as_shipped_or_transit');
                        }
                    );
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('stock_transfer_overview')
                    )->group(
                        function (): void {
                            Route::get('stock-transfers-overview', 'stockTransfersOverview')->name('overview');
                        }
                    );
                    Route::get('stock-transfers/{stockTransferId}/{transferType}/print', 'printStockTransfer')
                            ->name('print_stock_transfer');
                }
            );
            Route::controller(CategoryController::class)->name('categories.')->group(function (): void {
                Route::post('get-filtered-categories', 'getFilteredCategories')->name('get_filtered_categories');
                Route::get('get-categories-list', 'getCategoriesList')->name('get_categories_list');
            });
            Route::controller(BrandController::class)->name('brands.')->group(function (): void {
                Route::post('get-filtered-brands', 'getFilteredBrands')->name('get_filtered_brands');
                Route::post('get-brands', 'getBrands')->name('get_brands');
            });
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
            });
            Route::controller(BarCodeController::class)->name('barcode_prints.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('barcode'))->group(
                    function (): void {
                        Route::get('barcodes', 'index')->name('index');
                        Route::get('get-export-records-pending-status-counts', 'getPendingExportRecordCount')->name(
                            'get_pending_export_record_count'
                        );
                        Route::get('fetch-barcode-records', 'fetchBarcodeRecords')->name('fetch_barcodes');
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
                Route::post('get-matching-upc-inventory-products', 'getActiveInventoryProductsByUpcs')->name(
                    'get_matching_upc_inventory_products'
                );
                Route::post('get-matching-upc-products', 'getMatchingUpcProducts')->name('get_matching_upc_products');
                Route::post('search-by-article-number', 'searchByArticleNumber')->name('search_by_article_number');
                Route::post('get-products-article-numbers', 'getFilteredArticleNumber')->name(
                    'get_filtered_article_number'
                );
                Route::post('search-products-by-article-number', 'searchProductsByOnlyArticleNumber')->name(
                    'search_products_by_article_number'
                );
                Route::post('search-by-article-number-with-stock', 'searchByArticleNumberWithStock')->name(
                    'search_by_article_number_with_stock'
                );
                Route::post(
                    'get-matching-upc-inventory-products-with-derivatives',
                    'getActiveInventoryProductsByUpcsWithDerivatives'
                )->name('get_matching_upc_inventory_products_with_derivatives');
            });
            Route::controller(ColorController::class)->name('colors.')->group(function (): void {
                Route::post('get-filtered-colors', 'getFilteredColors')->name('get_filtered_colors');
            });
            Route::controller(SizeController::class)->name('sizes.')->group(function (): void {
                Route::post('get-filtered-sizes', 'getFilteredSizes')->name('get_filtered_sizes');
            });
            Route::controller(DepartmentController::class)->name('departments.')->group(function (): void {
                Route::post('get-filtered-departments', 'getFilteredDepartments')->name('get_filtered_departments');
                Route::get('get-departments-list', 'getDepartmentsList')->name('get_departments_list');
            });
            Route::controller(VendorController::class)->name('vendors.')->group(function (): void {
                Route::get('get-vendors-list', 'getVendorsList')->name('get_vendors_list');
            });
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
                            Route::post('re-upload-import-records/{goodsReceivedNoteId}', 'reUploadFailedRecord')->name(
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
                            Route::get('export-goods-received-note/{fileName}', 'exportGoodReceivedNote')->name(
                                'export_goods_received_note'
                            );
                            Route::get(
                                'export-goods-received-note-products/{goodsReceivedNoteId}/{fileName}',
                                'exportGoodReceivedNoteProducts'
                            );
                        }
                    );
                }
            );
            Route::controller(InventoryController::class)->group(function (): void {
                Route::get('get-stocks', 'getStocks')->name('get_inventory_stocks');
                Route::get('get-location-stocks', 'getLocationStocksForPurchaseOrder')->name(
                    'get_location_inventory_stocks'
                );
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
                        Route::post('stock-takes/{stockTakeId}/submit/', 'submitStockTake')
                            ->name('submit');
                        Route::post('stock-takes/{stockTakeId}/bulk-updates-stocks', 'bulkUpdateStocks')
                                        ->name('bulk_update_stocks');
                        Route::post(
                            'stock-takes/{stockTakeId}/update-submitted-stocks-by-id',
                            'updateSubmittedStockByStockId'
                        )->name('update_submitted_stock_by_stock_id');
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
            Route::controller(CustomReportController::class)->name('custom_reports.')->group(function (): void {
                Route::middleware('permission:' . PermissionList::getReadPermissionName('custom_report'))->group(
                    function (): void {
                        Route::get('custom-reports', 'index')->name('index');
                        Route::get('stock-movement-report-print', 'stockMovementReportPrint')->name(
                            'stock_movement_report_print'
                        );
                        Route::get('export-custom-stock-movement/{filename}', 'exportStockMovementReport')->name(
                            'stock_movement_report_export'
                        );
                        Route::get('print-stock-card', 'printStockCard')->name('print_stock_card');
                        Route::get('print-stock-summary', 'printStockSummary')->name('print_stock_summary');
                        Route::get('print-stock-transfer', 'printStockTransfer')->name('print_stock_transfer');
                        Route::get('export-stock-transfer/{filename}', 'exportStockTransfer')->name(
                            'export_stock_transfer'
                        );
                        Route::get('print-goods-received-note', 'printGoodsReceivedNote')->name(
                            'print_goods_received_note'
                        );
                        Route::get('export-stock-card/{filename}', 'exportStockCard');
                        Route::get('export-goods-received-note-report/{filename}', 'exportGoodsReceivedNote');
                        Route::get('export-stock-summary-report/{filename}', 'exportStockSummaryReport');
                        Route::get('stock-adjustment-report', 'printStockAdjustment')->name('print_stock_adjustment');
                        Route::get('export-stock-adjustment-report/{filename}', 'exportStockAdjustment');
                        Route::get('print-stock-transfer-discrepancy', 'printStockTransferDiscrepancy')->name(
                            'print_stock_transfer_discrepancy'
                        );
                        Route::get(
                            'export-stock-transfer-discrepancy/{filename}',
                            'exportStockTransferDiscrepancy'
                        )->name('export_stock_transfer_discrepancy');
                        Route::get('print-inter-company', 'printInterCompany')->name('print_inter_company');
                        Route::get('export-inter-company/{filename}', 'exportInterCompany')->name(
                            'export_inter_company'
                        );
                        Route::get('print-inter-company-invoice', 'printInterCompanyInvoiceReport')->name(
                            'print_inter_company_invoice'
                        );
                        Route::get('export-inter-company-invoice/{filename}', 'exportInterCompanyInvoiceReport')->name(
                            'export_inter_company_invoice'
                        );
                        Route::get('get-stores-and-warehouses', 'getStoresAndWareHouses')->name(
                            'get_stores_and_warehouses'
                        );
                    });
            });
            Route::controller(TagController::class)->name('tags.')->group(function (): void {
                Route::post('get-filtered-tags', 'getFilteredTags')->name('get_filtered_tags');
            });
            Route::controller(StyleController::class)->name('styles.')->group(function (): void {
                Route::post('get-filtered-styles', 'getFilteredStyles')->name('get_filtered_styles');
            });
            Route::controller(ProductCollectionController::class)->name('product_collections.')->group(
                function (): void {
                    Route::post('get-filtered-product-collection', 'getFilteredProductCollections')->name(
                        'get_filtered_product_collection'
                    );
                }
            );
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
                            Route::get(
                                'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/remove-discrepancy-proof',
                                'removeDiscrepancyProof'
                            )->name('remove_discrepancy_proof');
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
                            Route::get(
                                'purchase-order-fulfillments/{purchaseOrderFulfillmentItemId}/remove-additional-item',
                                'removeAdditionalItem'
                            )
                            ->name('remove_additional_item');
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
            Route::controller(ExternalLoginController::class)->name('external_logins.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('external_login'))->group(
                        function (): void {
                            Route::get('external-login', 'index')->name('index');
                            Route::get(
                                'get-external-login-details/{externalCompanyId}',
                                'getExternalLoginDetails'
                            )->name('get_external_login_details');
                        }
                    );
                }
            );
            Route::controller(BatchExpiryController::class)->name('batch_expiry.')->group(
                function (): void {
                    Route::middleware('permission:' . PermissionList::getReadPermissionName('batch_expiry'))->group(
                        function (): void {
                            Route::get('batch-expiry', 'index')->name('index');
                            Route::get('fetch-batch-expiry', 'fetchBatchExpiry')->name('fetch_batch_expiry');
                        }
                    );
                    Route::middleware('permission:' . PermissionList::getExportPermissionName('cashier_group'))->group(
                        function (): void {
                            Route::get('export-batch-expiry/{fileName}', 'exportBatchExpiry')->name(
                                'export_batch_expiry'
                            );
                        }
                    );
                }
            );
            Route::controller(ExternalInventoryReportController::class)->name('external_inventory_reports.')->group(
                function (): void {
                    Route::middleware(
                        'permission:' . PermissionList::getReadPermissionName('external_inventory')
                    )->group(
                        function (): void {
                            Route::get('external-inventory-reports', 'index')->name('index');
                            Route::get('fetch-external-inventories', 'fetchExternalInventories')->name('fetch');
                            Route::get('get-warehouses-and-regions', 'getWarehousesAndRegions')->name(
                                'get_warehouses_and_regions'
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
    });
});
