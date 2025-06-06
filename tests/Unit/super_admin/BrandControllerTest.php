<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Brand\DataObjects\BrandData;
use App\Domains\Brand\Jobs\BrandSyncMainJob;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\SuperAdmin\BrandController;
use App\Models\Brand;
use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the brand queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $brandQueries = $this->mock(BrandQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $brandController = new BrandController($brandQueries);

    $response = $brandController->fetchBrands(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the Add brand method of brand queries class', function (): void {
    $brandData = new BrandData('XYZ', 'XYZ123');

    $brandQueries = $this->mock(BrandQueries::class, function ($mock) use ($brandData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($brandData);
    });

    $brandController = new BrandController($brandQueries);
    $redirectResponse = $brandController->store($brandData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Brand added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/brands', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the brand queries class and returns proper response', function (): void {
    $requestParameter = [
        'name' => 'xyz',
        'code' => 'xyz123',
    ];

    $brandQueries = $this->mock(BrandQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn(new Brand($requestParameter));
    });

    $brandController = new BrandController($brandQueries);
    $response = $brandController->edit(1);
    $response->rootView('super_admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has('brand', fn (Assert $brand): Assert => $brand->where('name', 'xyz')->where('code', 'xyz123'))
    );
});

test('It can call updateBrand method of brand queries class', function (): void {
    $brandData = new BrandData('XYZ', 'XYZ123');

    $brandQueries = $this->mock(BrandQueries::class, function ($mock) use ($brandData): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($brandData, 1);
    });

    $brandController = new BrandController($brandQueries);
    $redirectResponse = $brandController->update($brandData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Brand updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/brands', $redirectResponse->getTargetUrl());
});

test('It calls the exportBrands method and returns a proper response', function (): void {
    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'desc',
    ];

    $brandQueries = $this->mock(BrandQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getBrandsExport')
            ->once()
            ->with($requestParameter)
            ->andReturn(new Collection([]));
    });

    $brandController = new BrandController($brandQueries);

    $response = $brandController->exportBrands('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the syncData method and returns proper response',
    function (): void {
        Queue::fake();
        setCompanyIdInSession();

        $superAdmin = SuperAdmin::factory()->make([
            'id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): SuperAdmin => $superAdmin);

        $this->mock(SaleChannelService::class, function ($mock) use ($superAdmin): void {
            $mock->shouldReceive('updateSyncData')
                ->once()
                ->with(1, SyncTypes::BRAND->value, $superAdmin, null);
        });

        $brandController = new BrandController(new BrandQueries());
        $brandController->syncData(1, $request);

        Queue::assertPushed(BrandSyncMainJob::class);
    }
);
