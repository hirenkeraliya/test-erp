<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Designation\DataObjects\SuperAdminDesignationData;
use App\Domains\Designation\DesignationQueries;
use App\Http\Controllers\SuperAdmin\DesignationController;
use App\Models\Designation;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test(
    'It calls the listQueryForSuperAdmin method of the designation queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('listQueryForSuperAdmin')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $designationController = new DesignationController($designationQueries);

        $response = $designationController->fetchDesignations(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test(
    'It calls the addForSuperAdmin method of the designation queries class and returns proper response',
    function (): void {
        $superAdmin = SuperAdmin::factory()->make();
        loginSuperAdmin($superAdmin);

        $designationRecord = Designation::factory()->make([
            'company_id' => 1,
        ])->toArray();

        $designationData = new SuperAdminDesignationData(...$designationRecord);

        $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use (
            $designationData,
            $superAdmin
        ): void {
            $mock->shouldReceive('addForSuperAdmin')
            ->once()
            ->with($designationData, $superAdmin);
        });

        $designationController = new DesignationController($designationQueries);
        $redirectResponse = $designationController->store($designationData);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'The designation was added successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('super-admin/designations', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls getByIdWithoutCompanyFilter method of the designation queries class and return proper response',
    function (): void {
        $companyId = 1;

        $requestParameter = Designation::factory()->make([
            'company_id' => $companyId,
        ])->toArray();

        $companies = [[
            'id' => '1',
            'name' => 'ABC',
        ]];

        $this->mock(CompanyQueries::class, function ($mock) use ($companies): void {
            $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn($companies);
        });

        $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('getByIdWithoutCompanyFilter')
            ->once()
            ->with(1)
            ->andReturn(new Designation($requestParameter));
        });

        $designationController = new DesignationController($designationQueries);
        $response = $designationController->edit(1);
        $response->rootView('super_admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'designation',
            fn (Assert $designation): Assert => $designation
                ->where('name', $requestParameter['name'])
                ->where('code', $requestParameter['code'])
                ->etc()
        )
        );
    }
);

test(
    'It calls the updateForSuperAdmin method of the designation queries class and returns proper response',
    function (): void {
        $designationRecord = Designation::factory()->make([
            'company_id' => 1,
        ])->toArray();

        $designationData = new SuperAdminDesignationData(...$designationRecord);

        $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use ($designationData): void {
            $mock->shouldReceive('updateForSuperAdmin')
            ->once()
            ->with($designationData, 1);
        });

        $designationController = new DesignationController($designationQueries);
        $redirectResponse = $designationController->update($designationData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Designation updated successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('super-admin/designations', $redirectResponse->getTargetUrl());
    }
);
