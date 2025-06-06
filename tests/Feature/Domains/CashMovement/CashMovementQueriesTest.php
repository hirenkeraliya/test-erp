<?php

declare(strict_types=1);

use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\DataObjects\PosCashMovementData;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovementReason\Enums\StaticCashMovementReasons;
use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Cashier;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\PosMismatch;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->cashMovementReason = CashMovementReason::factory()->create([
        'reason' => 'Cash movement reason 1',
        'company_id' => $this->companyId,
    ]);

    $this->cashMovementQueries = new CashMovementQueries();
});

test('New cash movement can be added', function (): void {
    $posCashMovementData = new PosCashMovementData(
        offline_id: 'a123',
        happened_at: Carbon::now()->format('Y-m-d H:i:s'),
        cash_movement_type_id: CashMovementTypes::CASH_IN->value,
        cash_movement_reason_id: $this->cashMovementReason->id,
        other_reason: null,
        remarks: null,
        authorizer_id: 1,
        authorizer_type: AuthorizerTypes::DIRECTOR->value,
        amount: 10.10,
    );

    $counterUpdate = CounterUpdate::factory()->create();
    $this->cashMovementQueries->addNew($posCashMovementData, $counterUpdate->id);

    $this->assertDatabaseHas('cash_movements', [
        'offline_id' => $posCashMovementData->offline_id,
        'happened_at' => $posCashMovementData->happened_at,
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_type_id' => $posCashMovementData->cash_movement_type_id,
        'cash_movement_reason_id' => $posCashMovementData->cash_movement_reason_id,
        'authorizer_id' => $posCashMovementData->authorizer_id,
        'amount' => $posCashMovementData->amount,
    ]);
});

test('the getByCounterUpdateId method returns the cash movements by counter update id', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $cashMovement = CashMovement::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_reason_id' => $this->cashMovementReason->id,
    ]);

    $response = $this->cashMovementQueries->getByCounterUpdateId($counterUpdate->id);

    expect($response->first()->toArray())
        ->toHaveKey('cash_movement_type_id', $cashMovement->cash_movement_type_id)
        ->toHaveKey('amount', $cashMovement->amount);
});

test('loadRelations method loads the reason, authorizer, employee, and mismatches as expected', function (): void {
    $cashMovement = CashMovement::factory()->create([
        'cash_movement_reason_id' => $this->cashMovementReason->id,
    ]);

    PosMismatch::factory()->create([
        'module_id' => $cashMovement->id,
        'module_type' => ModelMapping::CASH_MOVEMENT->name,
    ]);

    $response = $this->cashMovementQueries->loadRelations($cashMovement);

    expect($response->toArray())
        ->toHaveKey('cash_movement_type_id')
        ->toHaveKey('other_reason')
        ->toHaveKey('other_reason')
        ->toHaveKey('authorizer_id')
        ->toHaveKey('authorizer_type')
        ->toHaveKey('amount')
        ->toHaveKeys(['mismatches', 'cash_movement_reason', 'authorizer', 'authorizer.employee']);
});

test(
    'the getPaginatedListByIdFilterByText method returns the cash movements list as expected',
    function (): void {
        $company = Company::factory()->create();
        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);
        $cashMovementReason = CashMovementReason::factory()->create([
            'company_id' => $company->id,
        ]);
        CashMovement::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'cash_movement_reason_id' => $cashMovementReason->id,
        ]);
        $filterData = [
            'per_page' => 10,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'date_range' => null,
            'location_ids' => null,
            'counter_ids' => null,
            'cash_movement_type' => null,
        ];
        $cashMovementQueries = resolve(CashMovementQueries::class);
        $response = $cashMovementQueries->getPaginatedListByIdFilterByText($filterData, $company->id);
        expect($response->first()->toArray())
            ->toHaveKeys(
                ['id', 'created_at', 'counter_update.counter', 'cash_movement_reason.reason', 'amount', 'other_reason']
            );
    }
);

test(
    'the getPaginatedCashMovementListsForStoreManager method returns the cash movements list as expected',
    function (): void {
        $company = Company::factory()->create();
        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);
        $cashMovementReason = CashMovementReason::factory()->create([
            'company_id' => $company->id,
        ]);
        CashMovement::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'cash_movement_reason_id' => $cashMovementReason->id,
        ]);
        $filterData = [
            'per_page' => 10,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'date_range' => null,
            'counter_ids' => null,
            'cash_movement_type' => null,
        ];
        $cashMovementQueries = resolve(CashMovementQueries::class);
        $response = $cashMovementQueries->getPaginatedCashMovementListsForStoreManager(
            $filterData,
            $company->id,
            $location->id
        );
        expect($response->first()->toArray())
        ->toHaveKeys(
            ['id', 'created_at', 'counter_update.counter', 'cash_movement_reason.reason', 'amount', 'other_reason']
        );
    }
);

test('the getPaginatedCashMovements method returns the cash movements list as expected', function (): void {
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $cashMovementReason = CashMovementReason::factory()->create([
        'company_id' => $company->id,
    ]);
    CashMovement::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_reason_id' => $cashMovementReason->id,
    ]);
    $filterData = [
        'per_page' => 10,
        'only_current_counter' => 1,
        'from_date' => '',
        'to_date' => '',
        'movement_type_id' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'after_updated_at' => null,
    ];
    $cashMovementQueries = resolve(CashMovementQueries::class);
    $response = $cashMovementQueries->getPaginatedCashMovements($filterData, $location->id, $counterUpdate->id);
    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'offline_id', 'created_at', 'cash_movement_reason.reason', 'amount', 'other_reason']);
});

test(
    'the getCashMovementByIdWithRelation method returns the cash movement detail of given offline id or id',
    function (): void {
        $company = Company::factory()->create();
        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $cashMovementReason = CashMovementReason::factory()->create([
            'company_id' => $company->id,
        ]);

        $cashMovement = CashMovement::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'cash_movement_reason_id' => $cashMovementReason->id,
        ]);

        $response = $this->cashMovementQueries->getCashMovementByIdWithRelation(
            $company->id,
            $cashMovement->offline_id
        );

        expect($response->toArray())
            ->toHaveKey('id', $cashMovement->id)
            ->toHaveKey('offline_id', $cashMovement->offline_id)
            ->toHaveKey('amount', $cashMovement->amount)
            ->toHaveKey('other_reason', $cashMovement->other_reason)
            ->toHaveKeys(
                [
                    'cash_movement_reason',
                    'counter_update',
                    'counter_update.counter',
                    'counter_update.counter.location',
                    'authorizer',
                    'authorizer.employee',
                ]
            );
    }
);

test('New cash movement for cashback can be added', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $happenedAt = Carbon::now()->toString();
    CashMovementReason::factory()->create([
        'id' => StaticCashMovementReasons::CASHBACK->value,
        'reason' => StaticCashMovementReasons::CASHBACK->name,
    ]);

    $this->cashMovementQueries->addNewForCashback('1234', $counterUpdate->id, 500, $happenedAt);

    $this->assertDatabaseHas('cash_movements', [
        'offline_id' => '1234',
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_type_id' => CashMovementTypes::CASH_OUT->value,
        'cash_movement_reason_id' => StaticCashMovementReasons::CASHBACK->value,
        'amount' => 500,
        'happened_at' => $happenedAt,
    ]);
});

test('the getCashMovementListsForExport method returns the cash movements list as expected', function (): void {
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $cashMovementReason = CashMovementReason::factory()->create([
        'company_id' => $company->id,
    ]);
    CashMovement::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_reason_id' => $cashMovementReason->id,
    ]);
    $filterData = [
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'date_range' => null,
        'location_ids' => null,
        'counter_ids' => null,
        'cash_movement_type' => null,
    ];
    $cashMovementQueries = resolve(CashMovementQueries::class);
    $response = $cashMovementQueries->getCashMovementListsForExport($filterData, $company->id);
    expect($response->first()->toArray())
        ->toHaveKeys(
            ['id', 'created_at', 'counter_update.counter', 'cash_movement_reason.reason', 'amount', 'other_reason']
        );
});

test(
    'the getCashMovementListsForExportInStoreManagerPanel method returns the cash movements list as expected',
    function (): void {
        $company = Company::factory()->create();
        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);
        $cashMovementReason = CashMovementReason::factory()->create([
            'company_id' => $company->id,
        ]);
        CashMovement::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'cash_movement_reason_id' => $cashMovementReason->id,
        ]);
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'date_range' => null,
            'counter_ids' => null,
            'cash_movement_type' => null,
        ];
        $cashMovementQueries = resolve(CashMovementQueries::class);
        $response = $cashMovementQueries->getCashMovementListsForExportInStoreManagerPanel(
            $filterData,
            $company->id,
            $location->id
        );
        expect($response->first()->toArray())
        ->toHaveKeys(
            ['id', 'created_at', 'counter_update.counter', 'cash_movement_reason.reason', 'amount', 'other_reason']
        );
    }
);
test('the getCashMovementForReport method returns the cash movements list as expected', function (): void {
    $date = now();
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);
    $cashMovementReason = CashMovementReason::factory()->create([
        'company_id' => $company->id,
    ]);
    $cashMovement = CashMovement::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'cash_movement_reason_id' => $cashMovementReason->id,
        'happened_at' => $date,
    ]);
    $filterData = [
        'location_ids' => [$location->id],
        'date_range' => [$date->format('Y-m-d'), $date->format('Y-m-d')],
        'counter_ids' => [$counter->id],
        'cashier_ids' => [$cashier->id],
    ];
    $cashMovementQueries = resolve(CashMovementQueries::class);
    $response = $cashMovementQueries->getCashMovementForReport($filterData, $company->id);
    expect($response->first()->toArray())
        ->toHaveKey('counter_update_id', $cashMovement->counter_update_id)
        ->toHaveKeys(['id', 'counter_update_id', 'counter_update.counter', 'counter_update.cashier', 'amount']);
});
