<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Jobs;

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotification\Mail\SendAutomatedNotificationMail;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\SellingTypes;
use App\Models\AutomatedNotification;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AutomatedNotificationLowStockJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $automatedNotificationId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotification = $automatedNotificationQueries->getByIdWithRelationsForJob(
            $this->automatedNotificationId
        );

        try {
            if ($automatedNotification->automatedNotificationProducts->isNotEmpty() && $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value) {
                $this->productWiseAutomatedNotification($automatedNotification);
            }

            if ($automatedNotification->automatedNotificationStores->isNotEmpty() && $automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_LOCATION->value) {
                $this->storeWiseAutomatedNotification($automatedNotification);
            }

            if ($automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_COMPANY->value) {
                $this->companyWiseAutomatedNotification($automatedNotification);
            }
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Low Stock Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }

    public function productWiseAutomatedNotification(AutomatedNotification $automatedNotification): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allLocations = $locationQueries->getInventoryForLowStockNotificationProduct($automatedNotification);

        foreach ($allLocations as $location) {
            $productWiseDataPrepare = [];

            if ($location->total_record_count <= 0) {
                continue;
            }

            $inventories = $location->inventories;

            foreach ($inventories as $inventory) {
                /** @var Product $product */
                $product = $inventory->product;

                $linkForStoreManager = $this->getStoreManagerUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_PRODUCT->value,
                    $product->id
                );
                $linkForWarehouseManager = $this->getWarehouseManagerUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_PRODUCT->value,
                    $product->id
                );
                $linkForAdmin = $this->getAdminUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_PRODUCT->value,
                    $product->id
                );

                $productWiseDataPrepare['products'][] = [
                    'product_name' => $product->name,
                    'article_number' => $product->article_number,
                    'store_manager_link' => $linkForStoreManager,
                    'warehouse_manager_link' => $linkForWarehouseManager,
                    'admin_link' => $linkForAdmin,
                ];
            }

            $this->sendEmailAndNotificationForStoreManager(
                $location,
                $automatedNotification,
                $productWiseDataPrepare,
                Types::LOW_STOCK_PRODUCT->value
            );
            $this->sendEmailAndNotificationForWarehouseManager(
                $location,
                $automatedNotification,
                $productWiseDataPrepare,
                Types::LOW_STOCK_PRODUCT->value
            );

            $msg = 'Here are the products that are running low on stock in '. $location->name;

            $route = route('admin.inventory_reports.index', [
                'stock_type' => Types::LOW_STOCK_PRODUCT->value,
                'location_id' => $location->id,
                'location_type' => $location->type_id,
                'status' => ProductStatuses::ACTIVE->value,
                'selling_type' => SellingTypes::SELLING->value,
            ]);
            $link = '<a href=' . $route . ' class="text-primary underline">' . $location->total_record_count . ' more products</a>';

            $productWiseDataPrepare['count_link'] = $location->total_record_count > 10 ? $link : null;

            $this->sendEmailForRecipients($automatedNotification, $productWiseDataPrepare, $msg);
        }
    }

    public function storeWiseAutomatedNotification(AutomatedNotification $automatedNotification): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allLocations = $locationQueries->getInventoryForLowStockNotificationLocation($automatedNotification);

        foreach ($allLocations as $location) {
            $productWiseDataPrepare = [];

            if ($location->total_record_count <= 0) {
                continue;
            }

            $inventories = $location->inventories;

            foreach ($inventories as $inventory) {
                /** @var Product $product */
                $product = $inventory->product;

                $linkForStoreManager = $this->getStoreManagerUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_LOCATION->value
                );
                $linkForWarehouseManager = $this->getWarehouseManagerUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_LOCATION->value
                );
                $linkForAdmin = $this->getAdminUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_LOCATION->value,
                    $product->id
                );

                $productWiseDataPrepare['products'][] = [
                    'product_name' => $product->name,
                    'article_number' => $product->article_number,
                    'admin_link' => $linkForAdmin,
                    'store_manager_link' => $linkForStoreManager,
                    'warehouse_manager_link' => $linkForWarehouseManager,
                ];
            }

            $this->sendEmailAndNotificationForStoreManager(
                $location,
                $automatedNotification,
                $productWiseDataPrepare,
                Types::LOW_STOCK_LOCATION->value
            );
            $this->sendEmailAndNotificationForWarehouseManager(
                $location,
                $automatedNotification,
                $productWiseDataPrepare,
                Types::LOW_STOCK_LOCATION->value
            );

            $msg = 'Here are the products that are running low on stock in '. $location->name;

            $route = route('admin.inventory_reports.index', [
                'stock_type' => Types::LOW_STOCK_LOCATION->value,
                'location_id' => $location->id,
                'location_type' => $location->type_id,
                'status' => ProductStatuses::ACTIVE->value,
                'selling_type' => SellingTypes::SELLING->value,
            ]);
            $link = '<a href=' . $route . ' class="text-primary underline">' . $location->total_record_count . ' more products</a>';

            $productWiseDataPrepare['count_link'] = $location->total_record_count > 10 ? $link : null;

            $this->sendEmailForRecipients($automatedNotification, $productWiseDataPrepare, $msg);
        }
    }

    public function companyWiseAutomatedNotification(AutomatedNotification $automatedNotification): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allLocations = $locationQueries->getInventoryForLowStockNotificationCompany($automatedNotification);

        foreach ($allLocations as $location) {
            $productWiseDataPrepare = [];

            if ($location->total_record_count <= 0) {
                continue;
            }

            $inventories = $location->inventories;

            foreach ($inventories as $inventory) {
                /** @var Product $product */
                $product = $inventory->product;

                $linkForStoreManager = $this->getStoreManagerUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_COMPANY->value
                );
                $linkForWarehouseManager = $this->getWarehouseManagerUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_COMPANY->value
                );
                $linkForAdmin = $this->getAdminUrl(
                    $location->id,
                    $location->type_id,
                    Types::LOW_STOCK_COMPANY->value,
                    $product->id
                );

                $productWiseDataPrepare['products'][] = [
                    'product_name' => $product->name,
                    'article_number' => $product->article_number,
                    'store_manager_link' => $linkForStoreManager,
                    'warehouse_manager_link' => $linkForWarehouseManager,
                    'admin_link' => $linkForAdmin,
                ];
            }

            $this->sendEmailAndNotificationForStoreManager(
                $location,
                $automatedNotification,
                $productWiseDataPrepare,
                Types::LOW_STOCK_COMPANY->value
            );
            $this->sendEmailAndNotificationForWarehouseManager(
                $location,
                $automatedNotification,
                $productWiseDataPrepare,
                Types::LOW_STOCK_COMPANY->value
            );

            $msg = 'Here are the products that are running low on stock in '. $location->name;

            $route = route('admin.inventory_reports.index', [
                'stock_type' => Types::LOW_STOCK_COMPANY->value,
                'location_id' => $location->id,
                'location_type' => $location->type_id,
                'status' => ProductStatuses::ACTIVE->value,
                'selling_type' => SellingTypes::SELLING->value,
            ]);
            $link = '<a href=' . $route . ' class="text-primary underline">' . $location->total_record_count . ' more products</a>';

            $productWiseDataPrepare['count_link'] = $location->total_record_count > 10 ? $link : null;
            $this->sendEmailForRecipients($automatedNotification, $productWiseDataPrepare, $msg);
        }
    }

    private function sendEmailForRecipients(
        AutomatedNotification $automatedNotification,
        array $data,
        string $msg
    ): void {
        if (! array_key_exists('products', $data)) {
            return;
        }

        foreach ($automatedNotification->automatedEmailRecipients as $automatedEmailRecipient) {
            try {
                Mail::to($automatedEmailRecipient->receiver_email)
                    ->send(new SendAutomatedNotificationMail($automatedNotification, $msg, $data));
            } catch (Throwable $throwable) {
                Log::error('Automated Notification Job Error', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'Email' => $automatedEmailRecipient->receiver_email,
                    'txt Message' => $msg,
                    'Email Recipient' => $automatedEmailRecipient,
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);
            }
        }
    }

    private function sendEmailAndNotificationForStoreManager(
        Location $location,
        AutomatedNotification $automatedNotification,
        array $productWiseDataPrepare,
        int $typeId,
    ): void {
        if (! array_key_exists('products', $productWiseDataPrepare)) {
            return;
        }

        $notificationQueries = resolve(NotificationQueries::class);

        /* @phpstan-ignore-next-line */
        $totalCount = $location->total_record_count;

        $route = route('store_manager.inventory_reports.index', [
            'stock_type' => $typeId,
            'location_id' => $location->id,
            'location_type' => $location->type_id,
            'status' => ProductStatuses::ACTIVE->value,
            'selling_type' => SellingTypes::SELLING->value,
        ]);
        $link = '<a href=' . $route . ' class="text-primary underline">' . $totalCount . ' more products</a>';

        $linkForNotification = '<a href=' . $route . ' class="text-primary underline">' . $totalCount . '</a>';

        $productWiseDataPrepare['count_link'] = $totalCount > 10 ? $link : null;

        foreach ($location->storeManagers as $storeManager) {
            $message = 'There are ' . $linkForNotification . ' products low stock in the ' . $location->name . ' location.';
            $textMessage = 'There are ' . $totalCount . ' products low stock in the ' . $location->name . ' location.';

            $notificationQueries->addNew(
                $automatedNotification->company_id,
                null,
                null,
                ModelMapping::STORE_MANAGER->name,
                $storeManager->id,
                $message,
                null,
                $textMessage,
                null,
            );

            if ($storeManager->employee && $storeManager->employee->email) {
                try {
                    Mail::to($storeManager->employee->email)
                        ->send(new SendAutomatedNotificationMail($automatedNotification, $textMessage, [
                            'store_manager' => true,
                            'route' => $route,
                            'location_name' => $location->name,
                            'data' => $productWiseDataPrepare,
                        ]));
                } catch (Throwable $throwable) {
                    Log::error('Automated Notification Job Error', [
                        'error_message' => 'Error message: ' . $throwable->getMessage(),
                        'error_code' => 'Error code: ' . $throwable->getCode(),
                        'Email' => $storeManager->employee->email,
                        'txt Message' => $textMessage,
                        'Store Manager' => $storeManager,
                        'file' => 'File: ' . $throwable->getFile(),
                        'line' => 'Line: ' . $throwable->getLine(),
                        'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                        'Full error' => [$throwable],
                    ]);
                }
            }
        }
    }

    private function sendEmailAndNotificationForWarehouseManager(
        Location $location,
        AutomatedNotification $automatedNotification,
        array $productWiseDataPrepare,
        int $typeId
    ): void {
        if (! array_key_exists('products', $productWiseDataPrepare)) {
            return;
        }

        $notificationQueries = resolve(NotificationQueries::class);

        /* @phpstan-ignore-next-line */
        $totalCount = $location->total_record_count;

        $route = route('warehouse_manager.inventory_reports.index', [
            'stock_type' => $typeId,
            'location_id' => $location->id,
            'location_type' => $location->type_id,
            'status' => ProductStatuses::ACTIVE->value,
            'selling_type' => SellingTypes::SELLING->value,
        ]);
        $link = '<a href=' . $route . ' class="text-primary underline">' . $totalCount . ' more products</a>';

        $linkForNotification = '<a href=' . $route . ' class="text-primary underline">' . $totalCount . '</a>';

        $productWiseDataPrepare['count_link'] = $totalCount > 10 ? $link : null;

        foreach ($location->warehouseManagers as $warehouseManager) {
            $message = 'There are ' . $linkForNotification . ' products low stock in the ' . $location->name . ' location.';
            $textMessage = 'There are ' . $totalCount . ' products low stock in the ' . $location->name . ' location.';

            $notificationQueries->addNew(
                $automatedNotification->company_id,
                null,
                null,
                ModelMapping::STORE_MANAGER->name,
                $warehouseManager->id,
                $message,
                null,
                $textMessage,
                null,
            );

            if ($warehouseManager->employee && $warehouseManager->employee->email) {
                try {
                    Mail::to($warehouseManager->employee->email)
                        ->send(new SendAutomatedNotificationMail($automatedNotification, $textMessage, [
                            'warehouse_manager' => true,
                            'route' => $route,
                            'location_name' => $location->name,
                            'data' => $productWiseDataPrepare,
                        ]));
                } catch (Throwable $throwable) {
                    Log::error('Automated Notification Job Error', [
                        'error_message' => 'Error message: ' . $throwable->getMessage(),
                        'error_code' => 'Error code: ' . $throwable->getCode(),
                        'Email' => $warehouseManager->employee->email,
                        'txt Message' => $textMessage,
                        'Store Manager' => $warehouseManager,
                        'file' => 'File: ' . $throwable->getFile(),
                        'line' => 'Line: ' . $throwable->getLine(),
                        'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                        'Full error' => [$throwable],
                    ]);
                }
            }
        }
    }

    private function getStoreManagerUrl(int $locationId, int $locationType, int $typeId, ?int $productId = null): string
    {
        $parameters = [
            'stock_type' => $typeId,
            'location_id' => $locationId,
            'location_type' => $locationType,
            'status' => ProductStatuses::ACTIVE->value,
            'selling_type' => SellingTypes::SELLING->value,
        ];

        if ($productId) {
            $parameters['product_id'] = $productId;
        }

        $routeForStoreManager = route('store_manager.inventory_reports.index', $parameters);

        return '<a href=' . $routeForStoreManager . ' class="text-primary underline">view</a>';
    }

    private function getWarehouseManagerUrl(
        int $locationId,
        int $locationType,
        int $typeId,
        ?int $productId = null
    ): string {
        $parameters = [
            'stock_type' => $typeId,
            'location_id' => $locationId,
            'location_type' => $locationType,
            'status' => ProductStatuses::ACTIVE->value,
            'selling_type' => SellingTypes::SELLING->value,
        ];

        if ($productId) {
            $parameters['product_id'] = $productId;
        }

        $routeForWarehouseManager = route('warehouse_manager.inventory_reports.index', $parameters);

        return '<a href=' . $routeForWarehouseManager . ' class="text-primary underline">view</a>';
    }

    private function getAdminUrl(int $locationId, int $locationType, int $typeId, ?int $productId = null): string
    {
        $parameters = [
            'stock_type' => $typeId,
            'location_id' => $locationId,
            'location_type' => $locationType,
            'status' => ProductStatuses::ACTIVE->value,
            'selling_type' => SellingTypes::SELLING->value,
        ];

        if ($productId) {
            $parameters['product_id'] = $productId;
        }

        $routeForAdmin = route('admin.inventory_reports.index', $parameters);

        return '<a href=' . $routeForAdmin . ' class="text-primary underline">view</a>';
    }
}
