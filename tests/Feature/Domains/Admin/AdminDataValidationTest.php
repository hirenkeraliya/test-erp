<?php

declare(strict_types=1);

use App\Domains\Admin\DataObjects\AdminData;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->admin = Admin::factory()->create([
        'username' => 'XYZ',
        'employee_id' => $this->employeeA->id,
    ]);

    $this->employeeB = Employee::factory()->create();
});

test(
    'unique username and employee id validation fails as expected while adding a admin',
    function (string $username, int $employeeId): void {
        $request = new Request([
            'username' => $username,
            'employee_id' => $employeeId,
            'company_id' => $this->company->id,
            'password' => '123456',
            'password_confirmation' => '123456',
            'role_ids' => [1],
        ]);

        AdminData::validate($request);
    }
)->with([
    [
        'XYZ',
        fn () => $this->employeeB->id,
    ],
    [
        'ABC',
        fn () => $this->employeeA->id,
    ],
])->throws(ValidationException::class);

test('unique username and employee id validation works while updating a admin', function (): void {
    $request = new Request([
        'username' => 'ABCD',
        'employee_id' => $this->employeeA->id,
        'company_id' => $this->company->id,
        'role_ids' => [1],
    ], server: [
        'REQUEST_URI' => 'admins/' . $this->admin->id . '/update',
    ]);

    $request->setRouteResolver(
        fn (): Route => (new Route(
            'Post',
            'admins/{adminId}/update',
            [
                'as' => 'super_admin.admins.update',
                'uses' => [AdminController::class, 'update'],
            ]
        ))->bind($request)
    );

    $request->validate(AdminData::rules($request));

    $this->assertTrue(true);
});
