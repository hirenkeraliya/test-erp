<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Models\ExportRecord;
use App\Models\Membership;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use Illuminate\Support\Collection;

class ExportLoyaltyPointProduct implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        $filters = $exportRecord->filters ?? [];
        unset($filters['all_permission_lists']);

        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);

        $loyaltyPointProducts = $productLoyaltyPointQueries->exportLoyaltyPointProductRecords(
            $filters,
            $exportRecord->company_id,
            $insertedRows,
            $nextRecords
        );

        return $this->preparedLoyaltyPointProductRecords($loyaltyPointProducts);
    }

    public function preparedLoyaltyPointProductRecords(Collection $loyaltyPointProducts): Collection
    {
        return $loyaltyPointProducts->map(function (ProductLoyaltyPoint $productLoyaltyPoint): array {
            /** @var Product $product */
            $product = $productLoyaltyPoint->product;

            /** @var Membership $membership */
            $membership = $productLoyaltyPoint->membership;

            return [
                'upc' => $product->upc,
                'membership' => $membership->name,
                'loyalty_points' => $productLoyaltyPoint->points,
            ];
        });
    }
}
