<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationDeadlineRequestStockJob;
use App\Domains\AutomatedNotification\Mail\SendAutomatedNotificationMail;
use App\Domains\Company\CompanyQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\Notification\NotificationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\AutomatedNotification;
use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
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
    'it triggers automated deadline request stock notifications',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);

        $emailRecipient = EmailRecipient::factory()->make([
            'company_id' => 1,
            'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        ]);

        $automatedNotification->automatedEmailRecipients = collect([$emailRecipient]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByStoreCompanyId')
                ->once()
                ->andReturn(collect([$this->storeManager]));
        });

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByWarehouseCompanyId')
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

        AutomatedNotificationDeadlineRequestStockJob::dispatch($automatedNotification->id, 1)->onQueue(
            config('horizon.default_queue_name')
        );
    }
);

test(
    'it triggers automated deadline request stock notifications when mail is not sent when recipients are not attached',
    function (): void {
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 2,
            'type_id' => AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);

        $automatedNotification->automatedEmailRecipients = collect([]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByStoreCompanyId')
                ->once()
                ->andReturn(collect([$this->storeManager]));
        });

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByWarehouseCompanyId')
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

        AutomatedNotificationDeadlineRequestStockJob::dispatch($automatedNotification->id, 1)->onQueue(
            config('horizon.default_queue_name')
        );

        Mail::assertNotSent(SendAutomatedNotificationMail::class);
    }
);
