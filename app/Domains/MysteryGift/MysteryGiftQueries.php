<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MysteryGift\DataObjects\MysteryGiftData;
use App\Domains\MysteryGift\Enums\Statuses;
use App\Domains\MysteryGiftProduct\MysteryGiftProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Models\MysteryGift;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class MysteryGiftQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->mysteryGiftQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(MysteryGiftData $mysteryGiftData, int $companyId): MysteryGift
    {
        $mysteryGiftDetails = $mysteryGiftData->toArray();
        $mysteryGiftDetails['company_id'] = $companyId;

        unset($mysteryGiftDetails['uploaded_products']);
        $mysteryGift = MysteryGift::create($mysteryGiftDetails);
        $this->updateMysteryGiftProductRelationDetails($mysteryGift, $mysteryGiftData->toArray());

        return $mysteryGift;
    }

    public function update(MysteryGiftData $mysteryGiftData, MysteryGift $mysteryGift): MysteryGift
    {
        $data = $mysteryGiftData->toArray();
        unset($data['uploaded_products']);

        $mysteryGift->update($data);
        $mysteryGift->refresh();

        $this->updateMysteryGiftProductRelationDetails($mysteryGift, $mysteryGiftData->toArray());

        return $mysteryGift;
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getById(int $mysteryGiftId, int $companyId): MysteryGift
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $mysteryGiftProductQueries = resolve(MysteryGiftProductQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'mysteryGiftProducts:' . $mysteryGiftProductQueries->getBasicColumnNames(),
            'mysteryGiftProducts.product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge(
                $relations,
                [
                    'mysteryGiftProducts.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'mysteryGiftProducts.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'mysteryGiftProducts.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ]
            );
        } else {
            $relations = array_merge(
                $relations,
                [
                    'mysteryGiftProducts.product.color:' . $colorQueries->getBasicColumnNames(),
                    'mysteryGiftProducts.product.size:' . $sizeQueries->getBasicColumnNames(),
                ]
            );
        }

        return MysteryGift::query()
            ->select(...$this->getColumnNames())
            ->with($relations)
            ->where('company_id', $companyId)
            ->findOrFail($mysteryGiftId);
    }

    public function setStatus(int $mysteryGiftId, int $companyId, bool $status): void
    {
        $mysteryGift = MysteryGift::query()
            ->where('company_id', $companyId)
            ->findOrFail($mysteryGiftId);
        $mysteryGift->status = $status;
        $mysteryGift->save();

        if ($status) {
            $mysteryGift = MysteryGift::where('company_id', $companyId)
                ->whereNot('id', $mysteryGiftId)
                ->update([
                    'status' => Statuses::INACTIVE->value,
                ]);
        }
    }

    public function getMysteryGiftConfigurations(int $companyId): ?MysteryGift
    {
        return MysteryGift::query()
             ->where('company_id', $companyId)
             ->where('status', Statuses::ACTIVE->value)
             ->whereDate('start_date', '<=', now()->format('Y-m-d'))
             ->whereDate('end_date', '>=', now()->format('Y-m-d'))
             ->with(['promotions'])
             ->first();
    }

    public function getActiveConfigurations(?int $companyId = null): ?MysteryGift
    {
        return MysteryGift::query()
            ->select('id', 'name', 'minimum_spend', 'start_date', 'end_date')
            ->where('status', Statuses::ACTIVE->value)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->whereDate('start_date', '<=', now()->format('Y-m-d'))
            ->whereDate('end_date', '>=', now()->format('Y-m-d'))
            ->first();
    }

    public function removeSelectedProducts(array $mysteryGiftData): void
    {
        $mysteryGift = MysteryGift::select('id')->findOrFail($mysteryGiftData['id']);

        if (! $mysteryGift instanceof MysteryGift) {
            return;
        }

        $mysteryGift->mysteryGiftProducts()->delete();
    }

    public function fetchPromotionProducts(int $id): MysteryGift
    {
        $productQueries = new ProductQueries();
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $mysteryGiftProductQueries = resolve(MysteryGiftProductQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'mysteryGiftProducts:' . $mysteryGiftProductQueries->getBasicColumnNames(),
            'mysteryGiftProducts.product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge(
                $relations,
                [
                    'mysteryGiftProducts.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'mysteryGiftProducts.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'mysteryGiftProducts.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ]
            );
        } else {
            $relations = array_merge(
                $relations,
                [
                    'mysteryGiftProducts.product.color:' . $colorQueries->getBasicColumnNames(),
                    'mysteryGiftProducts.product.size:' . $sizeQueries->getBasicColumnNames(),
                ]
            );
        }

        return MysteryGift::query()
            ->select('id', 'name')
            ->with($relations)
            ->findOrFail($id);
    }

    private function mysteryGiftQuery(array $filterData, int $companyId): Builder
    {
        return MysteryGift::query()
            ->select(
                'id',
                'name',
                'company_id',
                'min_flat_amount',
                'max_flat_amount',
                'min_percentage',
                'max_percentage',
                'is_flat_amount',
                'is_percentage',
                'is_free_product',
                'start_date',
                'end_date',
                'minimum_spend',
                'minimum_spend_amount_for_free_product',
                'minimum_spend_amount_for_percentage',
                'minimum_spend_amount_for_flat_amount',
                'status',
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when(
                $filterData['sort_by'],
                function ($query) use ($filterData): void {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                },
                function ($query): void {
                    $query->orderBy('id', 'desc');
                }
            );
    }

    private function getColumnNames(): array
    {
        return [
            'id',
            'name',
            'company_id',
            'min_flat_amount',
            'max_flat_amount',
            'min_percentage',
            'max_percentage',
            'is_flat_amount',
            'is_percentage',
            'is_free_product',
            'start_date',
            'end_date',
            'minimum_spend',
            'minimum_spend_amount_for_free_product',
            'minimum_spend_amount_for_percentage',
            'minimum_spend_amount_for_flat_amount',
            'status',
        ];
    }

    private function updateMysteryGiftProductRelationDetails(MysteryGift $mysteryGift, array $mysteryGiftData): void
    {
        $mysteryGift->mysteryGiftProducts()->delete();
        if ($mysteryGiftData['uploaded_products']) {
            $mysteryGiftProductQueries = resolve(MysteryGiftProductQueries::class);
            foreach ($mysteryGiftData['uploaded_products'] as $uploadedProduct) {
                $mysteryGiftProductQueries->addNew([
                    'mystery_gift_id' => $mysteryGift->id,
                    'product_id' => $uploadedProduct['id'],
                    'quantity' => $uploadedProduct['quantity'],
                ]);
            }
        }
    }
}
