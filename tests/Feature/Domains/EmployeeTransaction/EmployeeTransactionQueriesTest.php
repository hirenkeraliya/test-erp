<?php

declare(strict_types=1);

use App\Domains\EmployeeTransaction\EmployeeTransactionQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeGroup;

test('a employee transaction can be added', function (): void {
    $companyId = Company::factory()->create()->id;

    $admin = Admin::factory()->create();

    $employeeGroup = EmployeeGroup::factory()->create([
        'company_id' => $companyId,
        'name' => 'ABCD',
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $companyId,
        'first_name' => 'DEF',
        'last_name' => 'JKL',
        'spent_till_now' => 0,
        'loyalty_points' => 100,
        'status' => true,
        'group_id' => $employeeGroup->id,
    ]);

    $employeeTransactionQueries = new EmployeeTransactionQueries();

    $employeeTransactionQueries->addNew($employee->id, $employee->status, $admin);

    $this->assertDatabaseHas('employee_transactions', [
        'employee_id' => $employee->id,
        'user_id' => $admin->id,
        'status' => $employee->status,
    ]);
});
