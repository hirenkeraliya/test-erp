<?php

declare(strict_types=1);

namespace App\Domains\Region\Jobs;

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Region\Mail\SendDailySalesSummaryToRegionManagerMail;
use App\Domains\Region\RegionQueries;
use App\Domains\Sale\SaleQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DailyTotalSalesMailToRegionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $regionId,
        private readonly string $fromDate,
        private readonly string $toDate,
    ) {
    }

    public function handle(): void
    {
        Log::channel('daily_total_sales_job')->info('daily_total_sales_job', [
            'The job for daily sales has started.',
        ]);

        $saleQueries = resolve(SaleQueries::class);
        $regionQueries = resolve(RegionQueries::class);
        $region = $regionQueries->getRegionByIdWithStoresAndBrands($this->regionId);

        $regionWiseGroup = collect();
        $locationWiseAndBrandWiseGroup = [];
        $regionWiseGroup = $saleQueries->getSalesByRegionId($region->id, $this->fromDate, $this->toDate);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($region->company_id);

        $regionWiseGroup->groupBy('location_id')->each(function ($group) use (
            &$locationWiseAndBrandWiseGroup
        ): void {
            $locationWiseAndBrandWiseGroup['locations'][] = [
                'location_name' => $group[0]->location_name,
                'total_sales_count' => $group->sum('total_sales_count'),
                'total_units_sold' => $group->sum('total_units_sold'),
                'total_sales_amount' => $group->sum('total_sales_amount'),
            ];
        });

        $regionWiseGroup->groupBy('brand_id')->each(function ($group) use (&$locationWiseAndBrandWiseGroup): void {
            $locationWiseAndBrandWiseGroup['brands'][] = [
                'brand_name' => $group[0]->brand_name,
                'total_sales_count' => $group->sum('total_sales_count'),
                'total_units_sold' => $group->sum('total_units_sold'),
                'total_sales_amount' => $group->sum('total_sales_amount'),
            ];
        });

        $preparedData = $this->prepareData($locationWiseAndBrandWiseGroup, $currency->getSymbol());

        try {
            Mail::to($region->manager_email)
                ->send(new SendDailySalesSummaryToRegionManagerMail($region, $preparedData));
        } catch (Throwable $throwable) {
            Log::error('Daily Sales Mail To Region Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('daily_total_sales_job')->info('daily_total_sales_job', [
            'The job for daily sales has finished.',
        ]);
    }

    private function prepareStoreColumns(): array
    {
        return ['Location Name', 'Orders', 'Units Sold', 'Sales'];
    }

    private function prepareBrandColumns(): array
    {
        return ['Brand Name', 'Orders', 'Units Sold', 'Sales'];
    }

    private function prepareData(array $locationWiseAndBrandWiseGroup, string $currencySymbol): array
    {
        return [
            'date' => now()->subDay()->startOfDay()->format('d F Y'),
            'locationColumns' => $this->prepareStoreColumns(),
            'brandColumns' => $this->prepareBrandColumns(),
            'locationAndBrandWiseGroup' => $locationWiseAndBrandWiseGroup,
            'currency_symbol' => $currencySymbol,
        ];
    }
}
