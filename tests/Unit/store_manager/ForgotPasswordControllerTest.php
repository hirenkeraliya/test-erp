<?php

declare(strict_types=1);

use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\StoreManager\Auth\ForgotPasswordController;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

test('validation works while forgot password', function (): void {
    $request = new Request([
        'username' => '',
    ]);

    $forgotPasswordController = new ForgotPasswordController(new StoreManagerQueries());
    $forgotPasswordController->forgotPassword($request);
})->throws(ValidationException::class);

test('It calls the forgot password method of store manager queries class', function (): void {
    Notification::fake();

    $request = new Request([
        'username' => 'ABCDE',
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'username' => 'abc',
        'forgot_password_token' => '123456',
    ]);

    $storeManager->employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
        'email' => 'abc@gmail.com',
        'mobile_number' => '1234567890',
    ]);

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $request,
        $storeManager
    ): void {
        $mock->shouldReceive('fetchStoreManagerByUsername')
            ->once()
            ->with($request->username)
            ->andReturn($storeManager);

        $mock->shouldReceive('getByStoreManagerCompanyId')
            ->once()
            ->andReturn($storeManager);
    });

    $forgotPasswordController = new ForgotPasswordController($storeManagerQueries);
    $redirectResponse = $forgotPasswordController->forgotPassword($request);

    Notification::assertSentTo(new AnonymousNotifiable(), ResetPassword::class);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'If an account with the provided email address exists, you will receive an email.',
        $redirectResponse->getSession()->all()['success']
    );
});
