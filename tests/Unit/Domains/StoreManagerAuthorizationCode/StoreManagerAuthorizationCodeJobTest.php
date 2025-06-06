<?php

declare(strict_types=1);

use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\Jobs\StoreManagerAuthorizationCodeJob;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employeeA = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
        'first_name' => 'ABCD',
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employeeA->id,
    ]);

    $this->storeManagerAuthorizationCodes = StoreManagerAuthorizationCode::factory(2)->make([
        'id' => 1,
        'store_manager_id' => $this->storeManager->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
        'expiry_date' => Carbon::now()->subHour(),
    ]);
});

test('it updates the expired status when the current time is greater than now', function (): void {
    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
        $mock->shouldReceive('getOnlyActiveStoreManagerAuthorizationCodes')
            ->once()
            ->andReturn($this->storeManagerAuthorizationCodes);

        $mock->shouldReceive('markStatusAsExpired')
            ->twice();
    });

    StoreManagerAuthorizationCodeJob::dispatch()->onQueue(config('horizon.default_queue_name'));
});

test('does not update the expired status when store manager authorization code are not there', function (): void {
    $storeManagerAuthorizationCodeTests = StoreManagerAuthorizationCode::factory(2)->make([
        'id' => 1,
        'store_manager_id' => $this->storeManager->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
        'expiry_date' => Carbon::now()->addHour(),
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCodeTests
    ): void {
        $mock->shouldReceive('getOnlyActiveStoreManagerAuthorizationCodes')
            ->once()
            ->andReturn($storeManagerAuthorizationCodeTests);

        $mock->shouldNotReceive('markStatusAsExpired');
    });

    StoreManagerAuthorizationCodeJob::dispatch()->onQueue(config('horizon.default_queue_name'));
});
