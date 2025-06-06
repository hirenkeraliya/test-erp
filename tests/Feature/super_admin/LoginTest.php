<?php

declare(strict_types=1);

use App\Models\SuperAdmin;

beforeEach(function (): void {
    $this->superAdmin = SuperAdmin::factory()->create();
});

test('super admin cannot login with Credentials are incorrect', function (): void {
    $this->post(route('super_admin.login_user'), [
        'username' => $this->superAdmin->username,
        'password' => '1234567',
    ])
    ->assertStatus(302)
    ->assertRedirect('/');

    $this->assertGuest('super_admin');
});

test('super admin can login with correct credentials', function (): void {
    $this->post(route('super_admin.login_user'), [
        'username' => $this->superAdmin->username,
        'password' => '123456',
    ])
    ->assertStatus(302)
    ->assertRedirect(route('super_admin.dashboard'));

    $this->assertAuthenticatedAs($this->superAdmin, 'super_admin');
});

test('super admin cannot visit the dashboard page without login', function (): void {
    $this->get(route('super_admin.dashboard'))
    ->assertStatus(302)
    ->assertRedirect('/super-admin');

    $this->assertGuest('super_admin');
});

test('super admin cannot visit the login page after login', function (): void {
    $this->post(route('super_admin.login_user'), [
        'username' => $this->superAdmin->username,
        'password' => '123456',
    ])
    ->assertStatus(302);

    $this->get('/super-admin')
    ->assertRedirect(route('super_admin.dashboard'));
});
