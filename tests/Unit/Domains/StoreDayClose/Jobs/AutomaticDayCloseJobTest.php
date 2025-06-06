<?php

declare(strict_types=1);

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreDayClose\Jobs\AutomaticDayCloseJob;
use App\Domains\StoreDayClose\Mail\SendFailedAutomaticDayCloseMail;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

test(
    'AutomaticDayCloseJob job calls respective methods and automatic day close as expected while all counters closed',
    function (): void {
        Mail::fake();

        $currentTime = Carbon::now()->format('H:i:s');

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'automatic_day_close_time' => $currentTime,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getWithAutomaticDayCloseTimeAndName')
                ->once()
                ->andReturn(collect([$location]));
        });

        $this->mock(StoreDayCloseQueries::class, function ($mock): void {
            $mock->shouldReceive('getLastDayClose')
                ->once();
        });

        $this->mock(StoreDayCloseService::class, function ($mock): void {
            $mock->shouldReceive('addStoreDayClose')
                ->once();
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getOpenCountersCountFilterByStoreAndDates')
                ->once()
                ->andReturn(0);
        });

        AutomaticDayCloseJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Mail::assertNotSent(SendFailedAutomaticDayCloseMail::class);
    }
);

test(
    'AutomaticDayCloseJob job calls respective methods and automatic day close as expected while one of the counter is open',
    function (): void {
        Mail::fake();

        $currentTime = Carbon::now()->format('H:i:s');

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'automatic_day_close_time' => $currentTime,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $storeManager = StoreManager::factory()->make([
            'employee_id' => 1,
        ]);

        $storeManager->employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'designation_id' => 1,
        ]);

        $storeManager->employee->company = $company;

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getWithAutomaticDayCloseTimeAndName')
                ->once()
                ->andReturn(collect([$location]));
        });

        $this->mock(StoreDayCloseQueries::class, function ($mock): void {
            $mock->shouldReceive('getLastDayClose')
                ->once();
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getOpenCountersCountFilterByStoreAndDates')
                ->once()
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('getByStoreIdWithEmployee')
                ->once()
                ->andReturn(collect([$storeManager]));
        });

        AutomaticDayCloseJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Mail::assertSent(
            SendFailedAutomaticDayCloseMail::class,
            fn ($mail): bool => $mail->hasTo($storeManager->employee->email)
            && $mail->location->name === $location->name
        );
    }
);
