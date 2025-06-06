<?php

declare(strict_types=1);

use App\Domains\Department\DepartmentQueries;
use App\Http\Controllers\WarehouseManager\DepartmentController;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test(
    'It calls the getFilteredDepartmentsByCompanyId method of the department queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $colorQueries = $this->mock(DepartmentQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getFilteredDepartmentsByCompanyId')
                ->once()
                ->with('ab', $companyId)
                ->andReturn(new Collection([]));
        });

        $departmentController = new DepartmentController($colorQueries);
        $response = $departmentController->getFilteredDepartments(new Request([
            'search_text' => 'ab',
        ]));

        expect($response['departments'])->toBeInstanceOf(Collection::class);
    }
);

test(
    'It calls the getWithBasicColumns method of the department queries class and returns proper response.',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $department = Department::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
        ]);

        $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
            $department,
            $companyId
        ): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->with($companyId)
                ->andReturn(new Collection([$department]));
        });

        $departmentController = new DepartmentController($departmentQueries);
        $response = $departmentController->getDepartmentsList();

        expect($response['departments'][0])
            ->toHaveKey('name', $department->name);
    }
);
