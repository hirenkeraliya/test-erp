<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->id,
    ]);
});

test('store selection for store manager works', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->storeManager->locations()->sync($location->id);

    $this->post(route('store_manager.login_user'), [
        'username' => $this->storeManager->username,
        'password' => '123456',
    ])
        ->assertStatus(302)
        ->assertRedirect(route('store_manager.store_selection'));

    $this->post(route('store_manager.set_selected_store'), [
        'location_id' => $location->id,
    ]);

    $this->assertEquals(session('store_manager_selected_location_id'), $location->id);
    $this->assertEquals(session('store_manager_selected_location_company_id'), $this->companyId);
});
