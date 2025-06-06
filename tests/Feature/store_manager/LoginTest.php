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

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->storeManager->locations()->sync($this->location);
});

test('store manager cannot login with Credentials are incorrect', function (): void {
    $this->post(route('store_manager.login_user'), [
        'username' => $this->storeManager->username,
        'password' => '1234567',
    ])
        ->assertStatus(302)
        ->assertRedirect('/');

    $this->assertGuest('store_manager');
});

test('store manager can login with correct credentials', function (): void {
    $this->post(route('store_manager.login_user'), [
        'username' => $this->storeManager->username,
        'password' => '123456',
    ])
        ->assertStatus(302)
        ->assertRedirect(route('store_manager.store_selection'));

    $this->assertAuthenticatedAs($this->storeManager, 'store_manager');
});

test('store manager cannot visit the dashboard page without login', function (): void {
    $this->get(route('store_manager.dashboard'))
        ->assertStatus(302)
        ->assertRedirect('/store-manager');

    $this->assertGuest('store_manager');
});

test('store manager cannot visit the login page after login', function (): void {
    $this->post(route('store_manager.login_user'), [
        'username' => $this->storeManager->username,
        'password' => '123456',
    ])
        ->assertStatus(302);

    $this->get('/store-manager')
        ->assertRedirect(route('store_manager.store_selection'));
});

test('store manager can visit the dashboard if store is selected ', function (): void {
    loginStoreManager($this->storeManager, $this->location->id);

    setStoreManagerStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession();

    $this->get(route('store_manager.dashboard'))
        ->assertStatus(200);
});

test('store manager cannot visit the dashboard if store is not selected ', function (): void {
    $this->post(route('store_manager.login_user'), [
        'username' => $this->storeManager->username,
        'password' => '123456',
    ]);

    $this->get(route('store_manager.dashboard'))
        ->assertStatus(302)
        ->assertRedirect(route('store_manager.store_selection'));
});
