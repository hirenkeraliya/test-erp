<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Services;

use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use Illuminate\Support\Collection;

class HoldSaleReturnService
{
    public CheckHoldSaleDetailsService $checkHoldSaleDetailsService;

    public Collection $saleReturnMismatches;

    public Collection $returnItems;

    public Collection $returnedSaleItems;

    public Collection $saleReturnReasons;

    public Collection $batches;

    public function setDetails(CheckHoldSaleDetailsService $checkHoldSaleDetailsService): void
    {
        $this->checkHoldSaleDetailsService = $checkHoldSaleDetailsService;
        $this->returnItems = collect($checkHoldSaleDetailsService->holdSaleData->return_items);

        $this->returnedSaleItems = $this->getReturnedSaleItems(
            $this->returnItems->pluck('sale_item_id')->unique()->toArray()
        );

        $returnReasonIds = $this->getReturnReasonIds();
        $this->saleReturnReasons = $this->getSaleReturnReasons($returnReasonIds);
    }

    public function getReturnedSaleItems(array $saleItemIds): Collection
    {
        if ($this->hasReturnItems()) {
            $saleItemQueries = resolve(SaleItemQueries::class);

            return $saleItemQueries->getByIds($saleItemIds);
        }

        return collect([]);
    }

    /**
     * @return mixed[]
     */
    public function getReturnReasonIds(): array
    {
        return $this->returnItems->pluck('sale_return_details')
            ->collapse()
            ->pluck('sale_return_reason_id')
            ->unique()
            ->toArray();
    }

    public function getSaleReturnReasons(array $saleReturnReasonIds): Collection
    {
        if ($this->hasReturnItems()) {
            $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);

            return $saleReturnReasonQueries->getByIdsAndCompanyId(
                $saleReturnReasonIds,
                $this->checkHoldSaleDetailsService->companyId
            );
        }

        return collect([]);
    }

    public function checkReturnItems(): void
    {
        $returnReasonIds = $this->getReturnReasonIds();

        if (
            $this->saleReturnReasons->count()
            !== count($returnReasonIds)
        ) {
            abort(412, 'Some of the sale return reasons are not available in our records.');
        }
    }

    public function hasReturnItems(): bool
    {
        return $this->returnItems->isNotEmpty();
    }
}
