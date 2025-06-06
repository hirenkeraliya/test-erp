<?php

declare(strict_types=1);

namespace App\Domains\Membership;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Membership\DataObjects\MembershipData;
use App\Models\Membership;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MembershipQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->membershipQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(MembershipData $membershipData, int $companyId, User $user): void
    {
        $data = $membershipData->all();
        $data['company_id'] = $companyId;
        $data['created_by_id'] = $user->id;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);

        Membership::create($data);
    }

    public function getById(int $membershipId, int $companyId): Membership
    {
        return Membership::select(
            'id',
            'name',
            'lifetime_value',
            'loyalty_points_per_currency_unit',
            'min_loyalty_points_for_redemption',
            'max_loyalty_points_for_redemption'
        )
            ->where('company_id', $companyId)
            ->findOrFail($membershipId);
    }

    public function update(MembershipData $membershipData, int $membershipId, int $companyId): void
    {
        $membership = $this->getById($membershipId, $companyId);
        $membership->update($membershipData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getByCompanyIdSortByMinimumSpendAmount(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return Membership::select(
            'id',
            'name',
            'lifetime_value',
            'loyalty_points_per_currency_unit',
            'min_loyalty_points_for_redemption',
            'max_loyalty_points_for_redemption'
        )
            ->where('company_id', $companyId)
            ->orderBy('lifetime_value', 'desc')
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getMembershipWhereLifetimeValueIsZero(int $companyId): ?Membership
    {
        return Membership::select('id')
            ->where('company_id', $companyId)
            ->where('lifetime_value', 0.00)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,loyalty_points_per_currency_unit,created_at,min_loyalty_points_for_redemption,max_loyalty_points_for_redemption';
    }

    public function getColumnNamesForMemberApi(): string
    {
        return 'id,name';
    }

    public function getMembershipsExport(array $filterData, int $companyId): Collection
    {
        return $this->membershipQuery($filterData, $companyId)->get();
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Membership::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Membership::select('id')
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getIdByName(string $name, int $companyId): ?int
    {
        return Membership::select('id')->where('name', $name)->where('company_id', $companyId)->first()?->id;
    }

    public function getByIdBasedOnMemberSpentTillNow(int $companyId, float $memberSpentTillNow): ?int
    {
        return Membership::query()
            ->where('company_id', $companyId)
            ->where('lifetime_value', '<', $memberSpentTillNow)
            ->first()
            ?->id;
    }

    private function membershipQuery(array $filterData, int $companyId): Builder
    {
        return Membership::query()
            ->select(
                'id',
                'name',
                'lifetime_value',
                'loyalty_points_per_currency_unit',
                'min_loyalty_points_for_redemption',
                'max_loyalty_points_for_redemption'
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            [
                                'name',
                                'lifetime_value',
                                'loyalty_points_per_currency_unit',
                                'min_loyalty_points_for_redemption',
                                'max_loyalty_points_for_redemption',
                            ],
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
}
