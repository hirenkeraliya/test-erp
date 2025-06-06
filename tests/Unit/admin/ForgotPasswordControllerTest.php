<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Models\Admin;
use App\Models\Employee;
use App\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

test('validation works while forgot password', function (): void {
    $request = new Request([
        'username' => '',
    ]);

    $forgotPasswordController = new ForgotPasswordController(new AdminQueries());
    $forgotPasswordController->forgotPassword($request);
})->throws(ValidationException::class);

test('It calls the forgot password method of admin queries class', function (): void {
    Notification::fake();

    $request = new Request([
        'username' => 'ABCDE',
    ]);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'username' => 'abc',
        'forgot_password_token' => '123456',
    ]);

    $admin->employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
        'email' => 'admin@gmail.com',
        'mobile_number' => '1234567890',
    ]);

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($request, $admin): void {
        $mock->shouldReceive('fetchAdminByUsername')
            ->once()
            ->with($request->username)
            ->andReturn($admin);

        $mock->shouldReceive('getByAdminCompanyId')
            ->once()
            ->andReturn($admin);
    });

    $forgotPasswordController = new ForgotPasswordController($adminQueries);
    $redirectResponse = $forgotPasswordController->forgotPassword($request);

    Notification::assertSentTo(new AnonymousNotifiable(), ResetPassword::class);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'If an account with the provided email address exists, you will receive an email.',
        $redirectResponse->getSession()->all()['success']
    );
});
