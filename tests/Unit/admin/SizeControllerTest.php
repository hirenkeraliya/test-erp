<?php

declare(strict_types=1);

use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\Size\DataObjects\SizeData;
use App\Domains\Size\Jobs\SizeSyncMainJob;
use App\Domains\Size\SizeQueries;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\Admin\SizeController;
use App\Models\Admin;
use App\Models\Size;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the size queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'group_ids' => null,
    ];

    $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $sizeController = new SizeController($sizeQueries);

    $response = $sizeController->fetchSizes(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the add size method of size queries class', function (): void {
    $sizeData = new SizeData('LMNO', 'LMNO', 1);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $sizes = Size::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'sort_order' => 1,
    ]);

    $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($sizeData, $companyId, $sizes): void {
        $mock->shouldReceive('getAllSizes')
            ->once()
            ->andReturn(collect([$sizes]));
        $mock->shouldReceive('addNew')
            ->once()
            ->with($sizeData, $companyId);
    });

    $sizeController = new SizeController($sizeQueries);
    $redirectResponse = $sizeController->store($sizeData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Size added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sizes', $redirectResponse->getTargetUrl());
});

test('It calls the addNew method of SizeQueries with valid data and returns a response', function (): void {
    $sizeData = new SizeData('LMNO', 'LMNO', 1);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $sizes = Size::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'sort_order' => 1,
    ]);

    $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($sizeData, $companyId, $sizes): void {
        $mock->shouldReceive('getAllSizes')
            ->once()
            ->andReturn(collect([$sizes]));
        $mock->shouldReceive('addNew')
            ->once()
            ->with($sizeData, $companyId);
    });

    $sizeController = new SizeController($sizeQueries);
    $response = $sizeController->storeAndReturn($sizeData);
    $this->assertArrayHasKey('size', $response);
});

test(
    'It calls the get by id method of the size queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'STUV',
            'code' => 'STUV',
        ];

        $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getAllSizes')
                ->once()
                ->andReturn(new Collection());
            $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Size($requestParameter));
        });

        $this->mock(SizeGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getSizeGroupByCompanyId')
                ->once()
                ->andReturn(collect([]));
        });

        $sizeController = new SizeController($sizeQueries);
        $response = $sizeController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'size',
            fn (Assert $size): Assert => $size->where('name', 'STUV')->where('code', 'STUV')->where(
                'company_id',
                $companyId
            )
        )
        );
    }
);

test('It calls the update size method of size queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $sizeData = new SizeData('STUV', 'STUV', 1);

    $sizes = Size::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'sort_order' => 1,
    ]);

    $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($sizeData, $companyId, $sizes): void {
        $mock->shouldReceive('getAllSizes')
            ->once()
            ->andReturn(collect([$sizes]));
        $mock->shouldReceive('update')
            ->once()
            ->with($sizeData, 1, $companyId);
    });

    $sizeController = new SizeController($sizeQueries);
    $redirectResponse = $sizeController->update($sizeData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Size updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sizes', $redirectResponse->getTargetUrl());
});

test('It calls the exportSizes method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'group_ids' => null,
    ];

    $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getSizesExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Size()));
    });

    $sizeController = new SizeController($sizeQueries);

    $response = $sizeController->exportSizes('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the getSizeSalesSummary method of the SizeQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $sizeQueries = $this->mock(SizeQueries::class, function ($mock): void {
            $mock->shouldReceive('getSizeSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $sizeController = new SizeController($sizeQueries);
        $redirectResponse = $sizeController->getSizeSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['sizes', 'total_sales', 'total_units_sold']);
    }
);

test(
    'It calls the syncData method and returns proper response',
    function (): void {
        Queue::fake();
        setCompanyIdInSession();

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->mock(SaleChannelService::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('updateSyncData')
                ->once()
                ->with(1, SyncTypes::SIZE->value, $admin, 1);
        });

        $sizeController = new SizeController(new SizeQueries());
        $sizeController->syncData(1, $request);

        Queue::assertPushed(SizeSyncMainJob::class);
    }
);
