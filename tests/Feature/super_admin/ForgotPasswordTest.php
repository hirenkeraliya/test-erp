<?php

declare(strict_types=1);

use App\Models\SuperAdmin;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('super admin cannot visit the forget password if already logged in ', function (): void {
    loginSuperAdmin();

    $this->get(route('super_admin.forgot_password'))
        ->assertStatus(302)
        ->assertRedirect(route('super_admin.dashboard'));
});

test('super admin cannot forget password with Credentials are incorrect', function (): void {
    $superAdmin = SuperAdmin::factory()->create([
        'email' => 'test@gmail.com',
    ]);

    $this->post(route('super_admin.forgot_password'), [
        'email' => 'faketest@gmail.com',
    ])
    ->assertStatus(302)
    ->assertRedirect('/');
});

test('link is sent when forget password form is submitted', function (): void {
    $superAdmin = SuperAdmin::factory()->create();
    Notification::fake();

    $this->post(route('super_admin.forgot_password'), [
        'email' => $superAdmin->email,
    ])
    ->assertStatus(302)
    ->assertRedirect('/');

    Notification::assertSentTo($superAdmin, ResetPassword::class);

    $this->assertDatabaseHas('super_admin_password_resets', [
        'email' => $superAdmin->email,
    ]);
});

test('super admin cannot visit the reset password page if already logged in', function (): void {
    loginSuperAdmin();

    $this->get(route('super_admin.reset_password', [
        'token' => 1351,
    ]))
        ->assertStatus(302)
        ->assertRedirect(route('super_admin.dashboard'));
});

test('super admin cannot reset password with Credentials are incorrect', function (): void {
    $superAdmin = SuperAdmin::factory()->create();

    $this->post(route('super_admin.password_update', [
        'email' => $superAdmin->email,
    ]))
    ->assertSessionHasErrors('token', 'password');
});

test('super admin can reset password', function (): void {
    $superAdmin = SuperAdmin::factory()->create();

    $token = Password::broker('super_admins')->createToken($superAdmin);

    $this->post(route('super_admin.password_update', [
        'email' => $superAdmin->email,
        'password' => '11111111',
        'password_confirmation' => '11111111',
        'token' => $token,
    ]))
    ->assertStatus(302);

    $this->post(route('super_admin.login_user'), [
        'username' => $superAdmin->username,
        'password' => '11111111',
    ])
    ->assertStatus(302)
    ->assertRedirect(route('super_admin.dashboard'));
});
