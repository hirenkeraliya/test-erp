<?php

declare(strict_types=1);

use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\SaleTargetPromoterTypes;
use App\Domains\SaleTarget\Enums\SaleTargetStoreTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTarget\Services\SaleTargetService;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\SaleTarget;

beforeEach(function (): void {
    $this->companyId = 1;

    $this->saleTargetData = new SaleTargetData(
        'test',
        10,
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

    $this->saleTarget = SaleTarget::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
    ]);

    $this->saleTargetService = new SaleTargetService();
});

test('addSaleTarget method calls addNew method of SaleTargetQueries class', function (): void {
    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($this->saleTarget);
    });

    $mock = $this->createPartialMock(
        SaleTargetService::class,
        ['addDailyTargetTimeFrame', 'addYearTargetTimeFrame', 'addMonthsTargetTimeFrame', 'addWeeksTargetTimeFrame']
    );

    $mock->expects($this->once())
        ->method('addDailyTargetTimeFrame');

    $mock->expects($this->once())
        ->method('addYearTargetTimeFrame');

    $mock->expects($this->once())
        ->method('addMonthsTargetTimeFrame');

    $mock->expects($this->once())
        ->method('addWeeksTargetTimeFrame');

    $saleTarget = $mock->addSaleTarget($this->saleTargetData, $this->companyId);
    $this->assertEquals($saleTarget, $this->saleTarget);
});

test('updateSaleTarget method calls update method of SaleTargetQueries class', function (): void {
    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($this->saleTarget);

        $mock->shouldReceive('update')
            ->once();
    });

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteBySaleTarget')
            ->once()
            ->andReturn($this->saleTarget);
    });

    $this->mock(SaleAchievedTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteBySaleTarget')
            ->once()
            ->andReturn($this->saleTarget->id);
    });

    $mock = $this->createPartialMock(
        SaleTargetService::class,
        ['addDailyTargetTimeFrame', 'addYearTargetTimeFrame', 'addMonthsTargetTimeFrame', 'addWeeksTargetTimeFrame']
    );

    $mock->expects($this->once())
        ->method('addDailyTargetTimeFrame');

    $mock->expects($this->once())
        ->method('addYearTargetTimeFrame');

    $mock->expects($this->once())
        ->method('addMonthsTargetTimeFrame');

    $mock->expects($this->once())
        ->method('addWeeksTargetTimeFrame');

    $mock->updateSaleTarget($this->saleTargetData, $this->companyId, $this->saleTarget->id);
});

test('addDailyTargetTimeFrame method return null when date not set', function (): void {
    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
    });

    $response = $this->saleTargetService->addDailyTargetTimeFrame($this->saleTargetData, $this->saleTarget);

    $this->assertNull($response);
});

test('addDailyTargetTimeFrame method calls addNew method of SaleTargetTimeframeQueries class', function (): void {
    $this->saleTargetData->dates = [now()->format('Y-m-d'), now()->format('Y-m-d')];
    $this->saleTargetData->target_label = TimeIntervalType::DAILY->value;

    $saleTargetTimeframeRecord = [
        'sale_target_id' => 1,
        'target_label' => TimeIntervalType::getFormattedCaseName($this->saleTargetData->time_interval_type),
        'start_date' => $this->saleTargetData->dates[0],
        'end_date' => $this->saleTargetData->dates[1],
        'amount' => $this->saleTargetData->amount,
    ];

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock) use ($saleTargetTimeframeRecord): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($saleTargetTimeframeRecord);
    });

    $this->saleTargetService->addDailyTargetTimeFrame($this->saleTargetData, $this->saleTarget);
});

test('addYearTargetTimeFrame method return null when date not set', function (): void {
    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
    });

    $response = $this->saleTargetService->addYearTargetTimeFrame($this->saleTargetData, $this->saleTarget);

    $this->assertNull($response);
});

test('addYearTargetTimeFrame method calls addNew method of SaleTargetTimeframeQueries class', function (): void {
    $this->saleTargetData->year = (int) now()->format('Y');
    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->saleTargetService->addYearTargetTimeFrame($this->saleTargetData, $this->saleTarget);
});

test('addMonthsTargetTimeFrame method return null when month_tiers set null', function (): void {
    $this->saleTargetData->month_tiers = null;

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
    });

    $response = $this->saleTargetService->addMonthsTargetTimeFrame($this->saleTargetData, $this->saleTarget);

    $this->assertNull($response);
});

test('addMonthsTargetTimeFrame method return null when date not set', function (): void {
    $this->saleTargetData->month_tiers = [];

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
    });

    $response = $this->saleTargetService->addMonthsTargetTimeFrame($this->saleTargetData, $this->saleTarget);

    $this->assertNull($response);
});

test('addMonthsTargetTimeFrame method calls addNew method of SaleTargetTimeframeQueries class', function (): void {
    $this->saleTargetData->month_tiers[0]['months'] = [
        'month' => 0o1,
        'year' => 2023,
    ];

    $this->saleTargetData->month_tiers[0]['amount'] = $this->saleTargetData->amount;

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->saleTargetService->addMonthsTargetTimeFrame($this->saleTargetData, $this->saleTarget);
});

test('addWeeksTargetTimeFrame method return null when week_tiers set null', function (): void {
    $this->saleTargetData->week_tiers = null;

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
    });

    $response = $this->saleTargetService->addWeeksTargetTimeFrame($this->saleTargetData, $this->saleTarget);

    $this->assertNull($response);
});

test('addWeeksTargetTimeFrame method return null when date not set', function (): void {
    $this->saleTargetData->week_tiers = [];

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
    });

    $response = $this->saleTargetService->addWeeksTargetTimeFrame($this->saleTargetData, $this->saleTarget);

    $this->assertNull($response);
});

test('addWeeksTargetTimeFrame method calls addNew method of SaleTargetTimeframeQueries class', function (): void {
    $this->saleTargetData->week_tiers[0]['weeks'] = [now()->format('Y-m-d'), now()->format('Y-m-d')];
    $this->saleTargetData->week_tiers[0]['amount'] = $this->saleTargetData->amount;

    $this->mock(SaleTargetTimeframeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->saleTargetService->addWeeksTargetTimeFrame($this->saleTargetData, $this->saleTarget);
});

test('getFirstAndLastDateOfYear method return first and last date of year', function (): void {
    [$startDate, $endDate] = $this->saleTargetService->getFirstAndLastDateOfYear((int) now()->format('Y'));

    $this->assertEquals($startDate, now()->startOfYear()->format('Y-m-d'));
    $this->assertEquals($endDate, now()->endOfYear()->format('Y-m-d'));
});

test('getFirstAndLastDateOfMonth method return first and last date of month', function (): void {
    [$startDate, $endDate, $monthName] = $this->saleTargetService->getFirstAndLastDateOfMonth(
        (int) (now()->format('m') - 1),
        (int) now()->format('Y')
    );

    $this->assertEquals($startDate, now()->startOfMonth()->format('Y-m-d'));
    $this->assertEquals($endDate, now()->endOfMonth()->format('Y-m-d'));
    $this->assertEquals($monthName, now()->format('F'));
});

test('getWeekNumbers method return week of month', function ($startDate, $week): void {
    $response = $this->saleTargetService->getWeekNumbers($startDate);
    $this->assertEquals($response, $week);
})->with([
    ['2024-01-03', 'Week-1'],
    ['2024-01-10', 'Week-2'],
    ['2024-01-17', 'Week-3'],
    ['2024-01-24', 'Week-4'],
    ['2024-01-31', 'Week-5'],
    ['2024-02-04', 'Week-1'],
    ['2024-04-07', 'Week-1'],
]);
