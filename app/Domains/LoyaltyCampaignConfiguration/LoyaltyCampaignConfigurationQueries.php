<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignConfiguration;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyCampaignConfiguration\DataObjects\LoyaltyCampaignConfigurationData;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Models\LoyaltyCampaignConfiguration;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LoyaltyCampaignConfigurationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->loyaltyCampaignConfigurationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(
        LoyaltyCampaignConfigurationData $loyaltyCampaignConfigurationData,
        int $companyId,
        User $user
    ): void {
        $data = $loyaltyCampaignConfigurationData->all();
        unset($data['brand_ids'], $data['location_ids'], $data['category_ids'], $data['product_ids']);
        $data['company_id'] = $companyId;
        $data['created_by_id'] = $user->id;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);

        $loyaltyCampaignConfiguration = LoyaltyCampaignConfiguration::create($data);
        $this->syncData($loyaltyCampaignConfiguration, $loyaltyCampaignConfigurationData);
    }

    public function getById(int $loyaltyCampaignConfigurationId, int $companyId): LoyaltyCampaignConfiguration
    {
        $brandQueries = resolve(BrandQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);

        $relations = [
            'brands:' . $brandQueries->getBasicColumnNames(),
            'locations:' . $locationQueries->getNameColumnName(),
            'categories:' . $categoryQueries->getBasicColumnNames(),
            'products:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'products.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'products.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'products.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'products.color:' . $colorQueries->getBasicColumnNames(),
                'products.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return LoyaltyCampaignConfiguration::select(
            'id',
            'description',
            'loyalty_campaign_type',
            'point_earned',
            'minimum_purchase_amount',
            'expiration_type',
            'include_tax',
            'status'
        )
            ->with($relations)
            ->where('company_id', $companyId)
            ->findOrFail($loyaltyCampaignConfigurationId);
    }

    public function update(
        LoyaltyCampaignConfigurationData $loyaltyCampaignConfigurationData,
        int $loyaltyCampaignConfigurationId,
        int $companyId
    ): void {
        $data = $loyaltyCampaignConfigurationData->all();

        unset($data['brand_ids'], $data['location_ids'], $data['category_ids'], $data['product_ids']);

        $loyaltyCampaignConfiguration = $this->getById($loyaltyCampaignConfigurationId, $companyId);
        $loyaltyCampaignConfiguration->update($data);

        $this->syncData($loyaltyCampaignConfiguration, $loyaltyCampaignConfigurationData);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,description,point_earned,minimum_purchase_amount';
    }

    public function getLoyaltyCampaignsExport(array $filterData, int $companyId): Collection
    {
        return $this->loyaltyCampaignConfigurationQuery($filterData, $companyId)->get();
    }

    private function loyaltyCampaignConfigurationQuery(array $filterData, int $companyId): Builder
    {
        return LoyaltyCampaignConfiguration::query()
            ->select(
                'id',
                'description',
                'point_earned',
                'minimum_purchase_amount',
                'loyalty_campaign_type',
                'expiration_type',
                'include_tax',
                'status'
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['name', 'minimum_purchase_amount', 'point_earned'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function syncData(
        LoyaltyCampaignConfiguration $loyaltyCampaignConfiguration,
        LoyaltyCampaignConfigurationData $loyaltyCampaignConfigurationData
    ): void {
        if (null !== $loyaltyCampaignConfigurationData->brand_ids) {
            $loyaltyCampaignConfiguration->brands()->sync($loyaltyCampaignConfigurationData->brand_ids);
        }

        if (null !== $loyaltyCampaignConfigurationData->location_ids) {
            $loyaltyCampaignConfiguration->locations()->sync($loyaltyCampaignConfigurationData->location_ids);
        }

        if (null !== $loyaltyCampaignConfigurationData->category_ids) {
            $loyaltyCampaignConfiguration->categories()->sync($loyaltyCampaignConfigurationData->category_ids);
        }

        if (null !== $loyaltyCampaignConfigurationData->product_ids) {
            $loyaltyCampaignConfiguration->products()->sync($loyaltyCampaignConfigurationData->product_ids);
        }
    }
}
