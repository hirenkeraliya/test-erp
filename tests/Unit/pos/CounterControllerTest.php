<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterData;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Domains\Counter\DataObjects\OpenCounterData;
use App\Domains\Counter\DataObjects\OpenCounterStatusDataForPos;
use App\Domains\Counter\DataObjects\PaginatedLastThirtyDaysCloseCountersDataForPos;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Api\Pos\CounterController;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('cashier can fetch the counters of a store', function (): void {
    $counterDetails = [
        'name' => 'abc',
        'store_id' => 1,
        'is_locked' => false,
    ];

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashierAndEmployeeData = makeCashierAndEmployeeForPosWithoutCounterUpdateId();

    $employee = $cashierAndEmployeeData['employee'];
    $cashier = $cashierAndEmployeeData['cashier'];

    $cashier->locations = $location;
    $cashier->employee = $employee;

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        $mock->shouldReceive('isAuthorizedToSelectedLocation')
            ->once()
            ->with($cashier, 1, 1)
            ->andReturn(true);
    });

    $counterQueries = $this->mock(CounterQueries::class, function ($mock) use ($counterDetails): void {
        $mock->shouldReceive('getCounterListOfSelectedLocation')
            ->once()
            ->with(1, 1)
            ->andReturn(collect([$counterDetails]));
    });

    $counterController = new CounterController();

    $response = $counterController->getStoreCounters($cashierQueries, $counterQueries, 1, $request);

    expect($response['counters'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test(
    'It can check all possibilities of checkRequestDetails method',
    function ($isLocked, $counterUpdateId, $counterId, $locationIds): void {
        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
            'is_locked' => $isLocked,
            'counter_update_id' => $counterUpdateId,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => $counterId,
        ]);

        $openCounterDetails = [
            'counter_id' => '1',
            'opening_balance' => 100,
            'opened_by_pos_at' => now()->format('Y-m-d'),
        ];

        $openCounterData = new OpenCounterData(...$openCounterDetails);

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier, $locationIds): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
            $mock->shouldReceive('getCashierLocationsId')
                ->once()
                ->with($cashier)
                ->andReturn([$locationIds]);
        });

        $this->mock(CounterQueries::class, function ($mock) use ($counter): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, 1)
                ->andReturn($counter);
        });

        $counterController = new CounterController();
        $counterController->openCounter($openCounterData, $request);
    }
)->with([
    [true, 1, null, 1], [false, 1, null, 1], [false, null, 1, 1], [false, null, 1, 0],
])->throws(HttpException::class);

test('cashier can open counter', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'is_locked' => false,
        'counter_update_id' => null,
        'location_id' => $location->id,
        'app_version' => 10,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
        'closed_at' => now()->yesterday()->format('Y-m-d H:i:s'),
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $cashierAndEmployeeData = makeCashierAndEmployeeForPosWithCounterUpdateId($counterUpdate->id);

    $employee = $cashierAndEmployeeData['employee'];
    $employee->company = $company;
    $cashier->employee = $employee;
    $cashier->counterUpdate = $counterUpdate;
    $cashier->counterUpdate->counter = $counter;
    $cashier->counterUpdate->counter->location = $location;

    $openCounterDetails = [
        'counter_id' => '1',
        'opening_balance' => 100,
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ];

    $openCounterData = new OpenCounterData(...$openCounterDetails);

    $request = new Request();

    $header = [
        'app_version' => 10,
    ];

    $request->headers->set('app-version', $header);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        $mock->shouldReceive('setCounterUpdateId')
            ->once()
            ->with($cashier, 1);
        $mock->shouldReceive('getCashierLocationsId')
            ->once()
            ->with($cashier)
            ->andReturn([1]);
    });

    $this->mock(CounterQueries::class, function ($mock) use ($counter): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, 1)
            ->andReturn($counter);
        $mock->shouldReceive('setCounterUpdateId')
            ->once()
            ->with($counter, 1);
        $mock->shouldReceive('setCounterAppVersion')
            ->once();
    });

    $this->mock(CounterUpdateQueries::class, function ($mock) use ($openCounterData, $counterUpdate): void {
        $mock->shouldReceive('getLastClosedTimeOfCounter')
            ->once()
            ->andReturn($counterUpdate);
        $mock->shouldReceive('addNew')
            ->once()
            ->with($openCounterData, 1)
            ->andReturn(1);
    });

    $counterController = new CounterController();
    $response = $counterController->openCounter($openCounterData, $request);

    expect($response['cashier']->resource)->toHaveKey('username', $cashier->username);
    expect($response['store']->resource)->toHaveKeys(['name', 'code']);
    expect($response['location']->resource)->toHaveKeys(['name', 'code']);
    expect($response['counter']->resource)->toHaveKey('opening_balance');
});

test('Cashier Cannot Open Counter with Date One Day Before the Last Closed Counter', function (): void {
    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => null,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
        'closed_by_pos_at' => Carbon::now()->subDays(2),
        'closed_at' => Carbon::now()->subDays(2),
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $openCounterDetails = [
        'counter_id' => '1',
        'opening_balance' => 100,
        'opened_by_pos_at' => Carbon::now()->subDays(2)->format('Y-m-d H:i:s'),
    ];

    $openCounterData = new OpenCounterData(...$openCounterDetails);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
        $mock->shouldNotReceive('setCounterUpdateId');
        $mock->shouldReceive('getCashierLocationsId')
            ->once()
            ->with($cashier)
            ->andReturn([1]);
    });

    $this->mock(CounterQueries::class, function ($mock) use ($counter): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, 1)
            ->andReturn($counter);
        $mock->shouldNotReceive('setCounterUpdateId');
    });

    $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
        $mock->shouldReceive('getLastClosedTimeOfCounter')
            ->once()
            ->andReturn($counterUpdate);
        $mock->shouldNotReceive('addNew');
    });

    $counterController = new CounterController();
    $counterController->openCounter($openCounterData, $request);

    $this->assertTrue(true);
})->throws(HttpException::class, 'Time travel is not possible! Opening a counter with a previous date is prohibited.');

test('It returns opened counter details', function (): void {
    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => 1,
    ]);

    $counter->counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'created_at' => Carbon::now(),
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CounterQueries::class, function ($mock) use ($counter): void {
        $mock->shouldReceive('getDetailsWithCounterUpdateByCounterUpdateId')
            ->once()
            ->with(1)
            ->andReturn($counter);
    });

    $counterController = new CounterController();
    $response = $counterController->getCurrentlyOpenCounterDetails($request);

    expect($response['counter']->resource)
        ->toHaveKey('id', $counter->id)
        ->toHaveKey('name', $counter->name)
        ->toHaveKey('is_locked', $counter->is_locked)
        ->toHaveKey('counterUpdate');
});

test('getCurrentCounterClosingDetails method returns the counter closing details', function (): void {
    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => 1,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'created_at' => Carbon::now(),
    ]);

    $employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $cashier->counterUpdate = $counterUpdate;
    $cashier->counterUpdate->counter = $counter;
    $cashier->employee = $employee;

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('loadDetailsForCounterCloseApi')
            ->once()
            ->with($cashier)
            ->andReturn($cashier);
    });

    $this->mock(CloseCounterService::class, function ($mock): void {
        $mock->shouldReceive('prepareAndReturnCounterClosingDetails')
            ->once()
            ->andReturn([]);
    });

    $counterController = new CounterController();

    $response = $counterController->getCurrentCounterClosingDetails($request);

    expect($response['counter_closing_details'])
        ->toHaveKey('cashier_name')
        ->toHaveKey('counter_name')
        ->toHaveKey('opening_date_time');
});

test('cashier can close counter', function (): void {
    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => null,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->counterUpdate = $counterUpdate;
    $cashier->counterUpdate->counter = $counter;

    $closeCounterDetails = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
        'closed_by_pos_at' => null,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $closeCounterDetails['denominations'] = CloseCounterDenominationData::collection([$denomination]);

    $closeCounterData = new CloseCounterData(...$closeCounterDetails);

    $request = new Request($closeCounterDetails);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $counterClosingDetails = [
        'opening_balance' => 0,
        'closing_balance' => 100,
    ];

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('loadDetailsForCounterCloseApi')
            ->once()
            ->with($cashier)
            ->andReturn($cashier);
        $mock->shouldReceive('unsetCounterUpdateId')
            ->once()
            ->with($cashier);
    });

    $this->mock(CounterQueries::class, function ($mock) use ($counter): void {
        $mock->shouldReceive('unsetCounterUpdateId')
            ->once()
            ->with($counter);
    });

    $this->mock(CloseCounterService::class, function ($mock) use ($counterClosingDetails): void {
        $mock->shouldNotReceive('checkCloseCounterDetails')
            ->once();
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->shouldReceive('prepareAndReturnCounterClosingDetails')
            ->once()
            ->andReturn($counterClosingDetails);
        $mock->shouldReceive('closeCounter')
            ->once();
    });

    $counterController = new CounterController();
    $counterController->closeCounter($request, $closeCounterData);
});

test('Cashier Cannot Close Counter Before the Date it was Opened', function (): void {
    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => null,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $currentTime = Carbon::now()->format('Y-m-d H:i:s');

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
        'opened_by_pos_at' => $currentTime,
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->counterUpdate = $counterUpdate;
    $cashier->counterUpdate->counter = $counter;

    $closeCounterDetails = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
        'closed_by_pos_at' => $currentTime,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $closeCounterDetails['denominations'] = CloseCounterDenominationData::collection([$denomination]);

    $closeCounterData = new CloseCounterData(...$closeCounterDetails);

    $request = new Request($closeCounterDetails);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('loadDetailsForCounterCloseApi')
            ->once()
            ->with($cashier)
            ->andReturn($cashier);
        $mock->shouldNotReceive('unsetCounterUpdateId');
    });

    $this->mock(CounterQueries::class, function ($mock): void {
        $mock->shouldNotReceive('unsetCounterUpdateId');
    });

    $counterController = new CounterController();
    $counterController->closeCounter($request, $closeCounterData);
})->throws(
    HttpException::class,
    'It is not allowed to close the counter with a date that is earlier than the date it was opened.'
);

test(
    'It calls the getPaginatedLastThirtyDaysClosedCountersForPos method of CounterUpdateQueries class and returns last thirty day closed counters list',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $paginatedLastThirtyDaysCloseCountersData = [
            'per_page' => 10,
            'page' => 1,
            'sort_by' => '',
            'sort_direction' => '',
            'search_text' => '',
            'after_updated_at' => null,
        ];

        $paginatedLastThirtyDaysCloseCountersDataForPos = new PaginatedLastThirtyDaysCloseCountersDataForPos(
            ...$paginatedLastThirtyDaysCloseCountersData
        );

        $request = new Request([
            'employee_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->with($cashier->counter_update_id)
                ->andReturn($location);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedLastThirtyDaysClosedCountersForPos')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $counterController = new CounterController();
        $counterController->getPaginatedLastThirtyDaysClosedCounters(
            $request,
            $paginatedLastThirtyDaysCloseCountersDataForPos
        );
    }
);

test(
    'closedCounterSales method throws an exceptions when the company does not match with current opened counter',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $cashier->employee = $employee;

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
            'is_locked' => false,
            'counter_update_id' => 1,
        ]);

        $counter->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 2,
            'created_at' => Carbon::now(),
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(2);
        });

        $counterController = new CounterController();
        $counterController->closedCounterSales($request, $counter->counterUpdate->id);
    }
)->throws(HttpException::class, 'The Counter Update Id does not match with this company');

test('closedCounterSales method throws an exceptions when counter is not closed yet.', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $cashier->employee = $employee;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => 1,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'closed_at' => null,
        'created_at' => Carbon::now(),
    ]);

    $counter->counterUpdate = $counterUpdate;

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CounterUpdateQueries::class, function ($mock) use ($employee, $counterUpdate): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn($employee->company_id);
        $mock->shouldReceive('getByIdWithClosedAtColumn')
            ->once()
            ->andReturn($counterUpdate);
    });

    $counterController = new CounterController();
    $counterController->closedCounterSales($request, $counter->counterUpdate->id);
})->throws(HttpException::class, 'Only the Close Counter update ID is allowed.');

test('closedCounterSales method returns the closed counter sales details', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $cashier->employee = $employee;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
        'is_locked' => false,
        'counter_update_id' => 1,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'created_at' => Carbon::now(),
    ]);

    $counter->counterUpdate = $counterUpdate;

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CounterUpdateQueries::class, function ($mock) use ($employee, $counterUpdate): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn($employee->company_id);
        $mock->shouldReceive('getByIdWithClosedAtColumn')
            ->once()
            ->andReturn($counterUpdate);
    });

    $this->mock(SaleQueries::class, function ($mock) use ($counterUpdate): void {
        $mock->shouldReceive('getSalesByCounterUpdateId')
            ->once()
            ->with($counterUpdate->id, null)
            ->andReturn(new Collection([]));
    });

    $counterController = new CounterController();
    $response = $counterController->closedCounterSales($request, $counter->counterUpdate->id);
    $this->assertEquals(new Collection([]), $response['closed_counter_sales']->resource);
});

test(
    'calls the getByIdOrByCounterIdAndOpenedByPosAt method of CounterUpdateQueries when counter data not in over records',
    function (): void {
        $cashier = Cashier::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $openCounterStatusData = [
            'counter_id' => 1,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
            'counter_update_id' => 1,
        ];

        $openCounterStatusDataForPos = new OpenCounterStatusDataForPos(...$openCounterStatusData);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdOrByCounterIdAndOpenedByPosAt')
                ->once()
                ->andReturn(null);
        });

        $counterController = new CounterController();
        $response = $counterController->getCounterOpenStatus($openCounterStatusDataForPos);
        $this->assertFalse($response['isCounterOpened']);
        $this->assertFalse($response['isCounterClosed']);
    }
);

test(
    'calls the getByIdOrByCounterIdAndOpenedByPosAt method of CounterUpdateQueries when counter open but not closed',
    function (): void {
        Cashier::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
            'closed_by_pos_at' => null,
        ]);

        $openCounterStatusData = [
            'counter_id' => 1,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
            'counter_update_id' => 1,
        ];

        $openCounterStatusDataForPos = new OpenCounterStatusDataForPos(...$openCounterStatusData);

        $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
            $mock->shouldReceive('getByIdOrByCounterIdAndOpenedByPosAt')
                ->once()
                ->andReturn($counterUpdate);
        });

        $counterController = new CounterController();
        $response = $counterController->getCounterOpenStatus($openCounterStatusDataForPos);
        $this->assertTrue($response['isCounterOpened']);
        $this->assertFalse($response['isCounterClosed']);
    }
);

test(
    'calls the getByIdOrByCounterIdAndOpenedByPosAt method of CounterUpdateQueries when counter open and also closed',
    function (): void {
        Cashier::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
            'closed_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $openCounterStatusData = [
            'counter_id' => 1,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
            'counter_update_id' => 1,
        ];

        $openCounterStatusDataForPos = new OpenCounterStatusDataForPos(...$openCounterStatusData);

        $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
            $mock->shouldReceive('getByIdOrByCounterIdAndOpenedByPosAt')
                ->once()
                ->andReturn($counterUpdate);
        });

        $counterController = new CounterController();
        $response = $counterController->getCounterOpenStatus($openCounterStatusDataForPos);
        $this->assertTrue($response['isCounterOpened']);
        $this->assertTrue($response['isCounterClosed']);
    }
);
