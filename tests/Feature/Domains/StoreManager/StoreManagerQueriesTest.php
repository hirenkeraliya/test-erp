<?php

declare(strict_types=1);

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StoreManager\DataObjects\ChangePasscodeData;
use App\Domains\StoreManager\DataObjects\ChangePasswordData;
use App\Domains\StoreManager\DataObjects\StoreManagerData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\Role;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'ABCD',
    ]);

    $this->storeManagerA = StoreManager::factory()->create([
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'XYZ',
    ]);

    $this->storeManagerB = StoreManager::factory()->create([
        'employee_id' => $this->employeeB->id,
    ]);

    $this->storeManagerQueries = new StoreManagerQueries();
});

test('Store managers can be searched', function (): void {
    $response = $this->storeManagerQueries->listQuery([
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

test('A store manager can be added', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $role = Role::factory()->create([
        'name' => 'first_role',
        'guard_name' => 'store_manager',
    ]);

    $brand = Brand::factory()->create();

    $requestParameter = [
        'employee_id' => $employee->id,
        'username' => 'store_manager_username1',
        'password' => '123456',
        'passcode' => '123456',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
        'can_manage_wholesale' => false,
    ];

    $requestParameter['location_ids'] = [$location->id];
    $requestParameter['brand_ids'] = [$brand->id];
    $requestParameter['role_ids'] = [$role->id];

    $this->storeManagerQueries->addNew(new StoreManagerData(...$requestParameter));

    $this->assertDatabaseHas('store_managers', [
        'employee_id' => $employee->id,
        'username' => 'store_manager_username1',
    ]);

    $this->assertDatabaseHas('location_store_manager', [
        'location_id' => $location->id,
    ]);

    $this->assertDatabaseHas('brand_store_manager', [
        'brand_id' => $brand->id,
    ]);
});

test('A store manager can be fetched with stores', function (): void {
    $location = Location::factory()->create();
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getByIdWithStores($this->storeManagerA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('username', $this->storeManagerA->username)
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id)
        ->toHaveKey('locations.0.id', $location->id);
});

test('A store manager can be fetched', function (): void {
    $location = Location::factory()->create();
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getById($this->storeManagerA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('username', $this->storeManagerA->username)
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id);
});

test('A store manager can be updated', function (): void {
    $location = Location::factory()->create();
    $brand = Brand::factory()->create();

    $role = Role::factory()->create([
        'name' => 'first_role',
        'guard_name' => 'store_manager',
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $requestParameter = [
        'employee_id' => $employee->id,
        'username' => 'store_manager_username',
        'password' => null,
        'passcode' => null,
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
        'can_manage_wholesale' => false,
    ];

    $requestParameter['location_ids'] = [$location->id];
    $requestParameter['brand_ids'] = [$brand->id];
    $requestParameter['role_ids'] = [$role->id];

    $this->storeManagerQueries->update(
        new StoreManagerData(...$requestParameter),
        $this->storeManagerA->id,
        $this->company->id
    );

    $this->assertDatabaseHas('store_managers', [
        'id' => $this->storeManagerA->id,
        'username' => $requestParameter['username'],
        'employee_id' => $requestParameter['employee_id'],
    ]);

    $this->assertDatabaseHas('location_store_manager', [
        'location_id' => $location->id,
        'store_manager_id' => $this->storeManagerA->id,
    ]);

    $this->assertDatabaseHas('brand_store_manager', [
        'brand_id' => $brand->id,
        'store_manager_id' => $this->storeManagerA->id,
    ]);
});

test('A store manager set fcm token', function (): void {
    $this->storeManagerQueries->updateFcmToken($token = 'test1234', $this->storeManagerA->id, $this->company->id);

    $this->assertDatabaseHas('store_managers', [
        'fcm_token' => $token,
    ]);
});

test('A store manager can update password', function (): void {
    $requestParameter = [
        'new_password' => '123456789',
    ];

    $storeManager = StoreManager::factory()->create([
        'password' => bcrypt('123456'),
    ]);

    $this->storeManagerQueries->changePassword($storeManager, new ChangePasswordData(...$requestParameter));

    $storeManager->refresh();
    $this->assertTrue(Hash::check($requestParameter['new_password'], $storeManager->password));
});

test('A store manager can update passcode', function (): void {
    $requestParameter = [
        'new_passcode' => '123456789',
    ];

    $storeManager = StoreManager::factory()->create([
        'passcode' => '123456',
    ]);

    $this->storeManagerQueries->changePasscode($storeManager, new ChangePasscodeData(...$requestParameter));

    $storeManager->refresh();
    $this->assertTrue($storeManager->passcode === $requestParameter['new_passcode']);
});

test('getStoreManagerListForPos method return the list', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->storeManagerA->locations()->attach($location->id);

    $response = $this->storeManagerQueries->getStoreManagerListForPos($location->id);

    expect($response->first()->toArray())
        ->toHaveKey('username', $this->storeManagerA->username)
        ->toHaveKey(
            'price_override_limit_percentage_for_item',
            $this->storeManagerA->price_override_limit_percentage_for_item
        )
        ->toHaveKey(
            'price_override_limit_percentage_for_cart',
            $this->storeManagerA->price_override_limit_percentage_for_cart
        )
        ->toHaveKey('employee.first_name', $this->employeeA->first_name)
        ->toHaveKey('employee.email', $this->employeeA->email);
});

test(
    'existsByIdStoreIdAndStatus method returns result as expected',
    function (): void {
        $locationA = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->storeManagerA->locations()->sync($locationA->id);

        $response = $this->storeManagerQueries->existsByIdStoreIdAndStatus($this->storeManagerA->id, $locationA->id);
        $this->assertTrue($response);

        $locationB = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $response = $this->storeManagerQueries->existsByIdStoreIdAndStatus($this->storeManagerA->id, $locationB->id);
        $this->assertFalse($response);
    }
);

test('existsByIdStoreIdAndPasscode method returns result as expected', function (): void {
    $locationA = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->storeManagerA->locations()->sync($locationA->id);

    $preparedArray = [
        'payment_type_id' => $paymentType->id,
        'amount' => 100.00,
        'currency_id' => null,
        'current_currency_rate' => null,
        'currency_amount' => null,
        'passcode' => $this->storeManagerA->passcode,
        'store_manager_id' => $this->storeManagerA->id,
    ];

    $response = $this->storeManagerQueries->existsByIdStoreIdAndPasscode(
        $locationA->id,
        new CreditNoteRefundData(...$preparedArray)
    );
    $this->assertTrue($response);
});

test('getByStoreIdWithEmployee method returns the store manager with employee', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getByStoreIdWithEmployee($location->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id)
        ->toHaveKey('employee');
});

test('getByStoreIdsWithEmployee method returns the store manager with employee', function (): void {
    $location = Location::factory([
        'type_id' => LocationTypes::STORE->value,
    ])->create();
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getByStoreIdsWithEmployee([$location->id]);

    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id)
        ->toHaveKey('employee');
});

test('the getByIds method returns the list of store managers', function (): void {
    $location = Location::factory()->create();
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getByIds([$this->storeManagerA->id], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'passcode', 'employee_id', 'employee']);
});

test('the getByIdWithEmployee method returns the store manager', function (): void {
    $location = Location::factory()->create();
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getByIdWithEmployee($this->storeManagerA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'passcode', 'employee_id', 'employee']);
});

test('Store manager can request forgot password email', function (): void {
    $this->assertDatabaseHas('store_managers', [
        'id' => $this->storeManagerA->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $response = $this->storeManagerQueries->fetchStoreManagerByUsername($this->storeManagerA->username);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'username', 'employee_id', 'forgot_password_token_expiration_at']);
});

test('Store manager can reset password', function (): void {
    $token = md5('store_managerTest@gmail.com' . now());

    $storeManager = StoreManager::factory()->create([
        'username' => 'StoreManagerTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $this->storeManagerQueries->resetPassword($storeManager, 'ABCDEFGH');

    $this->assertDatabaseHas('store_managers', [
        'id' => $storeManager->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $storeManager->refresh();
    $this->assertTrue(Hash::check('ABCDEFGH', $storeManager->password));
});

test('Exception is thrown if reset password token is expired', function (): void {
    $token = md5('store_managerTest@gmail.com' . now());

    StoreManager::factory()->create([
        'username' => 'StoreManagerTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->subHour(),
    ]);

    $storeManager = $this->storeManagerQueries->getByToken($token);
})->throws(ModelNotFoundException::class);

test('Reset password token fetches the correct store manager record', function (): void {
    $token = md5('store_managerTest@gmail.com' . now());

    StoreManager::factory()->create([
        'username' => 'StoreManagerTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $storeManager = $this->storeManagerQueries->getByToken($token);

    expect($storeManager)
        ->username->toEqual('StoreManagerTest')
        ->forgot_password_token->toEqual($token);
});

test('getStoreManagersExport method returns store manager as expected', function (): void {
    $response = $this->storeManagerQueries->getStoreManagersExport([
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
    'getAllByStoreCompanyId correctly retrieves store manager associated with a specific store and company',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
        ])->id;

        $this->storeManagerA->locations()->sync($locationId);

        $response = $this->storeManagerQueries->getAllByStoreCompanyId($this->company->id);
        expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerA->id);
    }
);

test(
    'getAllStoreManagerWithStore correctly retrieves store manager associated with a specific store and company',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
        ])->id;

        $this->storeManagerA->locations()->sync($locationId);

        $response = $this->storeManagerQueries->getAllStoreManagerWithStore($locationId);
        expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerA->id);
    }
);

test(
    'getAllStoreManagerWithStores correctly retrieves store manager associated with a specific store and company',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->company->id,
        ])->id;

        $this->storeManagerA->locations()->sync($locationId);

        $response = $this->storeManagerQueries->getAllStoreManagerWithStores([$locationId]);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->storeManagerA->id);
    }
);

test(
    'existsByIdAndStoreId method returns result as expected',
    function (): void {
        $locationA = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->storeManagerA->locations()->sync($locationA->id);

        $response = $this->storeManagerQueries->existsByIdAndStoreId($this->storeManagerA->id, $locationA->id);
        $this->assertTrue($response);

        $locationB = Location::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->storeManagerQueries->existsByIdAndStoreId($this->storeManagerA->id, $locationB->id);
        $this->assertFalse($response);
    }
);

test('findByIdAndCompanyId method returns store manager as expected', function (): void {
    $response = $this->storeManagerQueries->findByIdAndCompanyId($this->storeManagerA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id']);
});

test('loadEmployee method returns store manager With Employee as expected', function (): void {
    $response = $this->storeManagerQueries->loadEmployee($this->storeManagerA);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id', 'employee']);
});

test('getAllStoreManagerByCompany method returns expected', function (): void {
    $response = $this->storeManagerQueries->getAllStoreManagerByCompany($this->company->id);
    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'employee_id']);
});

test('getAllStoreManagerByStoreIdAndCompanyId method returns expected', function (): void {
    $locationA = Location::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->storeManagerA->locations()->sync($locationA->id);

    $response = $this->storeManagerQueries->getAllStoreManagerByStoreIdAndCompanyId($locationA->id, $this->company->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerA->id);
});

test('getByLocationIdsWithEmployee method returns the store manager with employee', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->storeManagerA->locations()->sync($location->id);

    $response = $this->storeManagerQueries->getByLocationIdsWithEmployee([$location->id]);

    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id)
        ->toHaveKey('employee');
});

test('getStoreManagersForBulkUpdate method call and return proper response', function (): void {
    $response = $this->storeManagerQueries->getStoreManagersForBulkUpdate($this->company->id);

    expect($response->last()->toArray())
        ->toHaveKey('id', $this->storeManagerA->id)
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id)
        ->toHaveKey('username', $this->storeManagerA->username)
        ->toHaveKey('price_override_type', $this->storeManagerA->price_override_type)
        ->toHaveKey(
            'price_override_limit_percentage_for_item',
            $this->storeManagerA->price_override_limit_percentage_for_item
        )
        ->toHaveKey(
            'price_override_limit_percentage_for_cart',
            $this->storeManagerA->price_override_limit_percentage_for_cart
        )
        ->toHaveKey('can_manage_wholesale', $this->storeManagerA->can_manage_wholesale)
        ->toHaveKeys(['locations', 'employee', 'brands', 'roles']);
});

test('usernameTakenByAnotherStoreManager method returns boolean as expected', function (): void {
    $response = $this->storeManagerQueries->usernameTakenByAnotherStoreManager(
        $this->storeManagerA->username,
        $this->employeeA->first_name,
        $this->company->id
    );
    $this->assertFalse($response);

    $response = $this->storeManagerQueries->usernameTakenByAnotherStoreManager(
        $this->storeManagerB->username,
        $this->employeeA->first_name,
        $this->company->id
    );
    $this->assertTrue($response);
});

test('A store manager can be updated by name', function (): void {
    $this->storeManagerQueries->updateByMobileNumber(
        [
            'username' => 'tests',
            'price_override_limit_percentage_for_cart' => 10,
            'price_override_limit_percentage_for_item' => 20,
            'role_ids' => [],
            'location_ids' => [],
            'brand_ids' => [],
        ],
        $this->employeeA->mobile_number,
        $this->company->id
    );

    $this->assertDatabaseHas('store_managers', [
        'username' => 'tests',
        'price_override_limit_percentage_for_cart' => 10,
        'price_override_limit_percentage_for_item' => 20,
    ]);
});

test('getByStoreManagerCompanyId method returns store manager', function (): void {
    $response = $this->storeManagerQueries->getByStoreManagerCompanyId($this->storeManagerA->id, $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerA->id)
        ->toHaveKey('employee_id', $this->storeManagerA->employee_id);
});

test('getAllStoreManagerWithStoreAndEmployee method returns store manager', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->storeManagerA->locations()->sync($location->id);
    $response = $this->storeManagerQueries->getAllStoreManagerWithStoreAndEmployee($location->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->storeManagerA->id);
});

test('createToken method returns store manager token as expected', function (): void {
    $response = $this->storeManagerQueries->createToken($this->storeManagerA);

    expect($response)
        ->toBeString();
});

test('getStoreManagerByUsername method returns store manager as expected', function (): void {
    $response = $this->storeManagerQueries->getStoreManagerByUsername($this->storeManagerA->username);

    expect($response->toArray())
        ->toHaveKeys(['id', 'employee_id', 'employee']);
});

test('getStoreManagerData method returns store manager as expected', function (): void {
    $response = $this->storeManagerQueries->getStoreManagerData($this->storeManagerA->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'username']);
});

test('updateStoreManagerProfile method can update Store Manager profile', function (): void {
    $data = $this->storeManagerA->toArray();
    unset($data['id']);
    unset($data['created_at']);
    unset($data['updated_at']);

    $this->storeManagerQueries->updateStoreManagerProfile($this->storeManagerA->id, $data);

    expect(true)->toBeTrue();
});
