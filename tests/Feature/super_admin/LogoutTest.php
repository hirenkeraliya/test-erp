<?php

declare(strict_types=1);

use App\Models\SuperAdmin;

test('super admin cannot logout without login', function (): void {
    $this->post(route('super_admin.logout'))
        ->assertStatus(302);
    $this->assertGuest('super_admin');
});

test('super admin can log out.', function (): void {
    $superAdmin = SuperAdmin::factory()->create();

    loginSuperAdmin($superAdmin)
        ->post(route('super_admin.logout'))
        ->assertStatus(302)
        ->assertRedirect('/super-admin');

    $this->assertGuest('super_admin');
});
