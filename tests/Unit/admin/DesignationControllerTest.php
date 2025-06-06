<?php

declare(strict_types=1);

use App\Domains\Designation\DataObjects\DesignationData;
use App\Domains\Designation\DesignationQueries;
use App\Http\Controllers\Admin\DesignationController;
use App\Models\Admin;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the designation queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $designationController = new DesignationController($designationQueries);

    $response = $designationController->fetchDesignations(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the designation queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $designationData = Designation::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($designationData['company_id']);

    $request = new Request();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $designationRecords = new DesignationData(...$designationData);

    $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use (
        $designationRecords,
        $companyId,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($designationRecords, $companyId, $admin);
    });

    $designationController = new DesignationController($designationQueries);
    $redirectResponse = $designationController->store($designationRecords, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The designation was added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/designations', $redirectResponse->getTargetUrl());
});

test('It calls get by id method of the designation queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = Designation::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Designation($requestParameter));
    });

    $designationController = new DesignationController($designationQueries);
    $response = $designationController->edit(1);
    $response->rootView('admin.index');

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
});

test('It calls update method of the designation queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $designationData = Designation::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($designationData['company_id']);

    $designationRecords = new DesignationData(...$designationData);

    $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use (
        $designationRecords,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($designationRecords, 1, $companyId);
    });

    $designationController = new DesignationController($designationQueries);
    $redirectResponse = $designationController->update($designationRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Designation updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/designations', $redirectResponse->getTargetUrl());
});

test('It calls the exportDesignations method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $designationQueries = $this->mock(DesignationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getDesignationsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Designation()));
    });

    $designationController = new DesignationController($designationQueries);

    $response = $designationController->exportDesignations('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
