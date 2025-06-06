<?php

declare(strict_types=1);

use App\Domains\User\UserQueries;
use App\Http\Controllers\Admin\Auth\UserResetPasswordController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation works while reset password', function (): void {
    $request = new Request([
        'token' => '',
        'password' => '',
    ]);

    $forgotPasswordController = new UserResetPasswordController(new UserQueries());
    $forgotPasswordController->resetPassword($request);
})->throws(ValidationException::class);

test('It calls the check reset password token and reset password method of user queries class', function (): void {
    $request = new Request([
        'token' => '1234564646',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $user = new User();

    $userQueries = $this->mock(UserQueries::class, function ($mock) use ($request, $user): void {
        $mock->shouldReceive('checkResetPasswordToken')
            ->once()
            ->with($request->token)
            ->andReturn($user);

        $mock->shouldReceive('resetPassword')
            ->once()
            ->with($user, $request->input('password'));
    });

    $userResetPasswordController = new UserResetPasswordController($userQueries);
    $redirectResponse = $userResetPasswordController->resetPassword($request);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password changed successfully.', $redirectResponse->getSession()->all()['success']);
});
