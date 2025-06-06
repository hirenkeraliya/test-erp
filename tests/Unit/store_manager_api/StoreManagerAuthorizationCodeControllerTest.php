<?php

declare(strict_types=1);

use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Http\Controllers\Api\StoreManager\StoreManagerAuthorizationCodeController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    $this->storeManager->employee = $this->employeeA;

    $this->storeManagerAuthorizationCodes = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => $this->storeManager->getKey(),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE->value,
        'expiry_date' => Carbon::now()->subHour(),
    ]);
});

test(
    'Store Manager Authorization Code Generation and Storage Functionality',
    function (): void {
        $this->storeManagerAuthorizationCodes->status = StoreManagerAuthorizationCodeStatuses::EXPIRED->value;

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('loadEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();

            $mock->shouldReceive('getWithStoreManager')
                ->once()
                ->with($this->storeManager->getKey())
                ->andReturn($this->storeManagerAuthorizationCodes);
        });

        $request = new Request();

        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $code = 'SM' . $this->employeeA->staff_id . Str::random(6) . Carbon::now()->format('Hi');
        $storeManagerAuthorizationCodeController = $this->createPartialMock(
            StoreManagerAuthorizationCodeController::class,
            ['generateNewCode']
        );

        $storeManagerAuthorizationCodeController->expects($this->once())
            ->method('generateNewCode')
            ->will($this->returnValue($code));

        $response = $storeManagerAuthorizationCodeController->getAuthorizationCode($request);

        expect($response['code'])->toBe($code);
    }
);

test(
    'Store Manager Authorization Code Generation and Storage Functionality with Cancelled Code and No Expiry Date',
    function (): void {
        $this->storeManagerAuthorizationCodes->status = StoreManagerAuthorizationCodeStatuses::CANCELLED->value;
        $this->storeManagerAuthorizationCodes->expiry_date = null;

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('loadEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();

            $mock->shouldReceive('getWithStoreManager')
                ->once()
                ->with($this->storeManager->getKey())
                ->andReturn($this->storeManagerAuthorizationCodes);
        });

        $request = new Request();

        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $code = 'SM' . $this->employeeA->staff_id . Str::random(6) . Carbon::now()->format('Hi');
        $storeManagerAuthorizationCodeController = $this->createPartialMock(
            StoreManagerAuthorizationCodeController::class,
            ['generateNewCode']
        );

        $storeManagerAuthorizationCodeController->expects($this->once())
            ->method('generateNewCode')
            ->will($this->returnValue($code));

        $response = $storeManagerAuthorizationCodeController->getAuthorizationCode($request);

        expect($response['code'])->toBe($code);
    }
);

test(
    'Store Manager Authorization Code Generation and Storage Functionality with Active Code and No Expiry Date',
    function (): void {
        $this->storeManagerAuthorizationCodes->status = StoreManagerAuthorizationCodeStatuses::ACTIVE->value;
        $this->storeManagerAuthorizationCodes->expiry_date = null;

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('loadEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();

            $mock->shouldReceive('getWithStoreManager')
                ->once()
                ->with($this->storeManager->getKey())
                ->andReturn($this->storeManagerAuthorizationCodes);

            $mock->shouldReceive('cancelTheAuthorizationCode')
                ->once()
                ->with($this->storeManagerAuthorizationCodes->getKey());
        });

        $request = new Request();

        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $code = 'SM' . $this->employeeA->staff_id . Str::random(6) . Carbon::now()->format('Hi');
        $storeManagerAuthorizationCodeController = $this->createPartialMock(
            StoreManagerAuthorizationCodeController::class,
            ['generateNewCode']
        );

        $storeManagerAuthorizationCodeController->expects($this->once())
            ->method('generateNewCode')
            ->will($this->returnValue($code));

        $response = $storeManagerAuthorizationCodeController->getAuthorizationCode($request);

        expect($response['code'])->toBe($code);
    }
);

test('Generating New Authorization Code for Store Manager', function (): void {
    $storeManagerAuthorizationCodeController = new StoreManagerAuthorizationCodeController();
    $response = $storeManagerAuthorizationCodeController->generateNewCode($this->employeeA->staff_id);

    expect($response)->toBeString();
});
