<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\EmployeeGroup\DataObjects\PaginatedEmployeeGroupListDataForPos;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Http\Controllers\Api\Pos\EmployeeGroupController;
use App\Models\Cashier;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'it calls the getPaginateEmployeeGroup method and returns the paginated list of employee groups',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
            'counter_update_id' => 1,
        ]);

        $cashier->employee = $employee;

        $paginatedEmployeeGroupListData = [
            'per_page' => 10,
            'page' => 1,
            'sort_by' => 'id',
            'search_text' => '',
            'sort_direction' => 'desc',
            'after_updated_at' => null,
        ];

        $paginatedEmployeeGroupListDataForPos = new PaginatedEmployeeGroupListDataForPos(
            ...$paginatedEmployeeGroupListData
        );

        $filteredData = [
            'search_text' => $paginatedEmployeeGroupListDataForPos->search_text,
            'sort_by' => $paginatedEmployeeGroupListDataForPos->sort_by,
            'sort_direction' => $paginatedEmployeeGroupListDataForPos->sort_direction,
            'per_page' => $paginatedEmployeeGroupListDataForPos->per_page,
            'after_updated_at' => $paginatedEmployeeGroupListDataForPos->after_updated_at,
        ];

        $request = new Request($filteredData);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use ($filteredData): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($filteredData, 1)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);

        $response = $employeeGroupController->getPaginateEmployeeGroup($request, $paginatedEmployeeGroupListDataForPos);

        expect($response['employee_group'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);
