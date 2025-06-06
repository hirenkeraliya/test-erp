<?php

declare(strict_types=1);

use App\Domains\SuperAdmin\DataObjects\SuperAdminChangePasswordData;
use App\Domains\SuperAdmin\DataObjects\SuperAdminData;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('It calls the super admin queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $superAdminQueries = $this->mock(SuperAdminQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $superAdminController = new SuperAdminController($superAdminQueries);

    $response = $superAdminController->fetchSuperAdmins(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the Add admin method of super admin queries class', function (): void {
    $superAdminData = new SuperAdminData('ABC', 'XYZ', 'ABCDEF', 'test@gmail.com', null, null, null);

    $superAdminQueries = $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdminData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($superAdminData);
    });

    $superAdminController = new SuperAdminController($superAdminQueries);
    $redirectResponse = $superAdminController->store($superAdminData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Super Admin successfully added.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/super-admins', $redirectResponse->getTargetUrl());
});

test('It calls the update super admin method of the super admin queries class', function (): void {
    $superAdminData = new SuperAdminData('ABC', 'XYZ', 'ABCDEF', 'test@gmail.com', null, null, null);

    $superAdmin = SuperAdmin::factory()->make([
        'username' => 'abcd',
        'name' => 'abcdef',
        'email' => 'test2@gmail.com',
        'password' => bcrypt('123456'),
        'remember_token' => null,
    ]);

    $superAdminQueries = $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdmin): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($superAdmin);

        $mock->shouldReceive('update')
            ->once();
    });

    $superAdminController = new SuperAdminController($superAdminQueries);
    $redirectResponse = $superAdminController->update($superAdminData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Super Admin updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/super-admins', $redirectResponse->getTargetUrl());
});

test('It calls change password method of the super admin queries class', function (): void {
    $superAdminChangePasswordData = new SuperAdminChangePasswordData('111111');

    $superAdmin = SuperAdmin::factory()->make([
        'username' => 'abcd',
        'name' => 'abcdef',
        'email' => 'test2@gmail.com',
        'password' => bcrypt('123456'),
        'remember_token' => null,
    ]);

    $superAdminQueries = $this->mock(SuperAdminQueries::class, function ($mock) use (
        $superAdmin,
        $superAdminChangePasswordData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($superAdmin);

        $mock->shouldReceive('superAdminChangePassword')
            ->once()
            ->with($superAdmin, $superAdminChangePasswordData);
    });

    $superAdminController = new SuperAdminController($superAdminQueries);
    $redirectResponse = $superAdminController->updatePassword($superAdminChangePasswordData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/super-admins', $redirectResponse->getTargetUrl());
});

it('editProfile method renders the correct view with super admin data', function (): void {
    $superAdminId = 1;
    $superAdminData = new SuperAdmin([
        'name' => 'John Doe',
        'email' => 'test@mail.com',
        'username' => 'john_doe',
    ]);

    Auth::shouldReceive('id')->andReturn($superAdminId);

    $superAdminQueries = $this->mock(SuperAdminQueries::class, function ($mock) use (
        $superAdminId,
        $superAdminData
    ): void {
        $mock->shouldReceive('getById')
            ->with($superAdminId)
            ->andReturn($superAdminData);
    });

    $superAdminController = new SuperAdminController($superAdminQueries);
    $response = $superAdminController->editProfile();

    $response->rootView('super_admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('superAdmin'));
});
