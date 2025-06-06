<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationDeadlineRequestStockJob;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationJob;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationLowStockJob;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationNoStockJob;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationRequestStockJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationMonthDate;
use App\Models\AutomatedNotificationWeekDay;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;

test(
    'it triggers low stock notifications on the specified day of the month',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);

        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
            'sent_notification' => true,
        ]);

        $automatedNotificationMonthDate = AutomatedNotificationMonthDate::make([
            'automated_notification_id' => $automatedNotification->id,
            'month_date' => now()->day,
        ]);

        $automatedNotification->monthly = new Collection([$automatedNotificationMonthDate]);
        $automatedNotification->weekly = new Collection([]);

        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);

        Queue::assertPushed(AutomatedNotificationLowStockJob::class);
    }
);

test(
    'it triggers low stock notifications on the specified day of the week',
    function (): void {
        Queue::fake();

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);

        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            'company_id' => 1,
            'sent_notification' => true,
        ]);

        $automatedNotificationWeekDay = AutomatedNotificationWeekDay::make([
            'automated_notification_id' => $automatedNotification->id,
            'week_day' => now()->dayOfWeek,
        ]);

        $automatedNotification->monthly = new Collection([]);
        $automatedNotification->weekly = new Collection([$automatedNotificationWeekDay]);

        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);

        Queue::assertPushed(AutomatedNotificationLowStockJob::class);
    }
);

test(
    'it triggers no stock notifications on the specified day of the month',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);
        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::NO_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);
        $automatedNotificationMonthDate = AutomatedNotificationMonthDate::make([
            'automated_notification_id' => $automatedNotification->id,
            'month_date' => now()->day,
        ]);
        $automatedNotification->monthly = new Collection([$automatedNotificationMonthDate]);
        $automatedNotification->weekly = new Collection([]);
        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);
        Queue::assertPushed(AutomatedNotificationNoStockJob::class);
    }
);

test(
    'it triggers no stock notifications on the specified day of the week',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);
        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::NO_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            'company_id' => 1,
        ]);
        $automatedNotificationWeekDay = AutomatedNotificationWeekDay::make([
            'automated_notification_id' => $automatedNotification->id,
            'week_day' => now()->dayOfWeek,
        ]);
        $automatedNotification->monthly = new Collection([]);
        $automatedNotification->weekly = new Collection([$automatedNotificationWeekDay]);
        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);
        Queue::assertPushed(AutomatedNotificationNoStockJob::class);
    }
);

test(
    'it triggers request stock notifications on the specified day of the month',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);
        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::REQUEST_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);
        $automatedNotificationMonthDate = AutomatedNotificationMonthDate::make([
            'automated_notification_id' => $automatedNotification->id,
            'month_date' => now()->day,
        ]);
        $automatedNotification->monthly = new Collection([$automatedNotificationMonthDate]);
        $automatedNotification->weekly = new Collection([]);
        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);
        Queue::assertPushed(AutomatedNotificationRequestStockJob::class);
    }
);

test(
    'it triggers request stock notifications on the specified day of the week',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);
        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::REQUEST_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            'company_id' => 1,
        ]);
        $automatedNotificationWeekDay = AutomatedNotificationWeekDay::make([
            'automated_notification_id' => $automatedNotification->id,
            'week_day' => now()->dayOfWeek,
        ]);
        $automatedNotification->monthly = new Collection([]);
        $automatedNotification->weekly = new Collection([$automatedNotificationWeekDay]);
        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);
        Queue::assertPushed(AutomatedNotificationRequestStockJob::class);
    }
);

test(
    'it triggers deadline request stock notifications on the specified day of the month',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);
        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            'company_id' => 1,
        ]);
        $automatedNotificationMonthDate = AutomatedNotificationMonthDate::make([
            'automated_notification_id' => $automatedNotification->id,
            'month_date' => now()->day,
        ]);
        $automatedNotification->monthly = new Collection([$automatedNotificationMonthDate]);
        $automatedNotification->weekly = new Collection([]);
        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);
        Queue::assertPushed(AutomatedNotificationDeadlineRequestStockJob::class);
    }
);

test(
    'it triggers deadline request stock notifications on the specified day of the week',
    function (): void {
        Queue::fake();
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);
        Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'stock' => 9,
        ]);
        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'type_id' => AutomatedNotificationTypes::DEADLINE_REQUEST_STOCK->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            'company_id' => 1,
        ]);
        $automatedNotificationWeekDay = AutomatedNotificationWeekDay::make([
            'automated_notification_id' => $automatedNotification->id,
            'week_day' => now()->dayOfWeek,
        ]);
        $automatedNotification->monthly = new Collection([]);
        $automatedNotification->weekly = new Collection([$automatedNotificationWeekDay]);
        $job = new AutomatedNotificationJob();
        $job->dispatchNotificationJob($automatedNotification->type_id, $automatedNotification, 1);
        Queue::assertPushed(AutomatedNotificationDeadlineRequestStockJob::class);
    }
);
