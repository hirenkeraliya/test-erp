<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughReportTypes;
use App\Domains\SellThroughAggregate\Services\SellThroughProductForCustomReportServices;
use App\Models\Company;
use App\Models\Currency;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'it calls sellThroughSalesAndReturnsDataByProductUpcForCustomReport method of the ProductQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now()->format('Y-m-d');

        $filterData = [
            'product_id' => null,
            'product_collection_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'size_id' => null,
            'color_ids' => null,
            'department_ids' => null,
            'tag_ids' => null,
            'article_numbers' => null,
            'style_ids' => null,
            'accumulated_sale_through_include_types' => null,
            'includes_by_goods_receive_note_in_location_ids' => null,
            'includes_by_goods_receive_note_out_location_ids' => null,
            'includes_by_stock_adjustment_in_location_ids' => null,
            'includes_by_stock_adjustment_out_location_ids' => null,
            'includes_by_stock_transfer_in_location_ids' => null,
            'includes_by_stock_transfer_out_location_ids' => null,
            'includes_by_delivery_order_in_location_ids' => null,
            'includes_by_delivery_order_out_location_ids' => null,
            'date' => $date,
            'location_ids' => null,
            'report_type' => SellThroughReportTypes::BY_PRODUCT->value,
            'filter_by' => null,
        ];

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('accumulatedSaleThroughSalesAndReturnsDataByProductUpcForCustomReport')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn(Company::factory()->make([
                    'default_country_id' => 1,
                ]));
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
        });

        $sellThroughProductUpcServices = new SellThroughProductForCustomReportServices();
        $response = $sellThroughProductUpcServices->export($filterData, $companyId, 'text.csv');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }
);
