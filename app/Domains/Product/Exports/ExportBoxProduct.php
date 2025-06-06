<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Models\BoxProduct;
use App\Models\ExportRecord;
use App\Models\PackageType;
use App\Models\Product;
use Illuminate\Support\Collection;

class ExportBoxProduct implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        $filters = $exportRecord->filters ?? [];
        unset($filters['all_permission_lists']);

        $boxProductQueries = resolve(BoxProductQueries::class);

        $boxProducts = $boxProductQueries->exportBoxProductRecords(
            $filters,
            $exportRecord->company_id,
            $insertedRows,
            $nextRecords
        );

        return $this->preparedBoxProductRecords($boxProducts);
    }

    public function preparedBoxProductRecords(Collection $boxProducts): Collection
    {
        return $boxProducts->map(function (BoxProduct $boxProduct): array {
            /** @var Product $product */
            $product = $boxProduct->product;

            /** @var PackageType $packageType */
            $packageType = $boxProduct->packageType;

            return [
                'upc' => $product->upc,
                'package_type_name' => $packageType->name,
                'units' => $boxProduct->units,
                'retail_price' => $boxProduct->retail_price,
                'staff_price' => $boxProduct->staff_price,
            ];
        });
    }
}
