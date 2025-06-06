<?php

declare(strict_types=1);

namespace App\Domains\GenuineProductVerification;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Models\GenuineProductVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GenuineProductVerificationQueries
{
    public function getById(int $genuineProductVerificationId): GenuineProductVerification
    {
        return GenuineProductVerification::findOrFail($genuineProductVerificationId);
    }

    public function addNew(array $genuineProductVerificationData): GenuineProductVerification
    {
        return GenuineProductVerification::create($genuineProductVerificationData);
    }

    public function update(
        GenuineProductVerification $genuineProductVerification,
        array $genuineProductVerificationData
    ): void {
        $genuineProductVerification->remarks = $genuineProductVerificationData['remarks'];
        $genuineProductVerification->save();
    }

    public function getPaginatedProductVerificationReport(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getGenuineProductVerificationQuery($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getProductVerificationReportDataForExport(array $filterData, int $companyId): Collection
    {
        return $this->getGenuineProductVerificationQuery($filterData, $companyId)
            ->get();
    }

    private function getGenuineProductVerificationQuery(array $filterData, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = ['product:' . $productQueries->getColumnsForReservedInventoryReports()];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return GenuineProductVerification::query()
            ->select(
                'name',
                'product_id',
                'is_genuine',
                'qr_code',
                'receipt_number',
                'mobile_number',
                'email',
                'member_id',
                'sale_id',
                'remarks',
                'created_at'
            )
            ->with($relations)
            ->when('' !== $filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere('qr_code', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere('mobile_number', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere('email', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhere('receipt_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['product_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('product_id', $filterData['product_ids']);
            })
            ->when(null !== $filterData['is_genuine'], function ($query) use ($filterData): void {
                $query->where('is_genuine', (bool) $filterData['is_genuine']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('sale', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id')
                        ->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                            $query->select('id', 'counter_id')
                                ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                        });
                });
            }, function ($query) use ($companyId, $counterUpdateQueries): void {
                $query->where(function ($query) use ($companyId, $counterUpdateQueries): void {
                    $query->whereHas('sale', function ($query) use ($companyId, $counterUpdateQueries): void {
                        $query->select('id')
                            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId));
                    })
                        ->orWhereNull('sale_id');
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query
                    ->where('created_at', '>=', $filterData['date_range'][0])
                    ->where('created_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
