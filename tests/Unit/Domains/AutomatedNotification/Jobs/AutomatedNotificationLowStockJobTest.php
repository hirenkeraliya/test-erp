<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationLowStockJob;
use App\Domains\AutomatedNotification\Mail\SendAutomatedNotificationMail;
use App\Domains\Company\CompanyQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationProduct;
use App\Models\AutomatedNotificationStore;
use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->store = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->warehouse = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $this->product = commonGetProductDetails();
    $this->product1 = commonGetProductDetails();
    $this->product1->id = 2;

    $this->inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'location_id' => $this->store->id,
        'stock' => 9,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'email' => 'test@example.com',
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
});

test(
    'it triggers automated low stock notifications',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
            'sent_notification' => true,
        ]);

        $this->mock(AutomatedNotificationQueries::class, function ($mock) use ($automatedNotification): void {
            $mock->shouldReceive('getByIdWithRelationsForJob')
                ->once()
                ->andReturn($automatedNotification);
        });

        $emailRecipient = EmailRecipient::factory()->make([
            'company_id' => 1,
            'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        ]);

        $automatedNotification->automatedEmailRecipients = collect([$emailRecipient]);
        $automatedNotification->automatedNotificationProducts = collect([]);
        $automatedNotification->automatedNotificationStores = collect([]);
        $automatedNotification->products = collect([]);

        $this->storeManager->employee = $this->employee;

        $this->store->total_record_count = 1;
        $this->store->storeManagers = collect([$this->storeManager]);
        $this->store->warehouseManagers = collect([]);
        $this->store->inventories = collect([$this->inventory]);

        $this->inventory->product = $this->product;

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getInventoryForLowStockNotificationCompany')
                ->once()
                ->andReturn(collect([$this->store]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->andReturn(new Company());
        });

        Mail::fake();

        AutomatedNotificationLowStockJob::dispatch(
            $automatedNotification->id,
        )->onQueue(config('horizon.default_queue_name'));

        Mail::assertSent(SendAutomatedNotificationMail::class, 2);
    }
);

test(
    'it triggers automated low stock notifications when automatedNotificationProducts is attached and type is low stock product',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
            'sent_notification' => true,
        ]);

        $emailRecipient = EmailRecipient::factory()->make([
            'company_id' => 1,
            'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        ]);

        $automatedNotificationProducts = AutomatedNotificationProduct::factory()->make([
            'automated_notification_id' => $automatedNotification->id,
            'product_id' => $this->product1->id,
            'location_id' => $this->store->id,
            'low_stock_alert_threshold' => 10,
        ]);

        $this->mock(AutomatedNotificationQueries::class, function ($mock) use ($automatedNotification): void {
            $mock->shouldReceive('getByIdWithRelationsForJob')
                ->andReturn($automatedNotification);
        });

        $automatedNotification->automatedEmailRecipients = collect([$emailRecipient]);
        $automatedNotification->automatedNotificationProducts = collect([$automatedNotificationProducts]);
        $automatedNotification->automatedNotificationStores = collect([]);

        $this->inventory->product_id = $this->product1->id;
        $this->inventory->product = $this->product1;
        $this->inventory->location = $this->store;

        $this->storeManager->employee = $this->employee;

        $this->store->total_record_count = 1;
        $this->store->inventories = collect([$this->inventory]);
        $this->store->storeManagers = collect([$this->storeManager]);
        $this->store->warehouseManagers = collect([]);

        $this->warehouse->total_record_count = 0;

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getInventoryForLowStockNotificationProduct')
                ->once()
                ->andReturn(collect([$this->store]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->andReturn(new Company());
        });

        Mail::fake();

        AutomatedNotificationLowStockJob::dispatch(
            $automatedNotification->id,
        )->onQueue(config('horizon.default_queue_name'));

        Mail::assertSent(SendAutomatedNotificationMail::class);
    }
);

test(
    'it triggers automated low stock notifications when automatedNotificationStores is attached and type is low stock location',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_LOCATION->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
            'sent_notification' => true,
        ]);

        $this->mock(AutomatedNotificationQueries::class, function ($mock) use ($automatedNotification): void {
            $mock->shouldReceive('getByIdWithRelationsForJob')
                ->once()
                ->andReturn($automatedNotification);
        });

        $emailRecipient = EmailRecipient::factory()->make([
            'company_id' => 1,
            'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        ]);

        $automatedNotificationStores = AutomatedNotificationStore::factory()->make([
            'automated_notification_id' => $automatedNotification->id,
            'location_id' => $this->store->id,
            'low_stock_alert_threshold' => 10,
        ]);

        $automatedNotification->automatedEmailRecipients = collect([$emailRecipient]);
        $automatedNotification->automatedNotificationProducts = collect([]);
        $automatedNotification->automatedNotificationStores = collect([$automatedNotificationStores]);

        $this->inventory->product_id = $this->product1->id;
        $this->inventory->product = $this->product1;

        $this->storeManager->employee = $this->employee;

        $this->store->total_record_count = 1;
        $this->store->inventories = collect([$this->inventory]);
        $this->store->storeManagers = collect([$this->storeManager]);
        $this->store->warehouseManagers = collect([]);

        $this->warehouse->total_record_count = 0;

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getInventoryForLowStockNotificationLocation')
                ->once()
                ->andReturn(collect([$this->store]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
                ->andReturn(new Company());
        });

        Mail::fake();

        AutomatedNotificationLowStockJob::dispatch(
            $automatedNotification->id,
        )->onQueue(config('horizon.default_queue_name'));

        Mail::assertSent(SendAutomatedNotificationMail::class);
    }
);

test(
    'it triggers automated low stock notifications when mail is not sent when recipients are not attached',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
            'sent_notification' => true,
        ]);

        $this->mock(AutomatedNotificationQueries::class, function ($mock) use ($automatedNotification): void {
            $mock->shouldReceive('getByIdWithRelationsForJob')
                ->once()
                ->andReturn($automatedNotification);
        });

        $automatedNotification->automatedEmailRecipients = collect([]);
        $automatedNotification->automatedNotificationProducts = collect([]);
        $automatedNotification->automatedNotificationStores = collect([]);

        $this->storeManager->employee = $this->employee;

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getInventoryForLowStockNotificationCompany')
                ->once()
                ->andReturn(collect([$this->store]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew');
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getById')
            ->andReturn(new Company());
        });

        Mail::fake();

        AutomatedNotificationLowStockJob::dispatch(
            $automatedNotification->id,
        )->onQueue(config('horizon.default_queue_name'));

        Mail::assertNotSent(SendAutomatedNotificationMail::class);
    }
);
