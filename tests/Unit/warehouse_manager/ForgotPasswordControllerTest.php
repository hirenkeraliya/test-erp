<?php

declare(strict_types=1);

use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\WarehouseManager\Auth\ForgotPasswordController;
use App\Models\Employee;
use App\Models\WarehouseManager;
use App\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

test('validation works while forgot password', function (): void {
    $request = new Request([
        'username' => '',
    ]);

    $forgotPasswordController = new ForgotPasswordController(new WarehouseManagerQueries());
    $forgotPasswordController->forgotPassword($request);
})->throws(ValidationException::class);

test('It calls the forgot password method of warehouse manager queries class', function (): void {
    Notification::fake();

    $request = new Request([
        'username' => 'ABCDE',
    ]);

    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'username' => 'abc',
        'forgot_password_token' => '123456',
    ]);

    $warehouseManager->employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
        'email' => 'abc@gmail.com',
        'mobile_number' => '1234567890',
    ]);

    $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $request,
        $warehouseManager
    ): void {
        $mock->shouldReceive('fetchWarehouseManagerByUsername')
            ->once()
            ->with($request->username)
            ->andReturn($warehouseManager);

        $mock->shouldReceive('getByWarehouseManagerCompanyId')
            ->once()
            ->andReturn($warehouseManager);
    });

    $forgotPasswordController = new ForgotPasswordController($warehouseManagerQueries);
    $redirectResponse = $forgotPasswordController->forgotPassword($request);

    Notification::assertSentTo(new AnonymousNotifiable(), ResetPassword::class);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'If an account with the provided email address exists, you will receive an email.',
        $redirectResponse->getSession()->all()['success']
    );
});
