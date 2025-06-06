<?php

declare(strict_types=1);

use App\Domains\Vendor\DataObjects\VendorData;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Admin\VendorController;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the vendor queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $vendorQueries = $this->mock(VendorQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $vendorController = new VendorController($vendorQueries);

    $response = $vendorController->fetchVendors(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the addNew method of the vendor queries class and returns proper response', function (): void {
    setCompanyIdInSession();

    $vendorRecord = Vendor::factory()->make([
        'company_id' => 1,
    ])->toArray();

    unset($vendorRecord['company_id']);

    $vendorData = new VendorData(...$vendorRecord);

    $vendorQueries = $this->mock(VendorQueries::class, function ($mock) use ($vendorData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($vendorData, 1);
    });

    $vendorController = new VendorController($vendorQueries);
    $redirectResponse = $vendorController->store($vendorData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Vendor added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/vendors', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the vendor queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $vendorRecord = Vendor::factory()->make([
        'company_id' => 1,
    ])->toArray();

    $vendorQueries = $this->mock(VendorQueries::class, function ($mock) use ($vendorRecord, $companyId): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Vendor($vendorRecord));
    });

    $vendorController = new VendorController($vendorQueries);
    $response = $vendorController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'vendor',
            fn (Assert $vendor): Assert => $vendor
            ->where('name', $vendorRecord['name'])
            ->where('code', $vendorRecord['code'])
            ->where('email', $vendorRecord['email'])
            ->etc()
        )
    );
});

test('It calls the update method of the vendor queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $vendorRecord = Vendor::factory()->make([
        'company_id' => 1,
    ])->toArray();

    unset($vendorRecord['company_id']);

    $vendorData = new VendorData(...$vendorRecord);

    $vendorQueries = $this->mock(VendorQueries::class, function ($mock) use ($vendorData, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($vendorData, 1, $companyId);
    });

    $vendorController = new VendorController($vendorQueries);
    $redirectResponse = $vendorController->update($vendorData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Vendor updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/vendors', $redirectResponse->getTargetUrl());
});

test('It calls the exportVendors method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $vendorQueries = $this->mock(VendorQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getVendorsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Vendor()));
    });

    $vendorController = new VendorController($vendorQueries);

    $response = $vendorController->exportVendors('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
