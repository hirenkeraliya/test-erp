<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionUpdate;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\PromoterCommissionUpdate;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromoterCommissionUpdateQueries
{
    public function addNew(array $promoterCommissionUpdateDetails): void
    {
        PromoterCommissionUpdate::create($promoterCommissionUpdateDetails);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,affected_by_id,affected_by_type,department_id,location_id,brand_id,commission_percentage,flat_commission,discount_type,amount,commission_amount,total_price_paid';
    }

    public function getPaginatedCommissionDetailsByPromoter(
        array $filterData,
        int $promoterCommissionId
    ): LengthAwarePaginator {
        $departmentQueries = resolve(DepartmentQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        return PromoterCommissionUpdate::query()
            ->select(
                'id',
                'promoter_commission_id',
                'affected_by_id',
                'affected_by_type',
                'department_id',
                'amount',
                'commission_percentage',
                'commission_amount'
            )
            ->with([
                'department:' . $departmentQueries->getBasicColumnNames(),
                'affected_by' => function (MorphTo $morphTo) use ($saleItemQueries, $saleReturnItemQueries): void {
                    $morphTo->constrain([
                        SaleItem::class => $saleItemQueries->getSaleAndProductRelationColumns(),
                        SaleReturnItem::class => $saleReturnItemQueries->getSaleReturnAndProductRelationColumns(),
                    ]);
                },
            ])
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $departmentQueries): void {
                $query->where(function ($query) use ($filterData, $departmentQueries): void {
                    $query->whereHas('department', $departmentQueries->searchByColumns($filterData['search_text']))
                        ->orWhereAny(
                            ['commission_percentage', 'commission_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where('promoter_commission_id', $promoterCommissionId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getPromoterCommissionDetailsForExport(array $filterData, int $promoterCommissionId): Collection
    {
        $departmentQueries = resolve(DepartmentQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        return PromoterCommissionUpdate::query()
            ->select(
                'id',
                'promoter_commission_id',
                'affected_by_id',
                'affected_by_type',
                'department_id',
                'amount',
                'commission_percentage',
                'commission_amount'
            )
            ->with([
                'department:' . $departmentQueries->getBasicColumnNames(),
                'affected_by' => function (MorphTo $morphTo) use ($saleItemQueries, $saleReturnItemQueries): void {
                    $morphTo->constrain([
                        SaleItem::class => $saleItemQueries->getSaleProductAndCounterRelationColumns(),
                        SaleReturnItem::class => $saleReturnItemQueries->getSaleReturnProductAndCounterRelationColumns(),
                    ]);
                },
            ])
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $departmentQueries): void {
                $query->where(function ($query) use ($filterData, $departmentQueries): void {
                    $query->whereHas('department', $departmentQueries->searchByColumns($filterData['search_text']))
                        ->orWhereAny(
                            ['commission_percentage', 'commission_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->where('promoter_commission_id', $promoterCommissionId)
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getPromoterCommissionReportByItem(array $filterData): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);

        return $this->getPromoterCommissionReportQuery($filterData)
            ->when(null !== $filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
            })
            ->when(null !== $filterData['group_ids'], function ($query) use ($filterData, $promoterQueries): void {
                $query->whereHas('promoterCommission', function ($query) use ($promoterQueries, $filterData): void {
                    $query->select('id')
                        ->whereHas('promoter', $promoterQueries->filterByGroupIds($filterData['group_ids']));
                });
            })
            ->get();
    }

    public function deleteByPromoterCommissionIds(array $promoterCommissionIds): void
    {
        PromoterCommissionUpdate::whereIntegerInRaw('promoter_commission_id', $promoterCommissionIds)->delete();
    }

    public function getPromoterCommissionAmount(array $date, int $locationId, int $promoterId): Collection
    {
        return PromoterCommissionUpdate::query()
            ->where('location_id', $locationId)
            ->whereIn('promoter_commission_id', function ($query) use ($promoterId, $date): void {
                $query->select('id')
                    ->from('promoter_commissions')
                    ->where('promoter_id', $promoterId)
                    ->where('commission_date', '>=', $date[0])
                    ->where('commission_date', '<=', $date[1]);
            })
            ->get();
    }

    public function getPromoterCommissionBySingleData(array $filterData, int $promoterId): LengthAwarePaginator
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        return PromoterCommissionUpdate::query()
            ->with('affected_by', function ($morphTo) use ($saleItemQueries, $saleReturnItemQueries): void {
                $morphTo->constrain([
                    SaleItem::class => $saleItemQueries->getIdSaleIdAndQuantityWithSaleRelation(),
                    SaleReturnItem::class => $saleReturnItemQueries->getIdSaleReturnIdAndQuantityWithSaleReturnRelation(),
                ]);
            })
            ->whereHas('promoterCommission', function ($query) use ($promoterId): void {
                $query->where('promoter_id', $promoterId);
            })
            ->whereHasMorph(
                'affected_by',
                [ModelMapping::SALE_ITEM->name, ModelMapping::SALE_RETURN_ITEM->name],
                function ($query) use ($filterData): void {
                    if ($query->getModel() instanceof SaleItem) {
                        $query->whereHas('sale', function ($query) use ($filterData): void {
                            $query->where(
                                'happened_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['selected_date'])
                            )
                                ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['selected_date']))
                                ->groupBy('offline_sale_id');
                        });

                        return;
                    }

                    $query->whereHas('saleReturn', function ($query) use ($filterData): void {
                        $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['selected_date']))
                            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['selected_date']));
                    });
                }
            )
            ->where('location_id', $filterData['location_id'])
            ->paginate($filterData['per_page']);
    }

    public function fetchCommissionDetailsById(int $promoterCommissionUpdateId): ?PromoterCommissionUpdate
    {
        $departmentQueries = resolve(DepartmentQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        return PromoterCommissionUpdate::query()
            ->with([
                'department:' . $departmentQueries->getBasicColumnNames(),
                'affected_by' => function ($morphTo) use ($saleItemQueries, $saleReturnItemQueries): void {
                    $morphTo->constrain([
                        SaleItem::class => $saleItemQueries->getSaleProductAndPromoterRelationColumns(),
                        SaleReturnItem::class => $saleReturnItemQueries->getSaleReturnProductAndPromoterRelationColumns(),
                    ]);
                },
            ])
            ->find($promoterCommissionUpdateId);
    }

    public function getItemsSoldCountAndCommissionAmountTotal(
        array $date,
        int $locationId,
        int $promoterId
    ): ?PromoterCommissionUpdate {
        $saleItemQuery = DB::table('sale_items as si')
            ->select('si.id', 'si.quantity', DB::raw('DATE(s.happened_at) as happened_at'))
            ->leftJoin('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('counter_updates as cu', 'cu.id', '=', 's.counter_update_id')
            ->leftJoin('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('c.location_id', $locationId)
            ->where('s.happened_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('s.happened_at', '<=', CommonFunctions::addEndTime($date[1]));

        $saleReturnItemQuery = DB::table('sale_return_items as sri')
            ->select('sri.id', 'sri.quantity', DB::raw('DATE(sr.happened_at) as happened_at'), 'sri.total_price_paid')
            ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->leftJoin('counter_updates as cu', 'cu.id', '=', 'sr.counter_update_id')
            ->leftJoin('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('c.location_id', $locationId)
            ->where('sr.happened_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('sr.happened_at', '<=', CommonFunctions::addEndTime($date[1]));

        return PromoterCommissionUpdate::query()
            ->select(
                DB::raw('SUM(saleItems.quantity) as total_units_sold'),
                DB::raw('SUM(saleReturnItems.quantity) as total_units_returned'),
                DB::raw('SUM(commission_amount) as total_commission_amount'),
                DB::raw('SUM(amount) as net_sales'),
                DB::raw('SUM(saleReturnItems.total_price_paid) as total_return_amount'),
            )
            ->where('location_id', $locationId)
            ->leftJoinSub($saleItemQuery, 'saleItems', function ($join): void {
                $join
                    ->on('saleItems.id', '=', 'promoter_commission_updates.affected_by_id')
                    ->where('promoter_commission_updates.affected_by_type', ModelMapping::SALE_ITEM->name);
            })
            ->leftJoinSub($saleReturnItemQuery, 'saleReturnItems', function ($join): void {
                $join
                    ->on('saleReturnItems.id', '=', 'promoter_commission_updates.affected_by_id')
                    ->where('promoter_commission_updates.affected_by_type', ModelMapping::SALE_RETURN_ITEM->name);
            })
            ->whereNull('deleted_at')
            ->whereHas('promoterCommission', function ($query) use ($promoterId, $date): void {
                $query
                    ->where('promoter_id', $promoterId)
                    ->where('commission_date', '>=', $date[0])
                    ->where('commission_date', '<=', $date[1])
                    ->whereNull('deleted_at');
            })
            ->first();
    }

    public function promoterCommissionBasedOnPromoterAndLocation(
        array $filterData,
        int $promoterId,
        int $locationId
    ): Collection {
        $saleItemQuery = DB::table('sale_items as si')
            ->select('si.id', 'si.quantity', DB::raw('DATE(s.happened_at) as happened_at'))
            ->leftJoin('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('counter_updates as cu', 'cu.id', '=', 's.counter_update_id')
            ->leftJoin('counters as c', 'c.id', '=', 'cu.counter_id')
            ->where('c.location_id', $locationId)
            ->whereNotIn(
                's.status',
                [
                    SaleStatus::VOID_SALE->value,
                    SaleStatus::CANCEL_CREDIT_SALE->value,
                    SaleStatus::CANCEL_LAYAWAY_SALE->value,
                ]
            )
            ->where('s.happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('s.happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));

        $saleReturnItemQuery = DB::table('sale_return_items as sri')
            ->select('sri.id', 'sri.quantity', DB::raw('DATE(sr.happened_at) as happened_at'))
            ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->where('sr.happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('sr.happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));

        return PromoterCommissionUpdate::query()
            ->select(
                DB::raw('SUM(saleItems.quantity) as units_sold'),
                DB::raw('SUM(saleReturnItems.quantity) as units_returned'),
                DB::raw('SUM(commission_amount) as commission_amount'),
                DB::raw('SUM(amount) as net_sales'),
                'saleItems.happened_at',
                'saleReturnItems.happened_at as sale_return_date',
            )
            ->leftJoinSub($saleItemQuery, 'saleItems', function ($join): void {
                $join
                    ->on('saleItems.id', '=', 'promoter_commission_updates.affected_by_id')
                    ->where('promoter_commission_updates.affected_by_type', ModelMapping::SALE_ITEM->name);
            })
            ->leftJoinSub($saleReturnItemQuery, 'saleReturnItems', function ($join): void {
                $join
                    ->on('saleReturnItems.id', '=', 'promoter_commission_updates.affected_by_id')
                    ->where('promoter_commission_updates.affected_by_type', ModelMapping::SALE_RETURN_ITEM->name);
            })
            ->whereNull('deleted_at')
            ->whereHas('promoterCommission', function ($query) use ($promoterId): void {
                $query->where('promoter_id', $promoterId)
                    ->whereNull('deleted_at');
            })
            ->groupBy('happened_at', 'sale_return_date')
            ->get();
    }

    private function getPromoterCommissionReportQuery(array $filterData): Builder
    {
        $promoterCommissionQueries = resolve(PromoterCommissionQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);

        return PromoterCommissionUpdate::query()
            ->with([
                'department:' . $departmentQueries->getBasicColumnNames(),
                'brand:' . $brandQueries->getBasicColumnNames(),
                'promoterCommission:id,promoter_id',
                'promoterCommission.promoter:id,employee_id,code,group_id',
                'promoterCommission.promoter.employee:id,first_name,last_name',
                'promoterCommission.promoter.promoterGroup:id,name',
                'affected_by' => function ($query) use ($saleQueries, $productQueries, $saleItemQueries): void {
                    $query->whereHas('product', function ($query) use ($productQueries): void {
                        $columns = explode(',', $productQueries->getColumnsForPromoterCommissionReport());
                        $query->select(...$columns);
                    })->constrain([
                        SaleItem::class => function ($query) use ($saleQueries, $productQueries): void {
                            $query->select('id', 'sale_id', 'product_id', 'quantity')
                                ->with([
                                    'sale:' . $saleQueries->getBasicColumnsForReport(),
                                    'product' => function ($query) use ($productQueries): void {
                                        $columns = explode(
                                            ',',
                                            $productQueries->getColumnsForPromoterCommissionReport()
                                        );
                                        $query->select(...$columns);
                                    },
                                ]);
                        },
                        SaleReturnItem::class => function ($query) use (
                            $saleQueries,
                            $productQueries,
                            $saleItemQueries
                        ): void {
                            $query->select('id', 'original_sale_item_id', 'quantity')
                                ->with([
                                    'saleItem:' . $saleItemQueries->getColumnNamesForPromoterCommissionReport(),
                                    'saleItem.sale:' . $saleQueries->getBasicColumnsForReport(),
                                    'saleItem.product' => function ($query) use ($productQueries): void {
                                        $columns = explode(
                                            ',',
                                            $productQueries->getColumnsForPromoterCommissionReport()
                                        );
                                        $query->select(...$columns);
                                    },
                                ]);
                        },
                    ]);
                },
            ])
            ->whereHas('affected_by', function ($query) use ($productQueries): void {
                $query->whereHas('product', function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getColumnsForPromoterCommissionReport());
                    $query->select(...$columns);
                });
            })
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->whereHas(
                'promoterCommission',
                $promoterCommissionQueries->filterByCommissionDate($filterData['month_range'])
            );
    }
}
