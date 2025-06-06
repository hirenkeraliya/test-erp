<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\DataObjects\PaginatedCashMovementsDataForPos;
use App\Domains\CashMovement\DataObjects\PosCashMovementData;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Director\DirectorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Http\Controllers\Api\Pos\CashMovementController;
use App\Models\Cashier;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function (): void {
    $this->cashMovement = CashMovement::factory()->make([
        'offline_id' => 'a123',
        'happened_at' => '2022-01-04 04:20:50',
        'counter_update_id' => 1,
        'cash_movement_reason_id' => 1,
        'authorizer_id' => 1,
        'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
    ])->toArray();

    unset($this->cashMovement['counter_update_id']);

    $this->cashMovementController = new CashMovementController();
});

test('It returns the cash movement authorizer types list', function (): void {
    $response = $this->cashMovementController->getAuthorizerTypes();

    expect($response['cash_movement_authorizer_types'][0])
        ->toHaveKey('id', AuthorizerTypes::DIRECTOR->value)
        ->toHaveKey('name', AuthorizerTypes::DIRECTOR->value)
        ->toHaveKey('key', 'DIRECTOR');
});

test('It calls the addNew method of the Cash Movement Queries class and returns proper response', function (): void {
    $cashMovement = CashMovement::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'cash_movement_reason_id' => 1,
        'authorizer_id' => 1,
        'remarks' => 'A1234',
        'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
    ]);

    $preparedArray = [
        'offline_id' => 'a123',
        'happened_at' => '2022-01-04 04:20:50',
        'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
        'cash_movement_reason_id' => $cashMovement->cash_movement_reason_id,
        'other_reason' => $cashMovement->other_reason,
        'remarks' => $cashMovement->remarks,
        'authorizer_id' => 1,
        'authorizer_type' => ModelMapping::STORE_MANAGER->name,
        'store_manager_authorization_code' => '1234',
        'amount' => $cashMovement->amount,
    ];

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn(Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]));
    });

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $cashMovementReason = CashMovementReason::factory()->make([
        'company_id' => 1,
        'type_id' => 1,
    ]);

    $cashMovement->cash_movement_reason_id = 2;
    $cashMovement->mismatches = collect([]);

    $request = $this->mock(Request::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('route');
    });

    $this->mock(CashMovementQueries::class, function ($mock) use ($cashMovement): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($cashMovement);
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($cashMovement);
    });

    $this->mock(CashMovementReasonQueries::class, function ($mock) use ($cashMovementReason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($cashMovementReason);
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $cashMovementController = new CashMovementController();

    $response = $cashMovementController->store(new PosCashMovementData(...$preparedArray), $request);

    expect($response['cash_movement'])->toBeObject();
});

test('checkRequestDetails method set mismatches when Cash movement reason type id not matched', function (): void {
    $cashMovementReason = CashMovementReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => CashMovementTypes::CASH_OUT->value,
    ]);

    $this->mock(CashMovementReasonQueries::class, function ($mock) use ($cashMovementReason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($cashMovementReason);
    });
    $mismatches = collect([]);

    $this->cashMovementController->checkRequestDetails(
        new PosCashMovementData(...$this->cashMovement),
        1,
        1,
        $mismatches
    );

    $this->assertTrue(
        $mismatches->contains('The selected cash movement type does not have an available cash movement reason.')
    );
});

test(
    'checkRequestDetails method set mismatches when Store Manager does not belong to open counter Location',
    function (): void {
        $this->cashMovement['cash_movement_reason_id'] = null;
        $this->cashMovement['authorizer_type'] = AuthorizerTypes::STORE_MANAGER->value;
        $this->cashMovement['authorizer_type'] = AuthorizerTypes::STORE_MANAGER->value;

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndStatus')
                ->once()
                ->andReturn(false);
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
        });

        $mismatches = collect([]);

        $this->cashMovementController->checkRequestDetails(
            new PosCashMovementData(...$this->cashMovement),
            1,
            1,
            $mismatches
        );

        $this->assertTrue(
            $mismatches->contains('The selected store manager does not belong to the current counter`s location.')
        );
    }
);

test(
    'checkRequestDetails method set mismatches when Director does not belong to open counter store',
    function (): void {
        $this->cashMovement['cash_movement_reason_id'] = null;
        $this->cashMovement['authorizer_type'] = AuthorizerTypes::DIRECTOR->value;

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdLocationIdAndStatus')
                ->once()
                ->andReturn(false);
        });

        $mismatches = collect([]);

        $this->cashMovementController->checkRequestDetails(
            new PosCashMovementData(...$this->cashMovement),
            1,
            1,
            $mismatches
        );
        $this->assertTrue(
            $mismatches->contains('The selected director does not belong to the current counter`s location.')
        );
    }
);

test('checkRequestDetails method returns the response as expected', function (): void {
    $this->cashMovement['cash_movement_reason_id'] = null;
    $this->cashMovement['authorizer_type'] = AuthorizerTypes::DIRECTOR->value;

    $this->mock(DirectorQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdLocationIdAndStatus')
            ->once()
            ->andReturn(true);
    });

    $response = $this->cashMovementController->checkRequestDetails(
        new PosCashMovementData(...$this->cashMovement),
        1,
        1,
        collect([])
    );
    $this->assertNull($response);
});

test('It calls the getPaginatedCashMovements method and returns the cash movements records', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $paginatedCashMovementsData = [
        'page' => 1,
        'from_date' => '',
        'to_date' => '',
        'per_page' => 10,
        'only_current_counter' => true,
        'movement_type_id' => CashMovementTypes::CASH_IN->value,
        'sort_by' => '',
        'search_text' => '',
        'sort_direction' => '',
        'after_updated_at' => null,
    ];
    $paginatedCashMovementsDataForPos = new PaginatedCashMovementsDataForPos(...$paginatedCashMovementsData);

    $request = new Request();

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(CashMovementQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedCashMovements')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $this->cashMovementController->getPaginatedCashMovements($request, $paginatedCashMovementsDataForPos);
});

test(
    'it calls the getCashMovementDetails method and returns the cash movement detail of given offline id',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request([
            'employee_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once();
        });

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
        });

        $this->mock(CashMovementQueries::class, function ($mock): void {
            $mock->shouldReceive('getCashMovementByIdWithRelation')
            ->once()
            ->andReturn(new CashMovement([]));
        });

        $cashMovementController = new CashMovementController();
        $cashMovementController->getCashMovementDetails($request, '12345');
    }
);
