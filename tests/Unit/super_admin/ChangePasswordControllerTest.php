<?php

declare(strict_types=1);

use App\Domains\SuperAdmin\DataObjects\ChangePasswordData;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\SuperAdmin\ChangePasswordController;
use App\Models\SuperAdmin;

beforeEach(function (): void {
    $this->superAdmin = new SuperAdmin([
        'password' => bcrypt('123456'),
    ]);
    loginSuperAdmin($this->superAdmin);
});

test('It calls the change password method of the super admin queries class', function (): void {
    $changePasswordData = new ChangePasswordData('123456', '1234567');

    $superAdminQueries = $this->mock(SuperAdminQueries::class, function ($mock) use ($changePasswordData): void {
        $mock->shouldReceive('changePassword')
            ->once()
            ->with($this->superAdmin, $changePasswordData);
    });

    $changePasswordController = new ChangePasswordController($superAdminQueries);
    $redirectResponse = $changePasswordController->update($changePasswordData);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
});

test('Password cannot be changed with Credentials are incorrect', function (): void {
    $changePasswordData = new ChangePasswordData('1234567', '12345678');
    $changePasswordController = new ChangePasswordController(new SuperAdminQueries());
    $changePasswordController->update($changePasswordData);
})->throws(RedirectBackWithErrorException::class);
