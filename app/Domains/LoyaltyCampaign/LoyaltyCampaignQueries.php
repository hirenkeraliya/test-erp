<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign;

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyCampaign\DataObjects\LoyaltyCampaignData;
use App\Domains\LoyaltyCampaign\Events\LoyaltyCampaignCreateEvent;
use App\Domains\LoyaltyCampaign\Events\LoyaltyCampaignUpdateEvent;
use App\Models\LoyaltyCampaign;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LoyaltyCampaignQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->loyaltyCampaignQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(LoyaltyCampaignData $loyaltyCampaignData, int $companyId, User $user): void
    {
        $data = $loyaltyCampaignData->all();
        unset($data['excluded_brand_ids']);
        $data['company_id'] = $companyId;
        $data['created_by_id'] = $user->id;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);

        $loyaltyCampaign = LoyaltyCampaign::create($data);
        if ($loyaltyCampaignData->excluded_brand_ids) {
            $loyaltyCampaign->excludedBrands()->sync($loyaltyCampaignData->excluded_brand_ids);
        }

        event(new LoyaltyCampaignCreateEvent($loyaltyCampaign));
    }

    public function getById(int $loyaltyCampaignId, int $companyId): LoyaltyCampaign
    {
        $brandQueries = resolve(BrandQueries::class);

        return LoyaltyCampaign::select(
            'id',
            'name',
            'minimum_spend_amount',
            'loyalty_points',
            'start_date',
            'end_date',
            'loyalty_point_expiration_days'
        )
            ->with('excludedBrands:' . $brandQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($loyaltyCampaignId);
    }

    public function update(LoyaltyCampaignData $loyaltyCampaignData, int $loyaltyCampaignId, int $companyId): void
    {
        $data = $loyaltyCampaignData->all();
        unset($data['excluded_brand_ids']);
        $loyaltyCampaign = $this->getById($loyaltyCampaignId, $companyId);
        $loyaltyCampaign->update($data);

        $loyaltyCampaign->excludedBrands()->sync((array) $loyaltyCampaignData->excluded_brand_ids);

        event(new LoyaltyCampaignUpdateEvent($loyaltyCampaign));
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getActiveLoyaltyCampaignsByCompanyId(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $brandQueries = resolve(BrandQueries::class);

        return LoyaltyCampaign::select(
            'id',
            'name',
            'minimum_spend_amount',
            'loyalty_points',
            'start_date',
            'end_date',
            'loyalty_point_expiration_days'
        )
            ->with('excludedBrands:' . $brandQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('start_date', '<=', now()->format('Y-m-d'))
                    ->where('end_date', '>=', now()->format('Y-m-d'));
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,minimum_spend_amount,loyalty_points';
    }

    public function getLoyaltyCampaignsExport(array $filterData, int $companyId): Collection
    {
        return $this->loyaltyCampaignQuery($filterData, $companyId)->get();
    }

    public function getByIds(array $ids, int $companyId): Collection
    {
        $brandQueries = resolve(BrandQueries::class);

        return LoyaltyCampaign::select(
            'id',
            'name',
            'minimum_spend_amount',
            'loyalty_points',
            'start_date',
            'end_date',
            'loyalty_point_expiration_days'
        )
            ->with('excludedBrands:' . $brandQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $ids)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getLoyaltyCampaignsForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        return LoyaltyCampaign::query()
            ->select('id', 'name', 'minimum_spend_amount', 'loyalty_points', 'start_date', 'end_date')
            ->where('company_id', $companyId)
            ->when($filteredData['selected_date'], function ($query) use ($filteredData): void {
                $query->where('start_date', '<=', $filteredData['selected_date'])
                    ->where('end_date', '>=', $filteredData['selected_date']);
            })
            ->when(null !== $filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where('name', 'like', '%' . $filteredData['search_text'] . '%');
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filteredData['per_page']);
    }

    public function getByOnlyId(int $loyaltyCampaignId): LoyaltyCampaign
    {
        $brandQueries = resolve(BrandQueries::class);

        return LoyaltyCampaign::select(
            'id',
            'company_id',
            'name',
            'minimum_spend_amount',
            'loyalty_points',
            'start_date',
            'end_date',
            'loyalty_point_expiration_days'
        )
        ->with('excludedBrands:' . $brandQueries->getBasicColumnNames())
        ->findOrFail($loyaltyCampaignId);
    }

    private function loyaltyCampaignQuery(array $filterData, int $companyId): Builder
    {
        return LoyaltyCampaign::query()
            ->select(
                'id',
                'name',
                'minimum_spend_amount',
                'loyalty_points',
                'start_date',
                'end_date',
                'loyalty_point_expiration_days'
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['name', 'minimum_spend_amount', 'loyalty_points', 'loyalty_point_expiration_days'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when($filterData['start_date'], function ($query) use ($filterData): void {
                $query->where('start_date', '>=', $filterData['start_date'][0])
                    ->where('start_date', '<=', $filterData['start_date'][1]);
            })
            ->when($filterData['end_date'], function ($query) use ($filterData): void {
                $query->where('end_date', '>=', $filterData['end_date'][0])
                    ->where('end_date', '<=', $filterData['end_date'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
