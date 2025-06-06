<?php

declare(strict_types=1);

use App\Domains\Style\DataObjects\StyleData;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Admin\StyleController;
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the style queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $styleController = new StyleController($styleQueries);

    $response = $styleController->fetchStyles(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the style queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $styleDetails = Style::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($styleDetails['company_id']);

    $styleRecord = new StyleData(...$styleDetails);

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($styleRecord, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($styleRecord, $companyId);
    });

    $styleController = new StyleController($styleQueries);
    $redirectResponse = $styleController->store($styleRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Style added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/styles', $redirectResponse->getTargetUrl());
});

test('It calls the addNew method of StyleQueries with valid data and returns a response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $styleDetails = Style::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($styleDetails['company_id']);

    $styleRecord = new StyleData(...$styleDetails);

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($styleRecord, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($styleRecord, $companyId);
    });

    $styleController = new StyleController($styleQueries);
    $response = $styleController->storeAndReturn($styleRecord);

    $this->assertArrayHasKey('style', $response);
});

test('It calls get by id method of the style queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = Style::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Style($requestParameter));
    });

    $styleController = new StyleController($styleQueries);
    $response = $styleController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'style',
            fn (Assert $style): Assert => $style
                ->where('name', $requestParameter['name'])
                ->where('code', $requestParameter['code'])
                ->etc()
        )
    );
});

test('It calls update method of the style queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $styleDetails = Style::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($styleDetails['company_id']);

    $styleRecord = new StyleData(...$styleDetails);

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($styleRecord, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($styleRecord, 1, $companyId);
    });

    $styleController = new StyleController($styleQueries);
    $redirectResponse = $styleController->update($styleRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Style updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/styles', $redirectResponse->getTargetUrl());
});

test('It calls the exportStyles method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getStylesExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Style()));
    });

    $styleController = new StyleController($styleQueries);

    $response = $styleController->exportStyles('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the getFilteredStylesByCompanyId method and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
    ];

    $style = Style::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($style): void {
        $mock->shouldReceive('getFilteredStylesByCompanyId')
            ->once()
            ->andReturn(collect([$style]));
    });

    $styleController = new StyleController($styleQueries);

    $response = $styleController->getFilteredStyles(new Request($requestParameter));
    expect($response['styles']->first()->toArray())
        ->toHaveKey('id', $style->id)
        ->toHaveKey('name', $style->name);
});

test(
    'It calls the getStyleSalesSummary method of the StyleQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $styleQueries = $this->mock(StyleQueries::class, function ($mock): void {
            $mock->shouldReceive('getStyleSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $styleController = new StyleController($styleQueries);
        $redirectResponse = $styleController->getStyleSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['styles', 'total_sales', 'total_units_sold']);
    }
);
