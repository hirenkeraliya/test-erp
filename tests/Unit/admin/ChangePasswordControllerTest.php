<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\ChangePasswordData;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Admin\ChangePasswordController;
use App\Models\Admin;

beforeEach(function (): void {
    $this->admin = new Admin([
        'password' => bcrypt('123456'),
    ]);
    $this->actingAs($this->admin, 'admin');
});

test('It calls the update password method of admin queries class', function (): void {
    $changePasswordData = new ChangePasswordData('123456', '111111');

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($changePasswordData): void {
        $mock->shouldReceive('changePassword')
            ->once()
            ->with($this->admin, $changePasswordData);
    });

    $changePasswordController = new ChangePasswordController($adminQueries);
    $redirectResponse = $changePasswordController->updatePassword($changePasswordData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
});

test('Admin password cannot be change if credentials are incorrect.', function (): void {
    $changePasswordData = new ChangePasswordData('1234567', '11111111');
    $changePasswordController = new ChangePasswordController(new AdminQueries());
    $changePasswordController->updatePassword($changePasswordData);
})->throws(RedirectBackWithErrorException::class);
