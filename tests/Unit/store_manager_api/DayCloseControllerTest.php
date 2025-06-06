<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Domains\Counter\DataObjects\StoreManagerApiDayCloseCounterData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Enums\CounterStatus;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\Denomination\DenominationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\DayCloseController;
use App\Models\Cashier;
use App\Models\CloseCounterDenomination;
use App\Models\CloseCounterPayment;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Denomination;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\StoreDayClose;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $this->counter->id,
        'cashier_id' => 1,
        'closing_balance' => null,
        'closed_at' => null,
    ]);

    $this->storeDayClose = StoreDayClose::factory()->make([
        'id' => 1,
        'location_id' => $this->location->id,
        'closed_by_store_manager_id' => $this->storeManager->id,
    ]);
});

test('calls the getCountersForDayClose method and returns counter record based on type', function (): void {
    $request = new Request();

    $filterData = [
        'store_id' => $this->location->id,
        'location_id' => $this->location->id,
        'status' => CounterStatus::ALL->value,
    ];

    $storeManagerApiDayCloseCounterData = new StoreManagerApiDayCloseCounterData(...$filterData);

    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
        ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(StoreDayCloseQueries::class, function ($mock): void {
        $mock->shouldReceive('getLastDayClose')
            ->once()
            ->with($this->location->id)
            ->andReturn($this->storeDayClose);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getByDayCloseAndStoreByType')
            ->andReturn(collect($this->counterUpdate))
            ->once();
    });

    $dayCloseController = new DayCloseController();
    $response = $dayCloseController->getCountersForDayClose($request, $storeManagerApiDayCloseCounterData);

    expect($response['data']->collection)->toBeInstanceOf(Collection::class);
});

test('getCountersForDayClose method throws an Exception when specify different status value', function (): void {
    $request = new Request();

    $filterData = [
        'store_id' => $this->location->id,
        'status' => 5,
        'location_id' => $this->location->id,
    ];

    $storeManagerApiDayCloseCounterData = new StoreManagerApiDayCloseCounterData(...$filterData);

    $request->validate(StoreManagerApiDayCloseCounterData::rules());

    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $dayCloseController = new DayCloseController();
    $dayCloseController->getCountersForDayClose($request, $storeManagerApiDayCloseCounterData);
})->throws(ValidationException::class);

test(
    'getCountersForDayClose method throws an Exception when the store manager specify a different location',
    function (): void {
        $storeManager = StoreManager::factory()->make([
            'id' => 2,
            'employee_id' => 2,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $storeManager);

        $filterData = [
            'store_id' => $this->location->id,
            'status' => CounterStatus::ALL->value,
            'location_id' => $this->location->id,
        ];

        $storeManagerApiDayCloseCounterData = new StoreManagerApiDayCloseCounterData(...$filterData);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('existsByIdAndStoreId')
        ->once()
            ->with((int) $storeManager->id, (int) $this->location->id)
            ->andReturn(false);
        });

        $dayCloseController = new DayCloseController();
        $dayCloseController->getCountersForDayClose($request, $storeManagerApiDayCloseCounterData);
    }
)->throws(HttpException::class);

test('closeCounter method calls and returns proper response', function (): void {
    $this->cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->counterUpdate->counter = $this->counter;

    $preparedArray = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);
    $closeCounterData = new CloseCounterDataForStoreManager(...$preparedArray);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('findByIdAndFilterByStore')
            ->once()
            ->andReturn($this->counterUpdate);
    });

    $this->mock(CloseCounterService::class, function ($mock): void {
        $mock->shouldReceive('prepareAndReturnCounterClosingDetails')
        ->once();
        $mock->shouldReceive('checkRequestDetails')
        ->once();
        $mock->shouldReceive('closeCounter')
        ->once();
    });

    $this->mock(CounterQueries::class, function ($mock): void {
        $mock->shouldReceive('unsetCounterUpdateId')
            ->once();
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('findByCounterUpdateId')
        ->once()
        ->with(1)
            ->andReturn($this->cashier);

        $mock->shouldReceive('unsetCounterUpdateId')
        ->once();
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $dayCloseController = new DayCloseController();
    $dayCloseController->closeCounter($closeCounterData, $this->location->id, $this->counterUpdate->id, $request);
});

test('closeCounter method throws an Exception when the counter is not opened', function (): void {
    $preparedArray = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $this->counterUpdate->counter = $this->counter;

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);
    $closeCounterData = new CloseCounterDataForStoreManager(...$preparedArray);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('findByIdAndFilterByStore')
            ->once()
            ->andReturn($this->counterUpdate);
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('findByCounterUpdateId')
        ->once();
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $dayCloseController = new DayCloseController();
    $dayCloseController->closeCounter($closeCounterData, $this->location->id, $this->counterUpdate->id, $request);
})->throws(HttpException::class);

test('closeCounter method throws an Exception when the counter is not found', function (): void {
    $this->cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $preparedArray = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $this->counterUpdate->counter = $this->counter;

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);
    $closeCounterData = new CloseCounterDataForStoreManager(...$preparedArray);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('findByIdAndFilterByStore')
        ->once();
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $dayCloseController = new DayCloseController();
    $dayCloseController->closeCounter($closeCounterData, $this->location->id, $this->counterUpdate->id, $request);
})->throws(HttpException::class);

test(
    'calls the counterDetails method and returns proper response when counter is open',
    function (): void {
        $this->company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->once()->andReturn([
            'location_id' => 1,
            'id' => 1,
        ]);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfStore')
                ->once()
                ->with((int) $this->location->id)
                ->andReturn($this->company->id);
        });

        $denomination = Denomination::factory()->make([
            'company_id' => $this->company->id,
            'denomination' => 20,
        ]);

        $this->mock(DenominationQueries::class, function ($mock) use ($denomination): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->with($this->company->id)
                ->andReturn(collect([$denomination]));
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('findByIdAndFilterByStore')
                ->andReturn($this->counterUpdate)
                ->once();
        });

        $this->mock(CloseCounterService::class, function ($mock): void {
            $mock->shouldReceive('prepareAndReturnCounterClosingDetails')
                ->once();
        });

        $dayCloseController = new DayCloseController();

        $response = $dayCloseController->counterDetails($request);

        expect($response['data'])
            ->toHaveKeys(['closed_at', 'denominations']);
    }
);

test(
    'calls the counterDetails method and returns exception when counter not found',
    function (): void {
        $this->company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->once()->andReturn([
            'location_id' => 1,
            'id' => 1,
        ]);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('findByIdAndFilterByStore')
                ->once();
        });

        $dayCloseController = new DayCloseController();

        $response = $dayCloseController->counterDetails($request);

        expect($response['data'])
            ->toHaveKeys(['closed_at', 'denominations']);
    }
)->throws(HttpException::class);

test(
    'calls the counterDetails method and returns proper response when counter is close',
    function (): void {
        $this->counterUpdate->closed_at = Carbon::now();

        $this->company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $closeCounterDenomination = CloseCounterDenomination::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->counterUpdate->denominations = collect([$closeCounterDenomination]);

        $counterUpdatePayment = CloseCounterPayment::factory()->make([
            'counter_update_id' => 1,
            'payment_type_id' => 1,
        ]);

        $counterUpdatePayment->paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->counterUpdate->payments = collect([$counterUpdatePayment]);

        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->once()->andReturn([
            'location_id' => 1,
            'id' => 1,
        ]);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfStore')
            ->once()
                ->with((int) $this->location->id)
                ->andReturn($this->company->id);
        });

        $denomination = Denomination::factory()->make([
            'company_id' => $this->company->id,
            'denomination' => 20,
        ]);

        $this->mock(DenominationQueries::class, function ($mock) use ($denomination): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
                ->with($this->company->id)
                ->andReturn(collect([$denomination]));
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('findByIdAndFilterByStore')
            ->once()
            ->andReturn($this->counterUpdate);

            $mock->shouldReceive('findByIdWithRelationsFilterByStore')
            ->once()
            ->andReturn($this->counterUpdate);
        });

        $dayCloseController = new DayCloseController();

        $response = $dayCloseController->counterDetails($request);

        expect($response['data'])
            ->toHaveKeys(['closed_at', 'denominations', 'payments']);
    }
);

test('calls the dayClose method and returns proper response when all counter is closed', function (): void {
    $request = new Request([
        'store_id' => $this->location->id,
    ]);

    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
        ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(StoreDayCloseQueries::class, function ($mock): void {
        $mock->shouldReceive('getLastDayClose')
        ->once();
        $mock->shouldReceive('loadRelations')
        ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('findByIdWithReceiptFooterDisclaimerAndCreatedAt')
            ->once()
            ->andReturn($this->location);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getOpenCountersCountFilterByStoreAndDates')
            ->once();
    });

    $this->mock(StoreDayCloseService::class, function ($mock): void {
        $mock->shouldReceive('addStoreDayClose')
            ->once();
    });

    $dayCloseController = new DayCloseController();

    $response = $dayCloseController->dayClose($request);
    expect($response)
        ->toHaveKeys(['store_day_close', 'store_receipt_footer', 'store_disclaimer']);
});

test('dayClose method throws an exception when counters are still open while day close', function (): void {
    $request = new Request([
        'store_id' => $this->location->id,
    ]);

    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(StoreDayCloseQueries::class, function ($mock): void {
        $mock->shouldReceive('getLastDayClose')
        ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('findByIdWithReceiptFooterDisclaimerAndCreatedAt')
            ->once()
            ->andReturn($this->location);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getOpenCountersCountFilterByStoreAndDates')
            ->once()
            ->andReturn(1);
    });

    $dayCloseController = new DayCloseController();

    $dayCloseController->dayClose($request);
})->throws(HttpException::class);

test('dayClose method throws an exception when location not found', function (): void {
    $request = new Request([
        'store_id' => $this->location->id,
    ]);

    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
        ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(StoreDayCloseQueries::class, function ($mock): void {
        $mock->shouldReceive('getLastDayClose')
        ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('findByIdWithReceiptFooterDisclaimerAndCreatedAt')
        ->once();
    });

    $dayCloseController = new DayCloseController();

    $dayCloseController->dayClose($request);
})->throws(HttpException::class);

test('dayClose method throws an exception when the store manager specify a different location', function (): void {
    $request = new Request([
        'store_id' => $this->location->id,
    ]);

    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
        ->once();
    });

    $dayCloseController = new DayCloseController();
    $dayCloseController->dayClose($request);
})->throws(HttpException::class);
