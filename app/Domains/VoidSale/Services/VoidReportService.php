<?php

declare(strict_types=1);

namespace App\Domains\VoidSale\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Exports\VoidReportByReceiptExport;
use App\Domains\Sale\SaleQueries;
use App\Domains\VoidSale\Enums\VoidFilterTypes;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoidReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        return $this->renderPreparedVoidReport($filterData, $company, $locations);
    }

    public function renderPreparedVoidReport(array $filterData, Company $company, Collection $locations): string
    {
        [$locationsSales, $columns] = $this->preparedVoidReport($filterData, $locations, $company->id);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.void_report_by_receipt', [
            'locationsSales' => $locationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
        ])->render();
    }

    public function exportVoidSaleReport(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $saleCollections = [];
        $columns = [];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        [$saleCollections, $columns] = $this->preparedVoidReport($filterData, $locations, $companyId);

        return Excel::download(new VoidReportByReceiptExport($saleCollections, $columns), $filename);
    }

    /**
     * @return array<int, mixed[]>
     */
    private function preparedVoidReport(array $filterData, Collection $locations, int $companyId): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $voidSaleNumberPrefix = $companyQueries->getVoidSaleNumberPrefix($companyId);

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getByStoreIdForSalesVoidReportExport($filterData);

        $locationsSales = [];
        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales' => [],
            ];
            foreach ($sales->sortBy('happened_at') as $sale) {
                $saleDetails = [];
                foreach ($sale->saleItems as $saleItem) {
                    $saleDetails['products'][] = [
                        'product_upc' => $saleItem->product->upc,
                        'product_name' => $saleItem->product->name,
                        'total' => ($saleItem->price_paid_per_unit * $saleItem->quantity),
                        'promoters' => $saleItem->promoters->isEmpty() ? null : $this->getPromoters($saleItem)->implode(
                            'first_name',
                            ','
                        ),
                    ];
                }

                $happenedAt = '';
                if ($sale->happened_at) {
                    /** @var Carbon $happenedAtFormat */
                    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
                    $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
                }

                $locationSales['sales'][] = array_merge([
                    'receipt_no' => $sale->offline_sale_id,
                    'receipt_date' => $happenedAt,
                    'void_reason' => $sale->voidSale->voidSaleReason->reason,
                    'voided_by' => $sale->voidSale->voidedByStoreManager->employee->getFullName(),
                    'void_sale_number' => $voidSaleNumberPrefix . $sale->voidSale->void_sale_number,
                ], $saleDetails);
            }

            $locationsSales[] = $locationSales;
        }

        $columns = [
            'Receipt Date',
            'Receipt No',
            'Product Upc',
            'Product Name',
            'Total',
            'Void Reason',
            'Approved By',
            'Void Sale Number',
            'Promoter',
        ];

        return [$locationsSales, $columns];
    }

    private function getPromoters(SaleItem $saleItem): Collection
    {
        return $saleItem->promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'first_name' => $employee->first_name,
            ];
        });
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === VoidFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                VoidFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return VoidFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
