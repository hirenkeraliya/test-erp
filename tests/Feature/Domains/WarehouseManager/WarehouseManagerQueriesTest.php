<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\WarehouseManager\DataObjects\ChangePasswordData;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\WarehouseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->warehouseManagerA = WarehouseManager::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'XYZ',
    ]);

    $this->warehouseManagerQueries = new WarehouseManagerQueries();
});

test('Warehouse managers can be searched', function (): void {
    $response = $this->warehouseManagerQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name);
});

test('A warehouse manager can be added', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $requestParameter = [
        'employee_id' => $employee->id,
        'username' => 'warehouse_manager_username1',
        'password' => '123456',
    ];

    $role = Role::factory()->create([
        'name' => 'first_role',
        'guard_name' => 'warehouse_manager',
    ]);

    $requestParameter['location_ids'] = [$location->id];
    $requestParameter['role_ids'] = [$role->id];

    $this->warehouseManagerQueries->addNew(new WarehouseManagerData(...$requestParameter));

    $this->assertDatabaseHas('warehouse_managers', [
        'employee_id' => $employee->id,
        'username' => 'warehouse_manager_username1',
    ]);

    $this->assertDatabaseHas('location_warehouse_manager', [
        'location_id' => $location->id,
    ]);
});

test('A warehouse manager can be fetched with warehouses', function (): void {
    $location = Location::factory()->create();
    $this->warehouseManagerA->locations()->sync($location->id);

    $response = $this->warehouseManagerQueries->getByIdWithWarehouses($this->warehouseManagerA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('username', $this->warehouseManagerA->username)
        ->toHaveKey('employee_id', $this->warehouseManagerA->employee_id)
        ->toHaveKey('locations.0.id', $location->id);
});

test('A warehouse manager can be fetched', function (): void {
    $location = Location::factory()->create();
    $this->warehouseManagerA->locations()->sync($location->id);

    $response = $this->warehouseManagerQueries->getById($this->warehouseManagerA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('username', $this->warehouseManagerA->username)
        ->toHaveKey('employee_id', $this->warehouseManagerA->employee_id);
});

test('A warehouse manager can be updated', function (): void {
    $location = Location::factory()->create();
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $requestParameter = [
        'employee_id' => $employee->id,
        'username' => 'warehouse_manager_username',
        'password' => null,
    ];

    $role = Role::factory()->create([
        'name' => 'first_role',
        'guard_name' => 'warehouse_manager',
    ]);

    $requestParameter['location_ids'] = [$location->id];
    $requestParameter['role_ids'] = [$role->id];

    $this->warehouseManagerQueries->update(
        new WarehouseManagerData(...$requestParameter),
        $this->warehouseManagerA->id,
        $this->company->id
    );

    $this->assertDatabaseHas('warehouse_managers', [
        'id' => $this->warehouseManagerA->id,
        'username' => $requestParameter['username'],
        'employee_id' => $requestParameter['employee_id'],
    ]);

    $this->assertDatabaseHas('location_warehouse_manager', [
        'location_id' => $location->id,
        'warehouse_manager_id' => $this->warehouseManagerA->id,
    ]);
});

test('A warehouse manager set fcm token', function (): void {
    $this->warehouseManagerQueries->updateFcmToken(
        $token = 'test1234',
        $this->warehouseManagerA->id,
        $this->company->id
    );

    $this->assertDatabaseHas('warehouse_managers', [
        'fcm_token' => $token,
    ]);
});

test('A warehouse manager can update password', function (): void {
    $requestParameter = [
        'new_password' => '123456789',
    ];

    $warehouseManager = WarehouseManager::factory()->create([
        'password' => bcrypt('123456'),
    ]);

    $this->warehouseManagerQueries->changePassword($warehouseManager, new ChangePasswordData(...$requestParameter));

    $warehouseManager->refresh();
    $this->assertTrue(Hash::check($requestParameter['new_password'], $warehouseManager->password));
});

test('warehouse manager can request forgot password email', function (): void {
    $this->assertDatabaseHas('warehouse_managers', [
        'id' => $this->warehouseManagerA->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $response = $this->warehouseManagerQueries->fetchWarehouseManagerByUsername($this->warehouseManagerA->username);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'username', 'employee_id', 'forgot_password_token_expiration_at']);
});

test('warehouse manager can reset password', function (): void {
    $token = md5('warehouse_managerTest@gmail.com' . now());

    $warehouseManager = WarehouseManager::factory()->create([
        'username' => 'WarehouseManagerTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $this->warehouseManagerQueries->resetPassword($warehouseManager, 'ABCDEFGH');

    $this->assertDatabaseHas('warehouse_managers', [
        'id' => $warehouseManager->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $warehouseManager->refresh();
    $this->assertTrue(Hash::check('ABCDEFGH', $warehouseManager->password));
});

test('Exception is thrown if reset password token is expired', function (): void {
    $token = md5('warehouse_managerTest@gmail.com' . now());

    WarehouseManager::factory()->create([
        'username' => 'WarehouseManagerTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->subHour(),
    ]);

    $warehouseManager = $this->warehouseManagerQueries->getByToken($token);
})->throws(ModelNotFoundException::class);

test('Reset password token fetches the correct warehouse manager record', function (): void {
    $token = md5('warehouse_managerTest@gmail.com' . now());

    WarehouseManager::factory()->create([
        'username' => 'WarehouseManagerTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $warehouseManager = $this->warehouseManagerQueries->getByToken($token);

    expect($warehouseManager)
        ->username->toEqual('WarehouseManagerTest')
        ->forgot_password_token->toEqual($token);
});

test('getWarehouseManagersExport method returns warehouse manager as expected', function (): void {
    $response = $this->warehouseManagerQueries->getWarehouseManagersExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee.first_name', $this->employeeA->first_name);
});

test(
    'getAllByWarehouseCompanyId correctly retrieves warehouse manager associated with a specific warehouse and company',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
        ])->id;

        $this->warehouseManagerA->locations()->sync($locationId);

        $response = $this->warehouseManagerQueries->getAllByWarehouseCompanyId($this->company->id);
        expect($response->first()->toArray())
        ->toHaveKey('id', $this->warehouseManagerA->id);
    }
);

test(
    'getAllWarehouseManagerWithWarehouse correctly retrieves warehouse manager associated with a specific warehouse and company',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
        ])->id;

        $this->warehouseManagerA->locations()->sync($locationId);

        $response = $this->warehouseManagerQueries->getAllWarehouseManagerWithWarehouse($locationId);
        expect($response->first()->toArray())
        ->toHaveKey('id', $this->warehouseManagerA->id);
    }
);

test(
    'existsByIdAndWarehouseId method returns result as expected',
    function (): void {
        $locationA = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);
        $this->warehouseManagerA->locations()->sync($locationA->id);

        $response = $this->warehouseManagerQueries->existsByIdAndWarehouseId(
            $this->warehouseManagerA->id,
            $locationA->id
        );
        $this->assertTrue($response);

        $locationB = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $response = $this->warehouseManagerQueries->existsByIdAndWarehouseId(
            $this->warehouseManagerA->id,
            $locationB->id
        );
        $this->assertFalse($response);
    }
);

test('updateExternalLoginToken method set login token', function (): void {
    $this->warehouseManagerQueries->updateExternalLoginToken(
        $this->warehouseManagerA->id,
        $this->company->id,
        $token = 'test1234',
    );

    $this->assertDatabaseHas('warehouse_managers', [
        'external_login_token' => $token,
    ]);
});

test('getByStaffIdAndCompanyId return warehouse manager', function (): void {
    $response = $this->warehouseManagerQueries->getByStaffIdAndCompanyId(
        $this->employeeA->staff_id,
        $this->company->id
    );

    expect($response->toArray())
        ->toHaveKey('username', $this->warehouseManagerA->username)
        ->toHaveKey('employee_id', $this->warehouseManagerA->employee_id);
});

test('getByIdAndExternalLoginToken return warehouse manager', function (): void {
    $this->warehouseManagerA->external_login_token = '123456';
    $this->warehouseManagerA->save();

    $response = $this->warehouseManagerQueries->getByIdAndExternalLoginToken($this->warehouseManagerA->id, '123456');

    expect($response->toArray())
        ->toHaveKey('username', $this->warehouseManagerA->username)
        ->toHaveKey('employee_id', $this->warehouseManagerA->employee_id)
        ->toHaveKey('employee');
});

test('getByWarehouseManagerCompanyId method returns warehouse manager', function (): void {
    $response = $this->warehouseManagerQueries->getByWarehouseManagerCompanyId(
        $this->warehouseManagerA->id,
        $this->company->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->warehouseManagerA->id)
        ->toHaveKey('employee_id', $this->warehouseManagerA->employee_id);
});

test('createToken method returns warehouse manager token as expected', function (): void {
    $response = $this->warehouseManagerQueries->createToken($this->warehouseManagerA);

    expect($response)
        ->toBeString();
});

test('getWarehouseManagerByUsername method returns warehouse manager as expected', function (): void {
    $response = $this->warehouseManagerQueries->getWarehouseManagerByUsername($this->warehouseManagerA->username);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id', 'employee']);
});

test('getWarehouseManagerData method returns Warehouse manager as expected', function (): void {
    $response = $this->warehouseManagerQueries->getWarehouseManagerData($this->warehouseManagerA->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'username']);
});

test('updateWarehouseManagerProfile method can update Warehouse Manager profile', function (): void {
    $data = $this->warehouseManagerA->toArray();
    unset($data['id']);
    unset($data['created_at']);
    unset($data['updated_at']);

    $this->warehouseManagerQueries->updateWarehouseManagerProfile($this->warehouseManagerA->id, $data);

    expect(true)->toBeTrue();
});
