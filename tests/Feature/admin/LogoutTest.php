<?php

declare(strict_types=1);

use App\Models\Admin;

test('Admin cannot logout without login', function (): void {
    $this->post(route('admin.logout'))
        ->assertStatus(302);
    $this->assertGuest('admin');
});

test('Admin can log out.', function (): void {
    $superAdmin = Admin::factory()->create();

    loginAdmin($superAdmin)
        ->post(route('admin.logout'))
        ->assertStatus(302)
        ->assertRedirect('/admin');

    $this->assertGuest('admin');
});
