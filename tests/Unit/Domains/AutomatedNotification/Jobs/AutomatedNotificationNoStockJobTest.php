<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationNoStockJob;
use App\Domains\AutomatedNotification\Mail\SendAutomatedNotificationMail;
use App\Domains\Company\CompanyQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\AutomatedNotification;
use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->product = commonGetProductDetails();

    Inventory::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'stock' => 9,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $this->warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
});

test(
    'it triggers automated no stock notifications',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::NO_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);

        $emailRecipient = EmailRecipient::factory()->make([
            'company_id' => 1,
            'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        ]);

        $automatedNotification->automatedEmailRecipients = collect([$emailRecipient]);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
                ->andReturn(collect([$this->location]));

            $mock->shouldReceive('getWithBasicColumnsOfWarehouse')
            ->once()
                ->andReturn(collect([$this->location]));
        });

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductCountOutOfStock')
                ->times(2)
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllStoreManagerWithStore')
                ->once()
                ->andReturn(collect([$this->storeManager]));
        });

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllWarehouseManagerWithWarehouse')
                ->once()
                ->andReturn(collect([$this->warehouseManager]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->andReturn(new Company());
        });

        AutomatedNotificationNoStockJob::dispatch($automatedNotification->id, 1)->onQueue(
            config('horizon.default_queue_name')
        );
    }
);

test(
    'it triggers automated no stock notifications when mail is not sent when recipients are not attached',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::NO_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);

        $automatedNotification->automatedEmailRecipients = collect([]);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
                ->andReturn(collect([$this->location]));

            $mock->shouldReceive('getWithBasicColumnsOfWarehouse')
            ->once()
                ->andReturn(collect([$this->location]));
        });

        $this->mock(InventoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductCountOutOfStock')
                ->times(2)
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllStoreManagerWithStore')
                ->once()
                ->andReturn(collect([$this->storeManager]));
        });

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllWarehouseManagerWithWarehouse')
                ->once()
                ->andReturn(collect([$this->warehouseManager]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->andReturn(new Company());
        });

        Mail::fake();

        AutomatedNotificationNoStockJob::dispatch($automatedNotification->id, 1)->onQueue(
            config('horizon.default_queue_name')
        );

        Mail::assertNotSent(SendAutomatedNotificationMail::class);
    }
);
