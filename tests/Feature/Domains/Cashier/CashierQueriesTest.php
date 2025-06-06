<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\DataObjects\CashierChangePinData;
use App\Domains\Cashier\DataObjects\CashierData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\CashierGroupPermission;
use App\Models\Company;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->cashierGroup = CashierGroup::factory()->create([
        'company_id' => $this->companyId,
    ]);

    CashierGroupPermission::create([
        'cashier_group_id' => $this->cashierGroup->id,
        'permission_id' => 1,
    ]);

    $this->cashierA = Cashier::factory()->create([
        'employee_id' => $this->employeeA->id,
        'cashier_group_id' => $this->cashierGroup->id,
        'username' => 'DEF',
    ]);

    $this->cashierB = Cashier::factory()->create([
        'employee_id' => $this->employeeB->id,
        'username' => 'ABC',
    ]);

    $this->cashierQueries = new CashierQueries();

    setCompanyIdInSession($this->companyId);
});

test('Cashiers can be searched', function (): void {
    $response = $this->cashierQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('employee_id', $this->cashierA->employee_id)
        ->toHaveKey('username', $this->cashierA->username);
});

test('A cashier can be stored', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $cashierArray = Cashier::factory()->make()->toArray();

    $cashierArray['pin'] = '1234';
    $cashierArray['location_ids'] = [$location->id];

    $admin = Admin::factory()->create();

    $this->cashierQueries->addNew(new CashierData(...$cashierArray), $admin);

    unset($cashierArray['location_ids'], $cashierArray['pin']);

    $this->assertDatabaseHas('cashiers', $cashierArray);

    $this->assertDatabaseHas('cashier_location', [
        'location_id' => $location->id,
    ]);
});

test('A cashier can be fetched', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->cashierA->locations()->sync($location->id);

    $response = $this->cashierQueries->getByIdWithLocations($this->cashierA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('username', $this->cashierA->username)
        ->toHaveKey('employee_id', $this->cashierA->employee_id)
        ->toHaveKey('cashier_group_id', $this->cashierA->cashier_group_id)
        ->toHaveKey('locations.0.id', $location->id)
        ->toHaveKey('cashier_group.id', $this->cashierGroup->id);
});

test('A cashier can be updated', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $cashierArray = Cashier::factory()->make()->toArray();

    $cashierArray['pin'] = '1234';
    $cashierArray['location_ids'] = [$location->id];

    $this->cashierQueries->update(new CashierData(...$cashierArray), $this->cashierA->id, $this->companyId);

    unset($cashierArray['location_ids']);

    $this->assertDatabaseHas('cashiers', [
        'username' => $cashierArray['username'],
        'employee_id' => $cashierArray['employee_id'],
        'cashier_group_id' => $cashierArray['cashier_group_id'],
        'pin' => $this->cashierA->pin,
    ]);

    $this->assertDatabaseHas('cashier_location', [
        'location_id' => $location->id,
    ]);
});

test(
    'It calls getByUsernameWithEmployeeDetails of the cashierQueries Class and returns the cashier',
    function (): void {
        $response = $this->cashierQueries->getByUsernameWithEmployeeDetails($this->cashierA->username);
        expect($response->toArray())
            ->toHaveKey('id', $this->cashierA->id)
            ->toHaveKey('last_login_at', $this->cashierA->last_login_at)
            ->toHaveKey('employee.id', $this->cashierA->employee->id);
    }
);

test(
    'It call checkCompanyAndGetByUsernameWithEmployeeDetails and retrieves a cashier with an active company and returns null for deleted companies',
    function (): void {
        $response = $this->cashierQueries->checkCompanyAndGetByUsernameWithEmployeeDetails($this->cashierA->username);
        expect($response->toArray())
            ->toHaveKey('id', $this->cashierA->id)
            ->toHaveKey('last_login_at', $this->cashierA->last_login_at)
            ->toHaveKey('employee.id', $this->cashierA->employee->id);

        $this->cashierA->employee->company->deleted_at = now();
        $this->cashierA->employee->company->save();

        $response = $this->cashierQueries->checkCompanyAndGetByUsernameWithEmployeeDetails($this->cashierA->username);

        expect($response)->toBeNull();
    }
);

test(
    'it can load the employee and cashier group details',
    function (): void {
        $this->cashierA->last_login_at = now()->format('Y-m-d H:i:s');
        $this->cashierA->save();

        $response = $this->cashierQueries->loadDetailsForMeApiEndpoint($this->cashierA);
        expect($response->toArray())
            ->toHaveKey('id', $this->cashierA->id)
            ->toHaveKey('last_login_at', $this->cashierA->last_login_at)
            ->toHaveKey('employee.id', $this->employeeA->id)
            ->toHaveKey('employee.email', $this->employeeA->email)
            ->toHaveKey('cashier_group.id', $this->cashierGroup->id)
            ->toHaveKey('cashier_group.permissions', [[
                'cashier_group_id' => $this->cashierGroup->id,
                'permission_id' => 1,
            ]]);
    }
);

test('it can load the location and company configuration details', function (): void {
    $this->cashierA->last_login_at = now()->format('Y-m-d H:i:s');
    $this->cashierA->save();

    $response = $this->cashierQueries->loadDetailsForConfigurationAPI($this->cashierA);

    expect($response->toArray())
        ->toHaveKey('id', $this->cashierA->id)
        ->toHaveKey('last_login_at', $this->cashierA->last_login_at);
});

test(
    'It calls loadLocationsAndGetWithBasicColumns of the cashierQueries Class and returns the stores',
    function (): void {
        $location = Location::factory()->create([
            'type_id' => LocationTypes::STORE->value,
        ]);
        $cashier = Cashier::factory()->create();
        $cashier->locations()->sync($location->id);

        $response = $this->cashierQueries->loadLocationsAndGetWithBasicColumns($cashier);

        expect($response[0])
            ->toHaveKey('id', $location->id)
            ->toHaveKey('name', $location->name)
            ->toHaveKey('code', $location->code)
            ->toHaveKey('mobile', $location->mobile);
    }
);

test('A cashier can update pin', function (): void {
    $requestParameter = [
        'new_pin' => '1111',
    ];

    $cashier = Cashier::factory()->create();

    $this->cashierQueries->changePin($cashier, new CashierChangePinData(...$requestParameter));

    $cashier->refresh();
    $this->assertEquals($requestParameter['new_pin'], $cashier->pin);
});

test('Set counter update id while open counter', function (): void {
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'cashier_id' => $cashier->id,
    ]);

    $this->cashierQueries->setCounterUpdateId($cashier, $counterUpdate->id);

    $this->assertDatabaseHas('cashiers', [
        'id' => $cashier->id,
        'counter_update_id' => $counterUpdate->id,
    ]);
});

test('It returns the cashier locations id', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->cashierA->locations()->sync($location->id);

    $response = $this->cashierQueries->getCashierLocationsId($this->cashierA);
    expect($response)->toEqual([$location->id]);
});

test('loads counter update employee and counter details of cashier', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();

    $cashier = Cashier::factory()->create([
        'username' => 'EFG',
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->save();

    $response = $this->cashierQueries->loadDetailsForCounterCloseApi($cashier);

    expect($response->toArray())
        ->toHaveKey('username', $cashier->username)
        ->toHaveKey('counter_update.opening_balance', $counterUpdate->opening_balance)
        ->toHaveKey('counter_update.counter.name', $counterUpdate->counter->name)
        ->toHaveKey('employee.first_name');
});

test('unsetCounterUpdateId method unsets the counter update id', function (): void {
    $cashier = Cashier::factory()->create();
    $counterUpdate = CounterUpdate::factory()->create([
        'cashier_id' => $cashier->id,
    ]);
    $cashier->counter_update_id = $counterUpdate->id;

    $this->cashierQueries->unsetCounterUpdateId($cashier);

    $this->assertDatabaseHas('cashiers', [
        'id' => $cashier->id,
        'counter_update_id' => null,
    ]);
});

test('getCashierCompanyId method returns company id as per the logged in cashier', function (): void {
    $employee = Employee::factory()->create();

    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $response = $this->cashierQueries->getCashierCompanyId($cashier);
    expect($response)->toEqual($employee->company_id);
});

test('getByCounterUpdateId method returns the cashier by counter update id', function (): void {
    $employee = Employee::factory()->create();
    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'cashier_id' => $cashier->id,
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->save();

    $response = $this->cashierQueries->getByCounterUpdateId($counterUpdate->id);

    expect($response)
        ->toHaveKey('id', $cashier->id)
        ->toHaveKey('counter_update_id', $cashier->counter_update_id);
});

test('getList method returns the list of cashiers', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyId,
    ]);

    $this->cashierA->locations()->attach($location->id);

    $response = $this->cashierQueries->getList($location->id);
    expect(collect($response)->first()->toArray())
        ->toHaveKey('employee_id', $this->employeeA->id)
        ->toHaveKey('username', $this->cashierA->username);
});

test('getAllCashiersByCompany method returns the cashier list', function (): void {
    $response = $this->cashierQueries->getAllCashiersByCompany($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->cashierA->id)
        ->toHaveKey('employee_id', $this->cashierA->employee_id);
});

test('getCashiersExport method returns store manager as expected', function (): void {
    $response = $this->cashierQueries->getCashiersExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->cashierA->employee_id)
        ->toHaveKey('username', $this->cashierA->username);
});

test('getCashierListOfSelectedLocation method returns the store cashiers list', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyId,
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $cashier->employee = $employee;
    $cashier->location = $location;
    $cashier->locations()->sync($location->id);

    $response = $this->cashierQueries->getCashierListOfSelectedLocation($location->id, $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $cashier->id);
});

test('getCashiersOfLocations method returns the stores cashiers list', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyId,
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $cashier->employee = $employee;
    $cashier->location = $location;
    $cashier->locations()->sync($location->id);

    $response = $this->cashierQueries->getCashiersOfLocations([$location->id], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $cashier->id);
});

test('getListForStoreManagerApp method returns the list of cashiers', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyId,
    ]);

    $this->cashierA->locations()->attach($location->id);
    $filterData = [
        'location_id' => $location->id,
        'search_text' => null,
    ];
    $response = $this->cashierQueries->getListForStoreManagerApp($filterData);
    expect(collect($response)->first()->toArray())
        ->toHaveKey('employee_id', $this->employeeA->id);
});

test('it successfully retrieves a collection of cashiers by their IDs', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $response = $this->cashierQueries->getByIds([$cashier->id]);
    expect($response)->toBeInstanceOf(Collection::class);
    expect(collect($response)->first()->toArray())
        ->toHaveKey('employee.first_name', $employee->first_name);
});

test('findByCounterUpdateId method returns the cashier by counter update id', function (): void {
    $employee = Employee::factory()->create();
    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'cashier_id' => $cashier->id,
    ]);

    $cashier->counter_update_id = $counterUpdate->id;
    $cashier->save();

    $response = $this->cashierQueries->getByCounterUpdateId($counterUpdate->id);

    expect($response)
        ->toHaveKeys(['id', 'counter_update_id']);
});

test('getCashiersForBulkUpdate method call and return proper response', function (): void {
    $response = $this->cashierQueries->getCashiersForBulkUpdate($this->companyId);

    expect($response->last()->toArray())
        ->toHaveKey('id', $this->cashierA->id)
        ->toHaveKey('username', $this->cashierA->username)
        ->toHaveKey('cashier_group_id', $this->cashierA->cashier_group_id)
        ->toHaveKey('employee_id', $this->cashierA->employee_id);
});

test('A cashier can be updated by mobile number', function (): void {
    $this->cashierQueries->updateByMobileNumber(
        [
            'username' => 'xyz',
            'location_ids' => [],
        ],
        $this->employeeA->mobile_number,
        $this->companyId
    );

    $this->assertDatabaseHas('cashiers', [
        'username' => 'xyz',
    ]);
});

test('usernameTakenByAnotherCashier method returns boolean as expected', function (): void {
    $response = $this->cashierQueries->usernameTakenByAnotherCashier(
        $this->cashierA->username,
        $this->employeeA->mobile_number,
        $this->companyId
    );
    $this->assertFalse($response);

    $response = $this->cashierQueries->usernameTakenByAnotherCashier(
        $this->cashierA->username,
        $this->employeeB->mobile_number,
        $this->companyId
    );
    $this->assertTrue($response);
});
