<?php

declare(strict_types=1);

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Director\DataObjects\ChangePasscodeData;
use App\Domains\Director\DataObjects\DirectorData;
use App\Domains\Director\DirectorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Location;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'test@company.com',
    ]);

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
        'status' => true,
    ]);

    $this->directorA = Director::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'XYZ',
    ]);

    $this->directorB = Director::factory()->create([
        'employee_id' => $this->employeeB->id,
    ]);

    $this->directorQueries = new DirectorQueries();
});

test('directors can be searched', function (): void {
    $response = $this->directorQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name)
        ->toHaveKey('employee.email', $this->employeeA->email);
});

test('A director can be added', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $admin = Admin::factory()->create();

    $requestParameter = [
        'employee_id' => $employee->id,
        'passcode' => '123456',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
    ];

    $requestParameter['location_ids'] = [$location->id];

    $this->directorQueries->addNew(new DirectorData(...$requestParameter), $admin);

    $this->assertDatabaseHas('directors', [
        'employee_id' => $employee->id,
        'passcode' => '123456',
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
    ]);

    $this->assertDatabaseHas('director_location', [
        'location_id' => $location->id,
    ]);
});

test('A director can be fetched with stores', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->directorA->locations()->sync($location->id);

    $response = $this->directorQueries->getByIdWithEmployeeAndLocations($this->directorA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('employee_id', $this->directorA->employee_id)
        ->toHaveKey('locations.0.id', $location->id);
});

test('A director can be fetched', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->directorA->locations()->sync($location->id);

    $response = $this->directorQueries->getById($this->directorA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('employee_id', $this->directorA->employee_id);
});

test('A director can be updated', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $requestParameter = [
        'employee_id' => $employee->id,
        'passcode' => '1234',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
    ];

    $requestParameter['location_ids'] = [$location->id];

    $this->directorQueries->update(
        new DirectorData(...$requestParameter),
        $this->directorA->id,
        $this->company->id
    );

    $this->assertDatabaseHas('directors', [
        'id' => $this->directorA->id,
        'employee_id' => $requestParameter['employee_id'],
    ]);

    $this->assertDatabaseHas('director_location', [
        'location_id' => $location->id,
        'director_id' => $this->directorA->id,
    ]);
});

test(
    'getList method returns the directors list',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->directorA->locations()->attach($location->id);

        $response = $this->directorQueries->getList($location->id, $this->company->id);

        expect($response->first()->toArray())
            ->toHaveKey('employee.first_name', $this->employeeA->first_name)
            ->toHaveKey('employee.last_name', $this->employeeA->last_name)
            ->toHaveKey('employee.email', $this->employeeA->email);
    }
);

test('A director can update passcode', function (): void {
    $requestParameter = [
        'new_passcode' => '111111',
    ];

    $director = Director::factory()->create();

    $this->directorQueries->changePasscode($director, new ChangePasscodeData(...$requestParameter));

    $director->refresh();
    $this->assertTrue($director->passcode === $requestParameter['new_passcode']);
});

test(
    'existsByIdLocationIdAndStatus method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->directorA->locations()->sync($location->id);

        $response = $this->directorQueries->existsByIdLocationIdAndStatus(
            $this->directorA->id,
            $this->company->id,
            $location->id
        );
        $this->assertTrue($response);

        $locationB = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $response = $this->directorQueries->existsByIdLocationIdAndStatus(
            $this->directorA->id,
            $this->company->id,
            $locationB->id
        );
        $this->assertFalse($response);
    }
);

test('the getByIds method returns the list of directors', function (): void {
    $response = $this->directorQueries->getByIds([$this->directorA->id], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'passcode', 'employee_id', 'employee']);
});

test('getDirectorsExport method returns director as expected', function (): void {
    $response = $this->directorQueries->getDirectorsExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name)
        ->toHaveKey('employee.email', $this->employeeA->email);
});

test('findByIdAndCompanyId method returns director as expected', function (): void {
    $response = $this->directorQueries->findByIdAndCompanyId($this->directorA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id']);
});

test('the getByIdWithEmployee method returns the director', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->directorA->locations()->sync($location->id);

    $response = $this->directorQueries->getByIdWithEmployee($this->directorA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'passcode', 'employee_id', 'employee']);
});
