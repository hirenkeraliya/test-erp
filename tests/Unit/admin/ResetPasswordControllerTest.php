<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation works while reset password', function (): void {
    $request = new Request([
        'token' => '',
        'password' => '',
    ]);

    $forgotPasswordController = new ResetPasswordController(new AdminQueries());
    $forgotPasswordController->resetPassword($request);
})->throws(ValidationException::class);

test('It calls the check reset password token and reset password method of admin queries class', function (): void {
    $request = new Request([
        'token' => '1234564646',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $admin = new Admin();

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($request, $admin): void {
        $mock->shouldReceive('checkResetPasswordToken')
            ->once()
            ->with($request->token)
            ->andReturn($admin);

        $mock->shouldReceive('resetPassword')
            ->once()
            ->with($admin, $request->input('password'));
    });

    $forgotPasswordController = new ResetPasswordController($adminQueries);
    $redirectResponse = $forgotPasswordController->resetPassword($request);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password changed successfully.', $redirectResponse->getSession()->all()['success']);
});
