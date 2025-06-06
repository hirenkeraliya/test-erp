<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\SaleTargetPromoterTypes;
use App\Domains\SaleTarget\Enums\SaleTargetStoreTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Jobs\SaleAchievedTargetJob;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTarget\Services\SaleTargetService;
use App\Http\Controllers\Admin\SaleTargetController;
use App\Models\Currency;
use App\Models\SaleTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the sale target queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'target_type' => null,
            'time_interval_type' => null,
            'select_status' => null,
            'location_ids' => null,
            'promoter_ids' => null,
        ];

        $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock) use ($requestParameter): void {
            setCompanyIdInSession(1);
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleTargetController = new SaleTargetController($saleTargetQueries);

        $response = $saleTargetController->fetchSaleTargets(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls addNew method of the sale target queries class', function (): void {
    Queue::fake();

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $saleTargetRecord = new SaleTargetData(
        'abc',
        12,
        null,
        SaleTargetAmountTypes::AMOUNT->value,
        TargetType::COMPANY_WISE->value,
        TimeIntervalType::DAILY->value,
        true,
        [],
        [],
        [],
        SaleTargetStoreTypes::SELECT->value,
        SaleTargetPromoterTypes::SELECT->value,
        null,
        null
    );

    $saleTarget = SaleTarget::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
    ]);

    $this->mock(SaleTargetService::class, function ($mock) use ($saleTargetRecord, $companyId, $saleTarget): void {
        $mock->shouldReceive('addSaleTarget')
            ->once()
            ->with($saleTargetRecord, $companyId)
            ->andReturn($saleTarget);
    });

    $saleTargetController = new SaleTargetController(new SaleTargetQueries());
    $redirectResponse = $saleTargetController->store($saleTargetRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Sale Targets added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sale-targets', $redirectResponse->getTargetUrl());

    Queue::assertPushed(SaleAchievedTargetJob::class);
});

test('It calls update method of the employee group queries class', function (): void {
    Cache::spy();
    Queue::fake();

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $saleTargetRecord = new SaleTargetData(
        'abc',
        12,
        null,
        SaleTargetAmountTypes::AMOUNT->value,
        TargetType::COMPANY_WISE->value,
        TimeIntervalType::DAILY->value,
        true,
        [],
        [],
        [],
        SaleTargetStoreTypes::SELECT->value,
        SaleTargetPromoterTypes::SELECT->value,
        null,
        null
    );

    $this->mock(SaleTargetService::class, function ($mock) use ($saleTargetRecord, $companyId): void {
        $mock->shouldReceive('updateSaleTarget')
            ->once()
            ->with($saleTargetRecord, 1, $companyId);
    });

    $saleTargetController = new SaleTargetController(new SaleTargetQueries());
    $redirectResponse = $saleTargetController->update($saleTargetRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Sale Target updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sale-targets', $redirectResponse->getTargetUrl());

    Queue::assertPushed(SaleAchievedTargetJob::class);
});

test('It calls the exportSaleTargets method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'target_type' => null,
        'time_interval_type' => null,
        'select_status' => null,
        'location_ids' => null,
        'promoter_ids' => null,
    ];

    $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getSaleTargetExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SaleTarget()));
    });

    $saleTargetController = new SaleTargetController($saleTargetQueries);

    $response = $saleTargetController->exportSaleTargets('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('it calls the adminSetStatus method of saleTargetQueries class', function (): void {
    setCompanyIdInSession(1);

    $saleTarget = SaleTarget::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock) use ($saleTarget): void {
        $mock->shouldReceive('adminSetStatus')
            ->once()
            ->with($saleTarget->id, 1, false);
    });

    $saleTargetController = new SaleTargetController($saleTargetQueries);
    $response = $saleTargetController->setStatus($saleTarget->id, false);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sale-targets', $response->getTargetUrl());
});

test('it calls the markAsRegenerateStart method of saleTargetQueries class', function (): void {
    Queue::fake();
    setCompanyIdInSession(1);
    $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsRegenerateStart')
            ->once();
    });

    $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteSaleAchievedTargetFromSaleTarget')
            ->once();
    });

    $saleTargetController = new SaleTargetController($saleTargetQueries);
    $saleTargetController->reGenerateTarget(1);

    Queue::assertPushed(SaleAchievedTargetJob::class);
});

test('it call the fetchSaleTarget method of saleTargetQueries class and return proper message', function (): void {
    setCompanyIdInSession(1);

    $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn(new SaleTarget([
                'amount_type' => 1,
                'target_type' => 1,
                'time_interval_type' => 1,
            ]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $saleTargetController = new SaleTargetController($saleTargetQueries);
    $response = $saleTargetController->fetchSaleTarget(1);
    expect($response)->toHaveKey('sale_target_details');
});
