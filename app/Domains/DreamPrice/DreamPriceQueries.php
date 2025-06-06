<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DreamPrice\DataObjects\DreamPriceData;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Models\DreamPrice;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DreamPriceQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->dreamPriceQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(DreamPriceData $dreamPriceData, int $companyId, User $user): void
    {
        $dreamPriceValidationData = $dreamPriceData->all();
        $dreamPriceValidationData['company_id'] = $companyId;
        $dreamPriceValidationData['created_by_id'] = $user->id;
        $dreamPriceValidationData['created_by_type'] = ModelMapping::getCaseName($user::class);

        unset($dreamPriceValidationData['location_ids']);
        unset($dreamPriceValidationData['member_group_ids']);
        unset($dreamPriceValidationData['employee_group_ids']);
        unset($dreamPriceValidationData['sale_channel_ids']);

        $dreamPrice = DreamPrice::create($dreamPriceValidationData);
        $this->updateRelationDetails($dreamPriceData, $dreamPrice);
    }

    public function getByIdWithLocations(int $dreamPriceId, int $companyId): DreamPrice
    {
        $locationQueries = resolve(LocationQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        return DreamPrice::select(
            'id',
            'name',
            'company_id',
            'start_date',
            'end_date',
            'allow_walk_in_member',
            'allow_registered_member',
            'allow_employee',
            'is_available_in_ecommerce',
            'is_available_in_pos',
            'status'
        )
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
                'saleChannels:' . $saleChannelQueries->getBasicColumnsInString(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($dreamPriceId);
    }

    public function getById(int $dreamPriceId, int $companyId): DreamPrice
    {
        return DreamPrice::select(
            'id',
            'name',
            'start_date',
            'end_date',
            'allow_walk_in_member',
            'allow_registered_member',
            'allow_employee',
            'is_available_in_ecommerce',
            'status'
        )
            ->where('company_id', $companyId)
            ->findOrFail($dreamPriceId);
    }

    public function getDreamPriceById(int $dreamPriceId): DreamPrice
    {
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        return DreamPrice::select(
            'id',
            'name',
            'company_id',
            'status',
            'start_date',
            'end_date',
            'updated_at',
            'created_at'
        )
            ->with('memberGroups:' . $memberGroupQueries->getBasicColumnNames())
            ->findOrFail($dreamPriceId);
    }

    public function update(DreamPriceData $dreamPriceData, int $dreamPriceId, int $companyId): void
    {
        $dreamPriceValidationData = $dreamPriceData->all();
        unset($dreamPriceValidationData['location_ids']);
        unset($dreamPriceValidationData['member_group_ids']);
        unset($dreamPriceValidationData['employee_group_ids']);
        unset($dreamPriceValidationData['sale_channel_ids']);

        $dreamPrice = $this->getById($dreamPriceId, $companyId);
        $dreamPrice->update($dreamPriceValidationData);
        $this->updateRelationDetails($dreamPriceData, $dreamPrice);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getListWithProducts(int $companyId, int $locationId, ?string $afterUpdatedAt = null): Collection
    {
        $dreamPriceProductQueries = new DreamPriceProductQueries();
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return DreamPrice::select(
            'id',
            'name',
            'start_date',
            'end_date',
            'allow_walk_in_member',
            'allow_registered_member',
            'allow_employee',
            'status'
        )
            ->with([
                'dreamPriceProducts:' . $dreamPriceProductQueries->getBasicColumnNames(),
                'dreamPriceProducts.product:' . $productQueries->getProductNameColumn(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('is_available_in_pos', true)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('status', true);
            })
            ->get();
    }

    public function getByIdsWithProductsAndLocations(array $ids, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();
        $dreamPriceProductQueries = new DreamPriceProductQueries();
        $memberGroupQueries = new MemberGroupQueries();
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return DreamPrice::select(
            'id',
            'name',
            'start_date',
            'end_date',
            'allow_walk_in_member',
            'allow_registered_member',
            'allow_employee',
            'status'
        )
            ->with([
                'dreamPriceProducts:' . $dreamPriceProductQueries->getBasicColumnNames(),
                'dreamPriceProducts.product:' . $productQueries->getBasicColumnNames(),
                'locations:' . $locationQueries->getNameColumnName(),
                'memberGroups:' . $memberGroupQueries->getBasicColumnNames(),
                'employeeGroups:' . $employeeGroupQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->whereIntegerInRaw('id', $ids)
            ->get();
    }

    public function getDreamPricesExport(array $filterData, int $companyId): Collection
    {
        return $this->dreamPriceQuery($filterData, $companyId)->get();
    }

    public function getDreamPricesForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return DreamPrice::query()
            ->select('id', 'name', 'start_date', 'end_date', 'status', 'is_available_in_pos')
            ->with([
                'dreamPriceProducts:' . $dreamPriceProductQueries->getDreamPriceColumn(),
                'dreamPriceProducts.product:' . $productQueries->getProductNameColumn(),
                'dreamPriceProducts.product.media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->where('is_available_in_pos', true)
            ->where('status', true)
            ->when($filteredData['selected_date'], function ($query) use ($filteredData): void {
                $query->where('start_date', '<=', $filteredData['selected_date'])
                    ->where('end_date', '>=', $filteredData['selected_date']);
            })
            ->when($filteredData['location_id'], function ($query) use ($filteredData, $locationQueries): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterById($filteredData['location_id'], LocationTypes::STORE->value)
                );
            })
            ->when(null !== $filteredData['dream_price_ids'], function ($query) use ($filteredData): void {
                $query->whereIn('id', $filteredData['dream_price_ids']);
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

    public function updateStatus(int $dreamPriceId, int $companyId, bool $status): void
    {
        $promotion = DreamPrice::query()
            ->where('company_id', $companyId)
            ->findOrFail($dreamPriceId);
        $promotion->status = $status;
        $promotion->save();
    }

    private function dreamPriceQuery(array $filterData, int $companyId): Builder
    {
        $dreamPriceProductQueries = resolve(DreamPriceProductQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);

        return DreamPrice::query()
            ->select(
                'id',
                'name',
                'start_date',
                'end_date',
                'allow_walk_in_member',
                'allow_registered_member',
                'allow_employee',
                'status'
            )
            ->with([
                'importRecord:' . $importRecordQueries->getBasicColumns(),
                'importRecord.media:' . $mediaQueries->getBasicColumnNames(),
                'dreamPriceProducts:' . $dreamPriceProductQueries->getDreamPriceColumn(),
                'saleDiscountDreamPrice:' . $saleDiscountQueries->getBasicColumnNames(),
                'saleItemDiscountDreamPrice:' . $saleItemDiscountQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when(null !== $filterData['status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getListWithProductsInEcommerce(
        int $companyId,
        int $locationId,
        array $filteredData
    ): LengthAwarePaginator {
        $locationQueries = resolve(LocationQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        return DreamPrice::select(
            'id',
            'name',
            'start_date',
            'end_date',
            'allow_walk_in_member',
            'allow_registered_member',
            'allow_employee',
            'is_available_in_ecommerce',
            'is_available_in_pos',
            'status',
            'created_at',
            'updated_at'
        )
            ->with(['memberGroups:' . $memberGroupQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->where('status', true)
            ->where('is_available_in_ecommerce', true)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->when($filteredData['after_updated_at'], function ($query) use ($filteredData): void {
                $query->where('updated_at', '>=', $filteredData['after_updated_at']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    private function updateRelationDetails(DreamPriceData $dreamPriceData, DreamPrice $dreamPrice): void
    {
        $dreamPrice->memberGroups()->detach();
        if ($dreamPriceData->member_group_ids) {
            $dreamPrice->memberGroups()->attach($dreamPriceData->member_group_ids);
        }

        $dreamPrice->locations()->detach();
        if ($dreamPriceData->location_ids) {
            $dreamPrice->locations()->attach($dreamPriceData->location_ids);
        }

        $dreamPrice->employeeGroups()->detach();
        if ($dreamPriceData->employee_group_ids) {
            $dreamPrice->employeeGroups()->attach($dreamPriceData->employee_group_ids);
        }

        if (! array_key_exists('sale_channel_ids', $dreamPriceData->all())) {
            return;
        }

        if (null === $dreamPriceData->sale_channel_ids) {
            return;
        }

        $dreamPrice->saleChannels()->sync($dreamPriceData->sale_channel_ids);
    }

    public function getSeasonalSalesBasicColumns(): Closure
    {
        return fn ($query) => $query->select('id', 'name');
    }

    public function getAllActiveDreamPrice(): Collection
    {
        return DB::table('dream_prices AS dreamPrice1')
            ->join(
                'dream_price_products AS dreamPriceProduct1',
                'dreamPrice1.id',
                '=',
                'dreamPriceProduct1.dream_price_id'
            )
            ->join(
                'dream_price_location AS dreamPriceLocation1',
                'dreamPrice1.id',
                '=',
                'dreamPriceLocation1.dream_price_id'
            )
            ->join('dream_prices AS dreamPrice2', function ($join): void {
                $join->join('dream_price_products AS dreamPriceProduct2', function ($join): void {
                    $join->on('dreamPrice2.id', '=', 'dreamPriceProduct2.dream_price_id');
                })
                ->join('dream_price_location AS dreamPriceLocation2', function ($join): void {
                    $join->on('dreamPrice2.id', '=', 'dreamPriceLocation2.dream_price_id');
                })
                ->on('dreamPriceProduct1.product_id', '=', 'dreamPriceProduct2.product_id')
                ->on('dreamPriceLocation1.location_id', '=', 'dreamPriceLocation2.location_id')
                ->whereColumn('dreamPrice1.id', '<', 'dreamPrice2.id')
                ->where('dreamPrice2.end_date', '>=', Carbon::now()->format('Y-m-d'))
                ->where('dreamPrice2.status', 1);
            })
            ->select(
                'dreamPrice1.id AS dream_price_id_1',
                'dreamPrice1.name AS dream_price_name_1',
                'dreamPrice1.company_id AS dream_price_company_id_1',
                'dreamPrice2.id AS dream_price_id_2',
                'dreamPrice2.name AS dream_price_name_2',
                'dreamPrice2.company_id AS dream_price_company_id_2'
            )
            ->where('dreamPrice1.end_date', '>=', Carbon::now()->format('Y-m-d'))
            ->where('dreamPrice1.status', 1)
            ->distinct('id')
            ->get();
    }

    public function setUpdatedAt(DreamPrice $dreamPrice): void
    {
        $dreamPrice->touch();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,company_id,start_date,end_date,is_available_in_ecommerce,status';
    }

    public function validateLocationAndSaleChannelMatch(DreamPrice $dreamPrice, SaleChannel $saleChannel): bool
    {
        return DreamPrice::select('id')->where('id', $dreamPrice->id)
                ->whereHas(
                    'locations',
                    fn ($query) => $query->select(
                        'location_id'
                    )->where('location_id', $saleChannel->default_location_id)
                )
                ->whereHas(
                    'saleChannels',
                    fn ($query) => $query->select('sale_channel_id')->where('sale_channel_id', $saleChannel->id)
                )
                ->exists();
    }

    public function getDreamPriceByIdForEcommerce(int $dreamPriceId): DreamPrice
    {
        return DreamPrice::select('id', 'company_id')->findOrFail($dreamPriceId);
    }

    public function getByDreamPrice(int $dreamPriceId, int $companyId): ?DreamPrice
    {
        return DreamPrice::select('id', 'is_available_in_ecommerce')
            ->where('company_id', $companyId)
            ->where('id', $dreamPriceId)
            ->first();
    }
}
