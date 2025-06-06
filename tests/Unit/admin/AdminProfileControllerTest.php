<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Role\RoleQueries;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Route::get('/admin/admin/edit-profile', [AdminProfileController::class, 'editProfile'])->name('admin.edit_profile');
    Route::post('/admin/admin/{adminId}/update-profile', [AdminProfileController::class, 'update'])->name(
        'admin.update'
    );
});

test('editProfile method renders the correct view with admin and roles data', function (): void {
    $adminId = 1;
    $adminData = new Admin([
        'username' => 'XYZ',
        'employee_id' => 1,
        'two_factor_recovery_codes' => null,
    ]);
    $roles = ['Full Access', 'admin'];
    $roles = collect($roles);

    Auth::shouldReceive('id')->andReturn($adminId);

    $this->mock(AdminQueries::class, function ($mock) use ($adminId, $adminData): void {
        $mock->shouldReceive('getAdminData')
            ->with($adminId)
            ->andReturn($adminData);
    });

    $this->mock(RoleQueries::class, function ($mock) use ($roles): void {
        $mock->shouldReceive('getRoles')
            ->with('admin')
            ->andReturn($roles);
    });
    $adminProfileController = new AdminProfileController();
    $response = $adminProfileController->editProfile();
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('admin')->has('roles'));
});

test('update method updates admin and redirects to dashboard on success', function (): void {
    $adminData = new AdminData('XYZ', 1, '', [1], null, null, null);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $this->mock(AdminQueries::class, function ($mock) use ($adminData): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($adminData, 1);
    });

    $adminProfileController = new AdminProfileController();
    $redirectResponse = $adminProfileController->update($adminData, $admin->id);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Admin updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/dashboard', $redirectResponse->getTargetUrl());
});
