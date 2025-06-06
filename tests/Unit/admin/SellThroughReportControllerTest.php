<?php

declare(strict_types=1);

use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Services\SellThroughByBrandServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByColorServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByDepartmentServices;
use App\Domains\SellThroughAggregate\Services\SellThroughBySizeServices;
use App\Domains\SellThroughAggregate\Services\SellThroughByStyleServices;
use App\Domains\SellThroughAggregate\Services\SellThroughCategoryServices;
use App\Domains\SellThroughAggregate\Services\SellThroughLocationServices;
use App\Domains\SellThroughAggregate\Services\SellThroughProductArticleNumberServices;
use App\Domains\SellThroughAggregate\Services\SellThroughProductUpcServices;
use App\Domains\SellThroughAggregate\Services\SellThroughSummaryServices;
use App\Http\Controllers\Admin\SellThroughAggregateReportController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'it fetches Sell Through details by size or color or style or article number or upc or store or department or brand or category as expected',
    function (int $sellThroughType): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'date' => null,
            'location_id' => null,
            'report_type' => $sellThroughType,
        ];

        if ($sellThroughType === SellThroughTypes::SIZES->value) {
            $this->mock(SellThroughBySizeServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsBySize')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::COLORS->value) {
            $this->mock(SellThroughByColorServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByColor')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::STYLES->value) {
            $this->mock(SellThroughByStyleServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByStyle')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $this->mock(SellThroughProductArticleNumberServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByProductArticleNumber')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::BY_UPC->value) {
            $this->mock(SellThroughProductUpcServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByProductUpc')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::LOCATIONS->value) {
            $this->mock(SellThroughLocationServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByStore')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::DEPARTMENTS->value) {
            $this->mock(SellThroughByDepartmentServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByDepartment')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::BRANDS->value) {
            $this->mock(SellThroughByBrandServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByBrand')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        if ($sellThroughType === SellThroughTypes::CATEGORIES->value) {
            $this->mock(SellThroughCategoryServices::class, function ($mock): void {
                $mock->shouldReceive('fetchSellThroughDetailsByCategory')
                ->once()
                ->andReturn([
                    'data' => [],
                    'total' => [],
                ]);
            });
        }

        $SellThroughController = new SellThroughAggregateReportController();
        $response = $SellThroughController->fetchSellThroughDetails(new Request($filterData));

        expect($response)->toBe([
            'data' => [],
            'total' => [],
        ]);
    }
)->with([[SellThroughTypes::SIZES->value], [SellThroughTypes::COLORS->value],
    [SellThroughTypes::STYLES->value], [SellThroughTypes::BY_MASTER_PRODUCT->value],
    [SellThroughTypes::BY_UPC->value], [SellThroughTypes::LOCATIONS->value],
    [SellThroughTypes::DEPARTMENTS->value], [SellThroughTypes::BRANDS->value],
    [SellThroughTypes::CATEGORIES->value], ]);

test(
    'it prints Accumulated Sell Through details by size or color or style or article number or upc or store or department or brand or category or summary as expected',
    function (int $sellThroughType): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'date' => null,
            'location_id' => null,
            'report_type' => $sellThroughType,
        ];

        if ($sellThroughType === SellThroughTypes::SIZES->value) {
            $this->mock(SellThroughBySizeServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsBySize')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::COLORS->value) {
            $this->mock(SellThroughByColorServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByColor')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::STYLES->value) {
            $this->mock(SellThroughByStyleServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByStyle')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $this->mock(SellThroughProductArticleNumberServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByProductArticleNumber')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::BY_UPC->value) {
            $this->mock(SellThroughProductUpcServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByProductUpc')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::LOCATIONS->value) {
            $this->mock(SellThroughLocationServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByStore')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::DEPARTMENTS->value) {
            $this->mock(SellThroughByDepartmentServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByDepartment')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::BRANDS->value) {
            $this->mock(SellThroughByBrandServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByBrand')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::CATEGORIES->value) {
            $this->mock(SellThroughCategoryServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetailsByCategory')
                ->once()
                ->andReturn('');
            });
        }

        if ($sellThroughType === SellThroughTypes::SUMMARY->value) {
            $this->mock(SellThroughSummaryServices::class, function ($mock): void {
                $mock->shouldReceive('printSellThroughDetails')
                ->once()
                ->andReturn('');
            });
        }

        $sellThroughAggregateReportController = new SellThroughAggregateReportController();
        $response = $sellThroughAggregateReportController->printSellThroughAggregateDetails(new Request($filterData));

        expect($response)->toBe('');
    }
)->with([[SellThroughTypes::SIZES->value], [SellThroughTypes::COLORS->value],
    [SellThroughTypes::STYLES->value], [SellThroughTypes::BY_MASTER_PRODUCT->value],
    [SellThroughTypes::BY_UPC->value], [SellThroughTypes::LOCATIONS->value],
    [SellThroughTypes::DEPARTMENTS->value], [SellThroughTypes::BRANDS->value],
    [SellThroughTypes::CATEGORIES->value], [SellThroughTypes::SUMMARY->value], ]);

test(
    'it calls the service class to fetch Accumulated Sell Through details for the chart',
    function (int $sellThroughType): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'date' => null,
            'location_id' => null,
            'report_type' => $sellThroughType,
        ];

        if ($sellThroughType === SellThroughTypes::SIZES->value) {
            $this->mock(SellThroughBySizeServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsBySizeForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::COLORS->value) {
            $this->mock(SellThroughByColorServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByColorForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::STYLES->value) {
            $this->mock(SellThroughByStyleServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByStyleForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $this->mock(SellThroughProductArticleNumberServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByProductArticleNumberForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::BY_UPC->value) {
            $this->mock(SellThroughProductUpcServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByProductUpcForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::LOCATIONS->value) {
            $this->mock(SellThroughLocationServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByStoreForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::DEPARTMENTS->value) {
            $this->mock(SellThroughByDepartmentServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByDepartmentForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::BRANDS->value) {
            $this->mock(SellThroughByBrandServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByBrandForChart')
                    ->once();
            });
        }

        if ($sellThroughType === SellThroughTypes::CATEGORIES->value) {
            $this->mock(SellThroughCategoryServices::class, function ($mock): void {
                $mock->shouldReceive('sellThroughDetailsByCategoryForChart')
                    ->once();
            });
        }

        $sellThroughAggregateReportController = new SellThroughAggregateReportController();
        $response = $sellThroughAggregateReportController->fetchSellThroughDetailsForChart(new Request($filterData));

        expect($response)->toBe([]);
    }
)->with([[SellThroughTypes::SIZES->value], [SellThroughTypes::COLORS->value],
    [SellThroughTypes::STYLES->value], [SellThroughTypes::BY_MASTER_PRODUCT->value],
    [SellThroughTypes::BY_UPC->value], [SellThroughTypes::LOCATIONS->value],
    [SellThroughTypes::DEPARTMENTS->value], [SellThroughTypes::BRANDS->value],
    [SellThroughTypes::CATEGORIES->value], ]);

test(
    'it exports sale through details by size or color or style or article number or upc or store or department or brand or category as expected',
    function (int $saleThroughType): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'date' => null,
            'location_id' => null,
            'report_type' => $saleThroughType,
        ];

        if ($saleThroughType === SellThroughTypes::SIZES->value) {
            $this->mock(SellThroughBySizeServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsBySize')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::COLORS->value) {
            $this->mock(SellThroughByColorServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByColor')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::STYLES->value) {
            $this->mock(SellThroughByStyleServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByStyle')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::BY_MASTER_PRODUCT->value) {
            $this->mock(SellThroughProductArticleNumberServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByProductArticleNumber')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::BY_UPC->value) {
            $this->mock(SellThroughProductUpcServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByProductUpc')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::LOCATIONS->value) {
            $this->mock(SellThroughLocationServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByStore')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::DEPARTMENTS->value) {
            $this->mock(SellThroughByDepartmentServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByDepartment')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::BRANDS->value) {
            $this->mock(SellThroughByBrandServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByBrand')
                ->once();
            });
        }

        if ($saleThroughType === SellThroughTypes::CATEGORIES->value) {
            $this->mock(SellThroughCategoryServices::class, function ($mock): void {
                $mock->shouldReceive('exportSellThroughDetailsByCategory')
                ->once();
            });
        }

        $sellThroughAggregateReportController = new SellThroughAggregateReportController();
        $response = $sellThroughAggregateReportController->exportSellThroughAggregateDetails(
            new Request($filterData),
            'abc.csv'
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
)->with([[SellThroughTypes::SIZES->value], [SellThroughTypes::COLORS->value],
    [SellThroughTypes::STYLES->value], [SellThroughTypes::BY_MASTER_PRODUCT->value],
    [SellThroughTypes::BY_UPC->value], [SellThroughTypes::LOCATIONS->value],
    [SellThroughTypes::DEPARTMENTS->value], [SellThroughTypes::BRANDS->value],
    [SellThroughTypes::CATEGORIES->value], ]);
