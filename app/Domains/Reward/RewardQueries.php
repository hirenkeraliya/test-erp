<?php

declare(strict_types=1);

namespace App\Domains\Reward;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Reward\DataObjects\RewardData;
use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Domains\Size\SizeQueries;
use App\Models\Reward;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RewardQueries
{
    public function addNew(RewardData $rewardData, int $companyId, User $user): void
    {
        $data = $rewardData->all();
        unset($data['brand_ids'], $data['department_ids'], $data['location_ids'], $data['category_ids'], $data['product_ids']);
        $data['company_id'] = $companyId;
        $data['created_by_id'] = $user->id;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);

        $reward = Reward::create($data);
        $this->syncData($reward, $rewardData);
    }

    public function update(RewardData $rewardData, int $rewardId, int $companyId): void
    {
        $data = $rewardData->all();

        unset($data['brand_ids'], $data['location_ids'], $data['department_ids'], $data['category_ids'], $data['product_ids']);

        $reward = $this->getById($rewardId, $companyId);
        $reward->update($data);

        $this->syncData($reward, $rewardData);
    }

    public function getById(int $rewardId, int $companyId): Reward
    {
        $brandQueries = resolve(BrandQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
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
            'departments:' . $departmentQueries->getBasicColumnNames(),
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

        return Reward::select(
            'id',
            'title',
            'type',
            'target_type',
            'minimum_point',
            'maximum_point',
            'loyalty_point',
            'discount_type',
            'discount',
            'status'
        )
            ->with($relations)
            ->where('company_id', $companyId)
            ->findOrFail($rewardId);
    }

    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->rewardQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getExport(array $filterData, int $companyId): Collection
    {
        return $this->rewardQuery($filterData, $companyId)->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    private function rewardQuery(array $filterData, int $companyId): Builder
    {
        return Reward::query()
            ->select('id', 'title', 'type', 'target_type', 'minimum_point', 'maximum_point', 'status')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['title', 'minimum_point', 'maximum_point'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereIntegerInRaw('type', RewardTypes::getMatchingCases($filterData['search_text']))
                        ->orWhereIntegerInRaw(
                            'target_type',
                            RewardTargetTypes::getMatchingCases($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function syncData(Reward $reward, RewardData $rewardData): void
    {
        if ($rewardData->target_type === RewardTargetTypes::BRANDS->value
            && null !== $rewardData->brand_ids) {
            $reward->brands()->sync($rewardData->brand_ids);
        }

        if ($rewardData->target_type === RewardTargetTypes::DEPARTMENTS->value
            && null !== $rewardData->department_ids) {
            $reward->departments()->sync($rewardData->department_ids);
        }

        if ($rewardData->target_type === RewardTargetTypes::CATEGORIES->value
            && null !== $rewardData->category_ids) {
            $reward->categories()->sync($rewardData->category_ids);
        }

        if ($rewardData->target_type === RewardTargetTypes::PRODUCTS->value
            && null !== $rewardData->product_ids) {
            $reward->products()->sync($rewardData->product_ids);
        }

        if (null !== $rewardData->location_ids) {
            $reward->locations()->sync($rewardData->location_ids);
        }
    }

    public function setStatus(int $rewardId, int $companyId, bool $status): void
    {
        $reward = Reward::query()
            ->where('company_id', $companyId)
            ->findOrFail($rewardId);

        $reward->status = $status;
        $reward->save();
    }
}
