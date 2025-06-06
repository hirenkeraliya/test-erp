<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\DataObjects\CloseCounterData;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Domains\Counter\DataObjects\OpenCounterData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Enums\CounterStatus;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Cashier;
use App\Models\CloseCounterDenomination;
use App\Models\CloseCounterPayment;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\CounterUpdateDeclarationAttemptPayment;
use App\Models\CounterUpdateEvent;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreDayClose;
use Carbon\Carbon;
use Illuminate\Support\Collection;

test('Save counter update details while open counter', function (): void {
    $counter = Counter::factory()->create();
    $cashier = Cashier::factory()->create();

    $preparedArray = [
        'counter_id' => (string) $counter->id,
        'opening_balance' => 100,
        'opened_by_pos_at' => null,
    ];

    $openCounterData = new OpenCounterData(...$preparedArray);

    $counterUpdateQueries = new CounterUpdateQueries();

    $response = $counterUpdateQueries->addNew($openCounterData, $cashier->id);

    expect($response)->toBeInt();

    $this->assertDatabaseHas('counter_updates', [
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => $preparedArray['opening_balance'],
    ]);
});

test('closeCounterUpdate method updates the counter update details', function (): void {
    $counter = Counter::factory()->create();
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $preparedArray = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
        'closed_by_pos_at' => null,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);

    $counterClosingDetails = prepareCloseCounterRecords(
        (float) $counterUpdate->opening_balance,
        (float) $preparedArray['closing_balance']
    );

    $closeCounterData = new CloseCounterData(...$preparedArray);

    $counterUpdateQueries = new CounterUpdateQueries();

    $counterUpdateQueries->closeCounterUpdate(
        $counterUpdate,
        $closeCounterData,
        $counterClosingDetails,
        ModelMapping::CASHIER->name,
        $cashier->id
    );

    $this->assertDatabaseHas('counter_updates', [
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'closing_balance' => $counterUpdate->closing_balance,
        'mismatch_amount' => $counterUpdate->mismatch_amount,
        'total_sales' => $counterClosingDetails['total_sales'],
        'total_sales_amount' => $counterClosingDetails['total_sales_amount'],
        'closed_by_type' => ModelMapping::CASHIER->name,
        'closed_by_id' => $cashier->id,
    ]);
});

test('closeCounterUpdate method sets the mismatch columns when the closing amount is not match.', function (): void {
    $counter = Counter::factory()->create();
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $preparedArray = [
        'closing_balance' => 200,
        'mismatch_amount_reason' => 'reason',
        'closed_by_pos_at' => 'null',
    ];

    $denomination = [
        'denomination' => 200,
        'quantity' => 1,
    ];

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);

    $mismatchAmount = $preparedArray['closing_balance'] - $counterUpdate->opening_balance;

    $counterClosingDetails = prepareCloseCounterRecords(
        (float) $counterUpdate->opening_balance,
        (float) $mismatchAmount
    );

    $closeCounterData = new CloseCounterData(...$preparedArray);

    $counterUpdateQueries = new CounterUpdateQueries();

    $counterUpdateQueries->closeCounterUpdate(
        $counterUpdate,
        $closeCounterData,
        $counterClosingDetails,
        ModelMapping::CASHIER->name,
        $cashier->id
    );

    $this->assertDatabaseHas('counter_updates', [
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'closing_balance' => $counterUpdate->closing_balance,
        'mismatch_amount' => $mismatchAmount,
        'amount_mismatch_reason' => $preparedArray['mismatch_amount_reason'],
        'closed_by_type' => ModelMapping::CASHIER->name,
        'closed_by_id' => $cashier->id,
    ]);
});

test('getCompanyIdByCounterUpdateId method returns the company id', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();

    $response = $counterUpdateQueries->getCompanyIdByCounterUpdateId($counterUpdate->id);
    $this->assertEquals($location->company_id, $response);
});

test('getByIdFilterByStore method returns the counter update details', function (): void {
    $location = Location::factory()->create([
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);
    $location = Location::factory()->create([
        'id' => 1,
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getByIdFilterByStore($location->id, $counterUpdate->id);
    expect($response->toArray())
        ->toHaveKey('id', $counterUpdate->id)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('counter_id', $counterUpdate->counter_id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance);
});

test(
    'getByDayCloseAndStore method return the counter updates after location day close closed_at',
    function (): void {
        [$location, $locationDayClose, $counterUpdate] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getByDayCloseAndStore(
            $location->id,
            $location->company_id,
            $locationDayClose
        );
        expect($response->first()->toArray())
            ->toHaveKey('id', $counterUpdate->id)
            ->toHaveKeys([
                'opening_balance', 'closing_balance', 'opened_by_pos_at', 'closed_by_pos_at', 'closed_at', 'created_at',
            ]);
    }
);

test(
    'getOpenCountersCountFilterByStoreAndDates method return the counter updates count after location day close closed_at',
    function (): void {
        [$location, $locationDayClose, $counterUpdate] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getOpenCountersCountFilterByStoreAndDates($location->id, $locationDayClose);
        expect($response)->toEqual(1);
    }
);

test(
    'getByStoreWithPaymentsFilterByDates method return the counter updates with payments after location day close closed_at',
    function (): void {
        [$location,
            $locationDayClose,
            $counterUpdate] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getByStoreWithPaymentsFilterByDates($location->id, $locationDayClose);

        expect($response->first()->toArray())
            ->toHaveKey('id', $counterUpdate->id)
            ->toHaveKey('payments');
    }
);

test(
    'the getPaginatedLastThirtyDaysClosedCountersForPos method returns the last thirty days closed counters',
    function (): void {
        $location = Location::factory()->create([
            'name' => 'ABCD',
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opening_balance' => '100',
            'closed_at' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'mismatch_amount' => null,
            'amount_mismatch_reason' => null,
        ]);
        $filterData = [
            'per_page' => 1,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'after_updated_at' => null,
        ];
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getPaginatedLastThirtyDaysClosedCountersForPos(
            $filterData,
            $location->company_id,
            $location->id
        );
        expect($response->toArray())
            ->toHaveKey('current_page', 1)
            ->toHaveKey('per_page', 1)
            ->toHaveKey('total', 1)
            ->toHaveKey('last_page', 1)
            ->toHaveKey('data.0.id', $counterUpdate->id)
            ->toHaveKey('data.0.counter_id', $counterUpdate->counter_id)
            ->toHaveKey('data.0.counter')
            ->toHaveKey('data.0.payments');
    }
);

test('getByIdWithClosedAtColumn method return the closed_at column', function (): void {
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getByIdWithClosedAtColumn($counterUpdate->id);
    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'closed_at']);
});

test('getByIdWithRelationsFilterByStore method returns the counter update details', function (): void {
    $location = Location::factory()->create([
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    CloseCounterDenomination::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);
    CloseCounterPayment::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getByIdWithRelationsFilterByStore($location->id, $counterUpdate->id);
    expect($response->toArray())
        ->toHaveKeys(['id', 'denominations', 'payments', 'payments.0.payment_type']);
});

test('ClosedCounterQueryList method return closed counter update details', function (): void {
    $filterData = [
        'per_page' => 1,
        'search_text' => '',
        'sort_by' => '',
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'date_range' => null,
        'closed_at' => null,
    ];

    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;
    $this->employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $this->employeeId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => '2006-12-10 11:01:13',
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
        'sales_collection_amount' => 100,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();

    $response = $counterUpdateQueries->closedCounterQueryList($filterData, $this->companyId);
    expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id)
        ->toHaveKey('opened_by_pos_at', $counterUpdate->opened_by_pos_at);
});

test('getByIdFilterByCompany method return closed counter update details', function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;
    $this->employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $this->employeeId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => '2006-12-10 11:01:13',
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getByIdFilterByCompany($counterUpdate->id, $this->companyId);
    expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id);
});

test('closedCounterListForExport method return closed counter update details', function (): void {
    $filterData = [
        'search_text' => '',
        'sort_by' => '',
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
        'date_range' => null,
        'closed_at' => null,
    ];

    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;
    $this->employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $this->employeeId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => '2006-12-10 11:01:13',
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();

    $response = $counterUpdateQueries->closedCounterListForExport($filterData, $this->companyId);
    expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id);
});

test('getPaginatedClosedCounterListForStoreManager method return closed counter update details', function (): void {
    $filterData = [
        'per_page' => 1,
        'search_text' => '',
        'sort_by' => '',
        'counter_ids' => null,
        'cashier_id' => null,
        'date_range' => null,
        'closed_at' => null,
    ];

    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;
    $this->employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $this->employeeId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => '2006-12-10 11:01:13',
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
        'sales_collection_amount' => 100,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();

    $response = $counterUpdateQueries->getPaginatedClosedCounterListForStoreManager(
        $filterData,
        $location->company_id,
        $location->id
    );
    expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id)
        ->toHaveKey('opened_by_pos_at', $counterUpdate->opened_by_pos_at);
});

test('getByIdFilterByCompanyAndStore method return closed counter update details', function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;
    $this->employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $this->employeeId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => '2006-12-10 11:01:13',
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getByIdFilterByCompanyAndStore($counterUpdate->id, $location->id);
    expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id);
});

test(
    'closedCounterQueryListForExportInStoreManagerPanel method return closed counter update details',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'counter_ids' => null,
            'cashier_id' => null,
            'date_range' => null,
            'closed_at' => null,
        ];

        $this->companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;
        $this->employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
        ])->id;
        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $this->employeeId,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
            'opening_balance' => '100.00',
            'closed_at' => '2006-12-10 11:01:13',
            'mismatch_amount' => null,
            'amount_mismatch_reason' => null,
        ]);

        $counterUpdateQueries = new CounterUpdateQueries();

        $response = $counterUpdateQueries->closedCounterQueryListForExportInStoreManagerPanel(
            $filterData,
            $location->company_id,
            $location->id
        );
        expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id);
    }
);

test(
    'getCounterUpdateAttemptDetailsByIdAndFilterByCompany method return closed counter update details',
    function (): void {
        $this->companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;

        $this->employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
        ])->id;

        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $counterUpdateDeclarationAttempt = CounterUpdateDeclarationAttempt::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $counterUpdateDeclarationAttempt->counterUpdateDeclarationAttemptPayments = CounterUpdateDeclarationAttemptPayment::factory()->create(
            [
                'counter_update_declaration_attempt_id' => $counterUpdateDeclarationAttempt->id,
            ]
        );

        $counterUpdateQueries = new CounterUpdateQueries();

        $counterUpdate->counterUpdateDeclarationAttempts = $counterUpdateDeclarationAttempt;
        $response = $counterUpdateQueries->getCounterUpdateAttemptDetailsByIdAndFilterByCompany(
            $counterUpdate->id,
            $this->companyId
        );

        expect($response->toArray())
            ->toHaveKey('counter_id', $counter->id)
            ->toHaveKey('counter.name', $counter->name)
            ->toHaveKey('counter_update_declaration_attempts');
    }
);

test(
    'getCounterUpdateAttemptDetailsByIdAndFilterByStore method return closed counter update details',
    function (): void {
        $this->companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;

        $this->employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
        ])->id;

        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $counterUpdateDeclarationAttempt = CounterUpdateDeclarationAttempt::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $counterUpdateDeclarationAttempt->counterUpdateDeclarationAttemptPayments = CounterUpdateDeclarationAttemptPayment::factory()->create(
            [
                'counter_update_declaration_attempt_id' => $counterUpdateDeclarationAttempt->id,
            ]
        );

        $counterUpdateQueries = new CounterUpdateQueries();

        $counterUpdate->counterUpdateDeclarationAttempts = $counterUpdateDeclarationAttempt;
        $response = $counterUpdateQueries->getCounterUpdateAttemptDetailsByIdAndFilterByStore(
            $counterUpdate->id,
            $location->id
        );

        expect($response->toArray())
            ->toHaveKey('counter_id', $counter->id)
            ->toHaveKey('counter.name', $counter->name)
            ->toHaveKey('counter_update_declaration_attempts');
    }
);

test(
    'getCounterUpdateTillDetailsByIdAndFilterByCompany method return closed counter update details',
    function (): void {
        $this->companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;

        $this->employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
        ])->id;

        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $counterUpdate->counterUpdateEvents = CounterUpdateEvent::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $counterUpdateQueries = new CounterUpdateQueries();

        $response = $counterUpdateQueries->getCounterUpdateTillDetailsByIdAndFilterByCompany(
            $counterUpdate->id,
            $this->companyId
        );

        expect($response->toArray())
            ->toHaveKey('counter_id', $counter->id)
            ->toHaveKey('counter.name', $counter->name)
            ->toHaveKey('counter_update_events');
    }
);

test(
    'getCounterUpdateTillDetailsByIdAndFilterByStore method return closed counter update details',
    function (): void {
        $this->companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;

        $this->employeeId = Employee::factory()->create([
            'company_id' => $this->companyId,
        ])->id;

        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
        ]);

        $counterUpdate->counterUpdateEvents = CounterUpdateEvent::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $counterUpdateQueries = new CounterUpdateQueries();

        $response = $counterUpdateQueries->getCounterUpdateTillDetailsByIdAndFilterByStore(
            $counterUpdate->id,
            $location->id
        );

        expect($response->toArray())
            ->toHaveKey('counter_id', $counter->id)
            ->toHaveKey('counter.name', $counter->name)
            ->toHaveKey('counter_update_events');
    }
);

test('getByIdOrByCounterIdAndOpenedByPosAt method CounterUpdate data or null', function (): void {
    $counterUpdate = CounterUpdate::factory()->create([
        'closed_by_pos_at' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getByIdOrByCounterIdAndOpenedByPosAt(
        $counterUpdate->id,
        $counterUpdate->counter_id,
        $counterUpdate->opened_by_pos_at,
    );
    expect($response->toArray())
        ->toHaveKey('id', $counterUpdate->id)
        ->toHaveKey('closed_by_pos_at', $counterUpdate->closed_by_pos_at);
});

test(
    'getForSalesCollectionByFilter method return the counter updates after location day close closed_at',
    function (): void {
        [$location, $locationDayClose, $counterUpdate] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getForSalesCollectionByFilter([
            'location_ids' => null,
            'date_range' => null,
            'counter_ids' => [$counterUpdate->counter_id],
            'cashier_ids' => [$counterUpdate->cashier_id],
        ]);
        expect($response->first()->toArray())
            ->toHaveKey('id', $counterUpdate->id)
            ->toHaveKey('counter_id', $counterUpdate->counter_id)
            ->toHaveKey('cashier_id', $counterUpdate->cashier_id);
    }
);

test(
    'getOpenCounterDetailsForReportsList method return open counter details',
    function (): void {
        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => '10',
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
        ];
        $companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;
        $employeeId = Employee::factory()->create([
            'company_id' => $companyId,
        ])->id;
        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
            'opening_balance' => '100.00',
            'opened_by_pos_at' => '2023-06-22 11:01:13',
            'closed_by_pos_at' => null,
            'closed_at' => null,
        ]);
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getOpenCounterDetailsForReportsList($filterData, $companyId);
        expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('cashier_id', $cashier->id);
    }
);

test(
    'getOpenCounterDetailsExport method return open counter export details',
    function (): void {
        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => '10',
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
        ];
        $companyId = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'abc@company.test',
            'code' => 'ABC',
        ])->id;
        $employeeId = Employee::factory()->create([
            'company_id' => $companyId,
        ])->id;
        $location = Location::factory()->create([
            'name' => 'ABCD123',
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);
        $cashier = Cashier::factory()->create([
            'employee_id' => $employeeId,
        ]);
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'cashier_id' => $cashier->id,
            'opening_balance' => '100.00',
            'opened_by_pos_at' => '2023-06-22 11:01:13',
            'closed_by_pos_at' => null,
            'closed_at' => null,
        ]);
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getOpenCounterDetailsExport($filterData, $companyId);
        expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('cashier_id', $cashier->id);
    }
);

function prepareCloseCounterRecords(float $openingBalance, float $closingBalance): array
{
    return [
        'sales_collection_amount' => 0,
        'total_sales' => 1,
        'total_sales_amount' => (float) 100,
        'total_discount_amount' => 0,
        'total_sale_returns_amount' => 0,
        'opening_balance' => $openingBalance,
        'closing_balance' => $closingBalance,
        'payments' => [],
        'total_layaway_sales' => 0,
        'total_credit_sales' => 0,
        'total_voided_sales' => 0,
        'total_sale_returns' => 0,
        'total_item_wise_discount_amount' => 0,
        'total_cart_wide_discount_amount' => 0,
        'total_layaway_sales_amount' => 0,
        'total_credit_sales_amount' => 0,
        'total_voided_sales_amount' => 0,
        'total_tax_amount' => 0,
        'total_cash_ins_amount' => 0,
        'total_cash_outs_amount' => 0,
        'total_credit_notes_used_amount' => 0,
        'total_credit_notes_used' => 0,
        'total_credit_notes_refunded_amount' => 0,
        'total_credit_notes_refunded' => 0,
        'total_booking_payment_amount' => 0,
        'total_booking_payment_refunded_amount' => 0,
        'total_booking_payment_used_amount' => 0,
        'total_sales_round_off' => 0,
        'total_sale_returns_round_off' => 0,
        'total_vouchers_used' => 0,
        'total_voucher_discount_amount' => 0,
        'total_vouchers_generated' => 0,
        'total_sale_promotion_used' => 0,
        'total_sale_promotion_discount_amount' => 0,
        'total_sale_item_promotion_used' => 0,
        'total_sale_item_promotion_discount_amount' => 0,
        'total_dream_price_used' => 0,
        'total_dream_price_discount_amount' => 0,
        'total_complimentary_item_discount_used' => 0,
        'total_complimentary_item_discount_amount' => 0,
        'total_price_override_used' => 0,
        'total_price_override_discount_amount' => 0,
        'total_cashback' => 0,
        'total_cashback_amount' => 0,
        'total_cash_amount_in_sales' => 0,
        'total_cash_amount_in_booking_payment' => 0,
        'total_cash_amount_in_booking_payment_refunded' => 0,
        'total_cash_amount_in_credit_note_refunded' => 0,
        'total_new_booking_payments' => 0,
        'total_used_booking_payments' => 0,
        'total_cancel_layaway_sales' => 0,
        'total_cancel_layaway_sales_amount' => 0,
    ];
}

function counterUpdateQueryCommonSeedRecords(): array
{
    $location = Location::factory()->create([
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();

    $locationDayClose = StoreDayClose::factory()->create([
        'location_id' => $location->id,
        'opened_at' => Carbon::now()->subSeconds(5),
        'closed_at' => Carbon::now()->subSeconds(5),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ]);

    return [$location, $locationDayClose, $counterUpdate];
}

test(
    'getForSalesOverallByFilter method return the counter updates after location day close closed_at',
    function (): void {
        [$location, $locationDayClose, $counterUpdate] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $response = $counterUpdateQueries->getForSalesOverallByFilter([
            'date_range' => null,
        ]);
        expect($response->first()->toArray())
            ->toHaveKey('id', $counterUpdate->id)
            ->toHaveKey('location_name', $location->name)
            ->toHaveKeys(
                ['month', 'sale_collection_amount', 'total_sales', 'total_sale_returns', 'location_name', 'location_id']
            );
    }
);

test(
    'getByDayCloseAndStoreByType method return counter details by status',
    function (): void {
        $status = CounterStatus::ALL->value;
        [$location, $locationDayClose] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $filterData = [
            'location_id' => $location->id,
            'status' => $status,
            'search_text' => null,
        ];
        $response = $counterUpdateQueries->getByDayCloseAndStoreByType(
            $filterData,
            $location->company_id,
            $locationDayClose
        );
        expect($response->first()->toArray())
            ->toHaveKeys([
                'id', 'opening_balance', 'closing_balance', 'opened_by_pos_at', 'closed_by_pos_at', 'closed_at', 'counter.location',
            ]);
    }
);

test('findByIdAndFilterByStore method returns the counter update details', function (): void {
    $location = Location::factory()->create([
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->findByIdAndFilterByStore(
        $location->id,
        $location->company_id,
        $counterUpdate->id
    );
    expect($response->toArray())
        ->toHaveKey('id', $counterUpdate->id)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('counter_id', $counterUpdate->counter_id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance);
});

test('findByIdWithRelationsFilterByStore method returns the counter update details', function (): void {
    $location = Location::factory()->create([
        'name' => 'ABCD',
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    CloseCounterDenomination::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);
    CloseCounterPayment::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->findByIdWithRelationsFilterByStore($location->id, $counterUpdate->id);
    expect($response->toArray())
        ->toHaveKeys(['id', 'denominations', 'payments', 'payments.0.payment_type']);
});

test('findByIdFilterByCompanyAndStore method return closed counter update details', function (): void {
    $this->companyId = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ])->id;
    $this->employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
    ])->id;
    $location = Location::factory()->create([
        'name' => 'ABCD123',
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $this->employeeId,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
        'opening_balance' => '100.00',
        'closed_at' => '2006-12-10 11:01:13',
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->findByIdFilterByCompanyAndStore(
        $counterUpdate->id,
        $location->company_id,
        $location->id
    );
    expect($response->first()->toArray())
        ->toHaveKey('counter_id', $counter->id)
        ->toHaveKey('opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('closed_at', $counterUpdate->closed_at)
        ->toHaveKey('cashier_id', $cashier->id);
});

test('getOpenCounterIds method return open counter update ids', function (): void {
    $counterUpdate = CounterUpdate::factory()->create([
        'opening_balance' => '100.00',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getOpenCounterIds();
    expect($response->first()->toArray())
        ->toHaveKey('id', $counterUpdate->id);
});

test('getClosedCounterIds method return closed counter update ids', function (): void {
    $counterUpdate = CounterUpdate::factory()->create([
        'opening_balance' => '100.00',
        'closed_at' => now()->format('Y-m-d H:i:s'),
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getClosedCounterIds(
        now()->startOfDay()->format('Y-m-d H:i:s'),
        now()->endOfDay()->format('Y-m-d H:i:s')
    );
    expect($response->first()->toArray())
        ->toHaveKey('id', $counterUpdate->id);
});

test('getSalesCollectionReportByDateAndBrand method call and return proper response', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $date = Carbon::now();
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'member_id' => null,
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $date->format('Y-m-d'),
    ]);
    $product = Product::factory()->create([
        'is_non_selling_item' => false,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'returned_quantity' => 0,
        'is_exchange' => false,
        'sale_return_item_id' => null,
    ]);
    $counterUpdateQueries = new CounterUpdateQueries();
    $response = $counterUpdateQueries->getSalesCollectionReportByDateAndBrand([
        'location_ids' => null,
        'date_range' => [],
        'counter_ids' => null,
        'cashier_ids' => null,
    ]);
    expect($response->first()->toArray()[$location->id][now()->format('Y-m-d')][0])
        ->toHaveKeys([
            'brand_id',
            'location_id',
            'opened_by_pos_at',
            'total_sales',
            'total_returns',
            'sales_collection_amount',
            'brand_name',
            'location_name',
        ]);
});
test(
    'getSalesAndReturnDataByDate method return the sales and sales return data',
    function (): void {
        [$location, $locationDayClose, $counterUpdate] = counterUpdateQueryCommonSeedRecords();
        $counterUpdateQueries = new CounterUpdateQueries();
        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->opened_by_pos_at);
        $response = $counterUpdateQueries->getSalesAndReturnDataByDate([
            'location_ids' => [$location->getKey()],
            'date_range' => [],
            'date' => $carbonDate->format('Y-m-d'),
            'counter_ids' => [$counterUpdate->counter_id],
            'cashier_ids' => [$counterUpdate->cashier_id],
        ]);
        expect($response)->toBeInstanceOf(Collection::class);
    }
);
