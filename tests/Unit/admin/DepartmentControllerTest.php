<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DataObjects\DepartmentData;
use App\Domains\Department\DepartmentQueries;
use App\Http\Controllers\Admin\DepartmentController;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the department queries class and returns proper response.', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $departmentController = new DepartmentController($departmentQueries);

    $response = $departmentController->fetchDepartments(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the add department method of department queries class.', function (): void {
    $departmentData = new DepartmentData('Department 1', 'Department001', null, 50, DiscountTypes::FLAT->value);
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
        $departmentData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($departmentData, $companyId);
    });

    $departmentController = new DepartmentController($departmentQueries);
    $redirectResponse = $departmentController->store($departmentData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The department has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/departments', $redirectResponse->getTargetUrl());
});

test('It calls the addNew method of DepartmentQueries with valid data and returns a response', function (): void {
    $departmentData = new DepartmentData('Department 1', 'Department001', null, 50, DiscountTypes::FLAT->value);
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
        $departmentData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($departmentData, $companyId);
    });

    $departmentController = new DepartmentController($departmentQueries);
    $response = $departmentController->storeAndReturn($departmentData);

    $this->assertArrayHasKey('department', $response);
});

test(
    'It calls the get by id method of the department queries class and returns proper response.',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'Department 1',
            'code' => 'Department001',
            'commission_percentage' => 0.00,
        ];

        $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new Department($requestParameter));
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->once()
                ->with(1)
                ->andReturn(new Company());
        });

        $departmentController = new DepartmentController($departmentQueries);
        $response = $departmentController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'department',
            fn (Assert $department): Assert => $department->where('name', 'Department 1')->where(
                'code',
                'Department001'
            )->etc()
        )
        );
    }
);

test('It calls the update department method of department queries class.', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $departmentData = new DepartmentData('Department 4', 'Department004', null, 50, DiscountTypes::FLAT->value);

    $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
        $departmentData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($departmentData, 1, $companyId);
    });

    $departmentController = new DepartmentController($departmentQueries);
    $redirectResponse = $departmentController->update($departmentData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Department updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/departments', $redirectResponse->getTargetUrl());
});

test(
    'It calls the getWithBasicColumns method of the department queries class and returns proper response.',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

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

test('It calls the exportDepartments method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getDepartmentsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Department()));
    });

    $departmentController = new DepartmentController($departmentQueries);

    $response = $departmentController->exportDepartments('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the getDepartmentSalesSummary method of the DepartmentQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $departmentQueries = $this->mock(DepartmentQueries::class, function ($mock): void {
            $mock->shouldReceive('getDepartmentSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $departmentController = new DepartmentController($departmentQueries);
        $redirectResponse = $departmentController->getDepartmentSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['departments', 'total_sales', 'total_units_sold']);
    }
);
