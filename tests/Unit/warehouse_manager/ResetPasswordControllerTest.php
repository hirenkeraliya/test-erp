<?php

declare(strict_types=1);

use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\WarehouseManager\Auth\ResetPasswordController;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation works while reset password', function (): void {
    $request = new Request([
        'token' => '',
        'password' => '',
    ]);

    $forgotPasswordController = new ResetPasswordController(new WarehouseManagerQueries());
    $forgotPasswordController->resetPassword($request);
})->throws(ValidationException::class);

test(
    'It calls the check reset password token and reset password method of warehouse manager queries class',
    function (): void {
        $request = new Request([
            'token' => '1234564646',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $storeManager = new WarehouseManager();

        $storeManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
            $request,
            $storeManager
        ): void {
            $mock->shouldReceive('getByToken')
            ->once()
            ->with($request->token)
            ->andReturn($storeManager);

            $mock->shouldReceive('resetPassword')
            ->once()
            ->with($storeManager, $request->input('password'));
        });

        $forgotPasswordController = new ResetPasswordController($storeManagerQueries);
        $redirectResponse = $forgotPasswordController->resetPassword($request);
        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Password changed successfully.', $redirectResponse->getSession()->all()['success']);
    }
);
