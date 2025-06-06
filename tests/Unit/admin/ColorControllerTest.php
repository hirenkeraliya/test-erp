<?php

declare(strict_types=1);

use App\Domains\Color\ColorQueries;
use App\Domains\Color\DataObjects\ColorData;
use App\Domains\Color\Jobs\ColorSyncMainJob;
use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\Admin\ColorController;
use App\Models\Admin;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the color queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'group_ids' => null,
    ];

    $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $colorController = new ColorController($colorQueries);

    $response = $colorController->fetchColors(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the color queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $colorData = Color::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($colorData['company_id']);

    $colorRecords = new ColorData(...$colorData);

    $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($colorRecords, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($colorRecords, $companyId);
    });

    $colorController = new ColorController($colorQueries);
    $redirectResponse = $colorController->store($colorRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The color has been added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/colors', $redirectResponse->getTargetUrl());
});

test('It calls the addNew method of ColorQueries with valid data and returns a response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $colorData = Color::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($colorData['company_id']);

    $colorRecords = new ColorData(...$colorData);

    $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($colorRecords, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($colorRecords, $companyId);
    });

    $colorController = new ColorController($colorQueries);
    $response = $colorController->storeAndReturn($colorRecords);
    $this->assertArrayHasKey('color', $response);
});

test('It calls get by id method of the color queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = Color::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Color($requestParameter));
    });

    $this->mock(ColorGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('getColorGroupByCompanyId')
            ->once()
            ->andReturn(collect([]));
    });

    $colorController = new ColorController($colorQueries);
    $response = $colorController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'color',
            fn (Assert $color): Assert => $color
                ->where('name', $requestParameter['name'])
                ->where('code', $requestParameter['code'])
                ->etc()
        )
    );
});

test('It calls update method of the color queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $colorData = Color::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($colorData['company_id']);

    $colorRecords = new ColorData(...$colorData);

    $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($colorRecords, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($colorRecords, 1, $companyId);
    });

    $colorController = new ColorController($colorQueries);
    $redirectResponse = $colorController->update($colorRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The color has been updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/colors', $redirectResponse->getTargetUrl());
});

test('It calls the exportColors method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'group_ids' => null,
    ];

    $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getColorsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Color()));
    });

    $colorController = new ColorController($colorQueries);

    $response = $colorController->exportColors('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the getColorSalesSummary method of the ColorQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $colorQueries = $this->mock(ColorQueries::class, function ($mock): void {
            $mock->shouldReceive('getColorSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $colorController = new ColorController($colorQueries);
        $redirectResponse = $colorController->getColorSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['colors', 'total_sales', 'total_units_sold']);
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
                ->with(1, SyncTypes::COLOR->value, $admin, 1);
        });

        $colorController = new ColorController(new ColorQueries());
        $colorController->syncData(1, $request);

        Queue::assertPushed(ColorSyncMainJob::class);
    }
);
