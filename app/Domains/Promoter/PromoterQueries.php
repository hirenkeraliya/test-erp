<?php

declare(strict_types=1);

namespace App\Domains\Promoter;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\DataObjects\ChangePasswordData;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Domains\Promoter\Enums\SalesByPromoterReportExcludeTypes;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\Admin;
use App\Models\Promoter;
use App\Models\SaleReturnItem;
use App\Models\StoreManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PromoterQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $locationQueries = new LocationQueries();
        $employeeQueries = new EmployeeQueries();
        $promoterGroupQueries = new PromoterGroupQueries();

        return Promoter::query()
            ->select('id', 'employee_id', 'group_id')
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'promoterGroup:' . $promoterGroupQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $employeeQueries,
                $locationQueries
            ): void {
                $query->where(function ($query) use ($filterData, $employeeQueries, $locationQueries): void {
                    $query->whereHas(
                        'employee',
                        $employeeQueries->searchByBasicColumns($filterData['search_text'])
                    )->orWhereHas('locations', $locationQueries->searchByName($filterData['search_text']));
                });
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when(array_key_exists('status', $filterData) && $filterData['status'], function ($query) use (
                $filterData
            ): void {
                $query->whereHas('employee', function ($query) use ($filterData): void {
                    $status = $filterData['status'] === Status::ACTIVE->value ? true : ($filterData['status'] === Status::INACTIVE->value ? false : null);
                    if (null !== $status) {
                        $query->where('status', $status);
                    }
                });
            })
            ->when($filterData['group_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('group_id', (array) $filterData['group_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getByIds(array $promoterIds): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::select('employee_id')
            ->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->whereIntegerInRaw('promoters.id', $promoterIds)
            ->get();
    }

    public function getPaginatedSalesByPromoters(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getSalesByPromotersQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getSalesByPromotersExport(array $filterData, int $companyId): Collection
    {
        return $this->getSalesByPromotersQuery($filterData, $companyId)->get();
    }

    public function getSalesByPromotersForDashboard(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $fromDate,
        string $toDate,
        bool $refresh = false,
    ): Collection {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cacheKey = 'cache-top-sale-by-promoter-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $fromDate . '-' . $toDate;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Promoter::query()
                ->with(['employee:' . $employeeQueries->getColumnNamesForPromoter()])
                ->select(
                    'promoters.id',
                    'promoters.employee_id',
                    DB::raw(
                        '(IFNULL(promoter_sale_total.total_units_sold, 0) - IFNULL(promoter_sale_return_total.total_units_returned, 0)) as units_sold'
                    ),
                    DB::raw(
                        '(IFNULL(promoter_sale_total.total_amount_sold, 0) - IFNULL(promoter_sale_return_total.total_returned_amount, 0)) as amount_sold'
                    ),
                    DB::raw('IFNULL(promoter_sale_return_total.total_units_returned, 0) as total_units_returned'),
                )
                ->leftJoinSub(
                    DB::table('sale_item_promoter')
                        ->select(
                            'sale_item_promoter.promoter_id',
                            DB::raw('SUM(si.quantity) as total_units_sold'),
                            DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold'),
                        )
                        ->leftJoinSub(
                            DB::table('sale_items')
                                ->select(
                                    'id',
                                    'quantity',
                                    'total_price_paid',
                                    'product_id',
                                    'sale_id',
                                    'promoters_per_sale_item',
                                    'is_exchange'
                                )
                                ->leftJoinSub(
                                    DB::table('sale_item_promoter')
                                        ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                        ->groupBy('sale_item_id'),
                                    'sip',
                                    'sale_items.id',
                                    '=',
                                    'sip.sale_item_id'
                                ),
                            'si',
                            'si.id',
                            '=',
                            'sale_item_promoter.sale_item_id'
                        )
                        ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                        ->leftJoin('sales', 'sales.id', '=', 'si.sale_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('p.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('p.brand_id', $brandId);
                        })
                        ->where('sales.happened_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('sales.happened_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->groupBy('sale_item_promoter.promoter_id'),
                    'promoter_sale_total',
                    'promoter_sale_total.promoter_id',
                    '=',
                    'promoters.id'
                )
                ->leftJoinSub(
                    DB::table('sale_item_promoter')
                        ->select(
                            'sale_item_promoter.promoter_id',
                            DB::raw('SUM(sri.quantity) as total_units_returned'),
                            DB::raw('SUM(sri.total_price_paid / si.promoters_per_sale_item) as total_returned_amount'),
                        )
                        ->leftJoinSub(
                            DB::table('sale_items')
                                ->select('id', 'product_id', 'sale_id', 'promoters_per_sale_item', 'is_exchange')
                                ->leftJoinSub(
                                    DB::table('sale_item_promoter')
                                        ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                        ->groupBy('sale_item_id'),
                                    'sip',
                                    'sale_items.id',
                                    '=',
                                    'sip.sale_item_id'
                                ),
                            'si',
                            'si.id',
                            '=',
                            'sale_item_promoter.sale_item_id'
                        )
                        ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                        ->leftJoin('sale_return_items as sri', 'sri.original_sale_item_id', '=', 'si.id')
                        ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                        ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sr.counter_update_id')
                        ->leftJoin('sale_items as exi', 'exi.sale_return_item_id', '=', 'sri.id')
                        ->leftJoin('sales as exs', 'exs.id', '=', 'sr.original_sale_id')
                        ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('p.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('p.brand_id', $brandId);
                        })
                        ->where('sr.happened_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('sr.happened_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->groupBy('sale_item_promoter.promoter_id'),
                    'promoter_sale_return_total',
                    'promoter_sale_return_total.promoter_id',
                    '=',
                    'promoters.id'
                )
                ->where(function ($query): void {
                    $query->whereNotNull('promoter_sale_total.total_units_sold')
                        ->orWhereNotNull('promoter_sale_total.total_amount_sold')
                        ->orWhereNotNull('promoter_sale_return_total.total_units_returned')
                        ->orWhereNotNull('promoter_sale_return_total.total_returned_amount');
                })
                ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
                ->when($locationId, function ($query) use ($locationQueries, $locationId): void {
                    $query->whereHas(
                        'locations',
                        $locationQueries->filterById((int) $locationId, LocationTypes::STORE->value)
                    );
                })
                ->orderBy('amount_sold', 'desc')
                ->limit(10)
                ->get()
        );
    }

    public function getSalesByPromotersTotals(array $filterData, int $companyId): ?Promoter
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $filterData['sales_filter_types'] = array_map('intval', $filterData['sales_filter_types']);

        return Promoter::query()
            ->select(
                'promoters.id',
                DB::raw('SUM(promoter_sale_return_total.total_units_returned) as total_units_returned'),
                DB::raw('SUM(promoter_sale_return_total.total_returned_amount) as total_returned_amount'),
                DB::raw('SUM(promoter_sale_total.total_tax_amount) as total_tax_amount'),
                DB::raw('SUM(promoter_sale_total.total_discount_amount) as total_discount_amount'),
                DB::raw('SUM(promoter_sale_total.total_units_sold) as total_units_sold'),
                DB::raw('SUM(promoter_sale_total.total_amount_sold) as total_amount_sold'),
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(si.quantity) as total_units_sold'),
                        DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold'),
                        DB::raw('SUM(si.total_discount_amount / si.promoters_per_sale_item) as total_discount_amount'),
                        DB::raw('SUM(si.total_tax_amount / si.promoters_per_sale_item) as total_tax_amount'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select(
                                'id',
                                'quantity',
                                'total_price_paid',
                                'total_discount_amount',
                                'total_tax_amount',
                                'product_id',
                                'sale_id',
                                'promoters_per_sale_item',
                                'is_exchange'
                            )
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('master_products as mp', 'mp.id', '=', 'p.master_product_id')
                    ->leftJoin('sales', 'sales.id', '=', 'si.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.department_id', $filterData['department_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.department_id', $filterData['department_ids']);
                        }
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sales.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sales.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::VOID_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues());
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::PENDING_LAYAWAY_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.layaway_pending_amount');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::COMPLETED_LAYAWAY_SALES->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.layaway_completed_at');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::PENDING_CREDIT_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.credit_pending_amount');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::COMPLETE_CREDIT_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.credit_completed_at');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::RETURN_WITH_NEW_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->where(function ($query): void {
                                $query->whereNull('sales.sale_return_id')
                                    ->orWhere('si.is_exchange', true);
                            });
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::EXCHANGE_SALES->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->where('si.is_exchange', false);
                        }
                    )
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_total',
                'promoter_sale_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(sri.quantity) as total_units_returned'),
                        DB::raw('SUM(sri.total_price_paid / si.promoters_per_sale_item) as total_returned_amount'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select('id', 'product_id', 'sale_id', 'promoters_per_sale_item', 'is_exchange')
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('master_products as mp', 'mp.id', '=', 'p.master_product_id')
                    ->join('sale_return_items as sri', 'sri.original_sale_item_id', '=', 'si.id')
                    ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sr.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.department_id', $filterData['department_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.department_id', $filterData['department_ids']);
                        }
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where(
                            'sr.happened_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['date_range'][0])
                        )
                            ->where('sr.happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                    })
                    ->where(function ($query) use ($filterData): void {
                        $query->when(
                            in_array(
                                SalesByPromoterReportExcludeTypes::EXCHANGE_SALES->value,
                                $filterData['sales_filter_types'],
                                true
                            ),
                            function ($query): void {
                                $query->whereIn('sri.id', function ($query): void {
                                    $query->select('sale_return_item_id')
                                        ->from('sale_items')
                                        ->where('is_exchange', 1)
                                        ->whereNotNull('sale_return_item_id');
                                });
                            }
                        )->when(
                            in_array(
                                SalesByPromoterReportExcludeTypes::RETURN_WITH_NEW_SALE->value,
                                $filterData['sales_filter_types'],
                                true
                            ),
                            function ($query): void {
                                $query->whereNotIn('sri.id', function ($query): void {
                                    $query->select('sale_return_item_id')
                                        ->from('sale_items')
                                        ->where('is_exchange', 1)
                                        ->whereNotNull('sale_return_item_id');
                                });
                            }
                        );
                    })
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_return_total',
                'promoter_sale_return_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->when($filterData['search_text'], function ($query) use ($employeeQueries, $filterData): void {
                $query->whereHas('employee', $employeeQueries->searchByFirstAndLastName($filterData['search_text']));
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['promoter_id'], function ($query) use ($filterData): void {
                $query->where('id', (int) $filterData['promoter_id']);
            })
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->first();
    }

    public function addNew(PromoterData $promoterData, Admin|StoreManager $user): void
    {
        $promoterValidationData = $promoterData->all();
        unset($promoterValidationData['location_ids']);
        $promoterValidationData['password'] = bcrypt($promoterValidationData['password']);
        $promoterValidationData['created_by_type'] = ModelMapping::getCaseName($user::class);
        $promoterValidationData['created_by_id'] = $user->id;

        $promoter = Promoter::create($promoterValidationData);

        $promoter->locations()->sync($promoterData->location_ids);
    }

    public function getByIdWithEmployeeAndLocations(int $promoterId, int $companyId): Promoter
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return Promoter::select(
            'id',
            'employee_id',
            'username',
            'monthly_sales_target',
            'code',
            'default_commission_amount_percentage',
            'monthly_target_commission_percentage',
            'group_id'
        )
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'employee.media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($promoterId);
    }

    public function getById(int $promoterId, int $companyId): Promoter
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::query()
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($promoterId);
    }

    public function updateFcmToken(string $fcmToken, int $promoterId, int $companyId): void
    {
        $promoter = $this->getById($promoterId, $companyId);
        $promoter->fcm_token = $fcmToken;
        $promoter->save();
    }

    public function update(PromoterData $promoterData, int $promoterId, int $companyId): void
    {
        $promoter = $this->getById($promoterId, $companyId);
        $promoterValidatedData = $promoterData->all();

        unset(
            $promoterValidatedData['location_ids'],
            $promoterValidatedData['password'],
        );

        $promoter->update([
            'employee_id' => $promoterData->employee_id,
            'username' => $promoterData->username,
            'monthly_sales_target' => $promoterData->monthly_sales_target,
            'code' => $promoterData->code,
            'default_commission_amount_percentage' => $promoterData->default_commission_amount_percentage,
            'monthly_target_commission_percentage' => $promoterData->monthly_target_commission_percentage,
            'group_id' => $promoterData->group_id,
        ]);

        $promoter->locations()->sync($promoterData->location_ids);
    }

    public function getPromoterListForPosAndOrders(
        int $locationId,
        int $companyId,
        int $typeId = SaleReturnOrVoidSaleReasonTypes::POS->value,
        ?string $afterUpdatedAt = null,
    ): Collection {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::with(['employee:' . $employeeQueries->getColumnNamesForPromoter()])
            ->whereHas('locations', $locationQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt)
                    ->orWhereHas('employee', function ($query) use ($afterUpdatedAt): void {
                        $query->select('id')
                            ->where('updated_at', '>=', $afterUpdatedAt);
                    });
            }, function ($query) use ($employeeQueries): void {
                $query->whereHas('employee', $employeeQueries->filterByStatus());
            })
            ->where(function ($query) use ($typeId): void {
                $query->whereNull('group_id')
                    ->orWhereHas('promoterGroup', function ($query) use ($typeId): void {
                        $query->where('type_id', $typeId);
                    });
            })
            ->get();
    }

    public function getPromoterList(int $locationId, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::with(['employee:' . $employeeQueries->getColumnNamesForPromoter()])
            ->whereHas('locations', $locationQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->get();
    }

    public function getPromoterListWithLocationsForStoreManagerAPI(int $companyId, array $filterData): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::with(['employee:' . $employeeQueries->getColumnNamesForPromoter()])
            ->whereHas('locations', function ($query) use ($filterData, $locationQueries, $companyId): void {
                $query->where($locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
                    ->where('id', $filterData['location_id']);
            })
            ->when(null !== $filterData['search_text'], function ($query) use (
                $filterData,
                $employeeQueries
            ): void {
                $query->whereHas('employee', $employeeQueries->searchByFirstAndLastName($filterData['search_text']));
            })
            ->with('locations:' . $locationQueries->getNameColumnName())
            ->get();
    }

    public function getPromotersWithlocations(): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::select('id', 'monthly_sales_target')
            ->with(['locations:' . $locationQueries->getBasicColumnNames()])
            ->get();
    }

    public function getIds(): Collection
    {
        return Promoter::select('id')->orderBy('id', 'asc')->get();
    }

    public function doAllPromotersExist(array $promoterIds, int $companyId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $totalRecords = Promoter::whereIntegerInRaw('id', $promoterIds)
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->count();

        return count($promoterIds) === $totalRecords;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,employee_id,code';
    }

    public function doesCodeExist(string $promoterCode, int $companyId, ?int $promoterId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::query()
            ->when($promoterId, function ($query) use ($promoterId): void {
                $query->whereNot('id', $promoterId);
            })
            ->where('code', $promoterCode)
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->exists();
    }

    public function filterByCompany(int $companyId): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->whereHas('employee', $employeeQueries->filterByCompany($companyId));
    }

    public function getAllWithMonthlySalesAndCompanyDetailsForPeriod(
        string $startDate,
        string $endDate,
        array $promoterIds,
    ): Collection {
        $companyQueries = resolve(CompanyQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return Promoter::query()
            ->select(
                'id',
                'employee_id',
                'monthly_sales_target',
                'default_commission_amount_percentage',
                'monthly_target_commission_percentage'
            )
            ->with([
                'employee:' . $employeeQueries->getColumnNamesForAdmin(),
                'employee.company:' . $companyQueries->getPromoterColumns(),
                'saleItems' => function ($query) use ($saleItemQueries, $startDate, $endDate): void {
                    $query->select(...$saleItemQueries->getBasicColumnNamesInArray())
                        ->whereHas('sale', function ($query) use ($startDate, $endDate): void {
                            $query->where(function ($query) use ($startDate, $endDate): void {
                                $query->where(function ($query) use ($startDate, $endDate): void {
                                    $query->where('status', SaleStatus::REGULAR_SALE->value)
                                        ->where('happened_at', '>=', CommonFunctions::addStartTime($startDate))
                                        ->where('happened_at', '<=', CommonFunctions::addEndTime($endDate));
                                })->orWhere(function ($query) use ($startDate, $endDate): void {
                                    $query->where('status', SaleStatus::COMPLETE_LAYAWAY_SALE->value)
                                        ->where('layaway_completed_at', '>=', CommonFunctions::addStartTime($startDate))
                                        ->where('layaway_completed_at', '<=', CommonFunctions::addEndTime($endDate));
                                })->orWhere(function ($query) use ($startDate, $endDate): void {
                                    $query->where('status', SaleStatus::COMPLETE_CREDIT_SALE->value)
                                        ->where('credit_completed_at', '>=', CommonFunctions::addStartTime($startDate))
                                        ->where('credit_completed_at', '<=', CommonFunctions::addEndTime($endDate));
                                });
                            });
                        });
                },
                'saleItems.promoters:id',
                'saleItems.sale:' . $saleQueries->getBasicColumnNames(),
                'saleItems.sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'saleItems.sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'saleItems.product:' . $productQueries->getIdAndDepartmentIdAndBrandColumnName(),
                'saleItems.product.department:' . $departmentQueries->getBasicColumnNames(),
            ])
            ->whereIntegerInRaw('id', $promoterIds)
            ->get();
    }

    public function getPromoterCommissionReturnItemsByIdAndPeriod(
        int $promoterId,
        string $startDate,
        string $endDate,
    ): Collection {
        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);

        return SaleReturnItem::query()
            ->select('id', 'sale_return_id', 'original_sale_item_id', 'total_price_paid')
            ->with([
                'saleItem:' . $saleItemQueries->getBasicColumns(),
                'saleItem.promoterCommissionUpdate:' . $promoterCommissionUpdateQueries->getBasicColumnNames(),
            ])
            ->whereHas('saleReturn', function ($query) use ($startDate, $endDate): void {
                $query->select('id')->where('happened_at', '>=', CommonFunctions::addStartTime($startDate))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($endDate));
            })
            ->whereHas('saleItem', function ($query) use ($promoterId): void {
                $query->select('id')
                    ->whereHas('promoters', function ($query) use ($promoterId): void {
                        $query->select('id')
                            ->where('id', $promoterId);
                    });
            })
            ->get();
    }

    public function getPromotersExport(array $filterData, int $companyId): Collection
    {
        $locationQueries = new LocationQueries();
        $employeeQueries = new EmployeeQueries();

        return Promoter::query()
            ->select(
                'id',
                'employee_id',
                'monthly_sales_target',
                'code',
                'default_commission_amount_percentage',
                'monthly_target_commission_percentage',
                'group_id'
            )
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $employeeQueries,
                $locationQueries
            ): void {
                $query->where(function ($query) use ($filterData, $employeeQueries, $locationQueries): void {
                    $query->whereHas(
                        'employee',
                        $employeeQueries->searchByBasicColumns($filterData['search_text'])
                    )->orWhereHas('locations', $locationQueries->searchByName($filterData['search_text']));
                });
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when(array_key_exists('status', $filterData) && $filterData['status'], function ($query) use (
                $filterData
            ): void {
                $query->whereHas('employee', function ($query) use ($filterData): void {
                    $status = $filterData['status'] === Status::ACTIVE->value ? true : ($filterData['status'] === Status::INACTIVE->value ? false : null);
                    if (null !== $status) {
                        $query->where('status', $status);
                    }
                });
            })
            ->when($filterData['group_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('group_id', (array) $filterData['group_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getPromoterByLocations(array $locationIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getColumnNamesForPromoter()])
            ->whereHas('locations', $locationQueries->filterByIds($locationIds, LocationTypes::STORE->value))
            ->get();
    }

    public function getActivePromoterByLocations(array $locationIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getColumnNamesForPromoter()])
            ->whereHas('locations', $locationQueries->filterByIds($locationIds, LocationTypes::STORE->value))
            ->whereHas('employee', $employeeQueries->filterByStatus())
            ->get();
    }

    public function getPromoterByUsername(string $username): ?Promoter
    {
        $employeeQueries = new EmployeeQueries();
        $companyQueries = new CompanyQueries();

        return Promoter::select('id', 'employee_id', 'password')
            ->with(['employee:' . $employeeQueries->getBasicColumnNamesWithStatus()])
            ->with(['employee.company:' . $companyQueries->getBasicColumnNames()])
            ->where('username', $username)
            ->first();
    }

    public function getPromoterByPromoterGroup(array $promoterGroupIds): Collection
    {
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        return Promoter::select('id')
            ->whereHas('promoterGroup', $promoterGroupQueries->filterByIds($promoterGroupIds))
            ->get();
    }

    public function getPromoterListOfSelectedStore(int $locationId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::with([
            'employee:' . $employeeQueries->getColumnNamesForPromoter(),
            'locations:' . $locationQueries->getNameColumnName(),
        ])
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->get();
    }

    public function getActivePromoterListOfSelectedStore(int $locationId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::with([
            'employee:' . $employeeQueries->getColumnNamesForPromoter(),
            'locations:' . $locationQueries->getNameColumnName(),
        ])
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->whereHas('employee', $employeeQueries->filterByStatus())
            ->get();
    }

    public function doExistsByEmployeeId(?int $employeeId): bool
    {
        return Promoter::query()->select('id', 'employee_id')
            ->where('employee_id', $employeeId)
            ->exists();
    }

    public function doExistsByPromoterUsername(string $userName, int $companyId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::query()->select('id', 'employee_id', 'username')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->where('username', $userName)
            ->exists();
    }

    public function getForSalesByPromotersByDetailsReport(array $filterData, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $selectedColumns = [];

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, [
                'saleItems.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        } else {
            $selectedColumns = array_merge($selectedColumns, [
                'saleItems.product.department:' . $departmentQueries->getBasicColumnNames(),
                'saleItems.product.brand:' . $brandQueries->getBasicColumnNames(),
                'saleItems.product.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        }

        return Promoter::query()
            ->select('id', 'employee_id', 'group_id')
            ->with([
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'promoterGroup:' . $promoterGroupQueries->getBasicColumnNames(),
                'saleItems' => function ($query) use (
                    $saleQueries,
                    $saleReturnItemQueries,
                    $filterData,
                    $departmentQueries,
                    $categoryQueries,
                    $brandQueries
                ): void {
                    $query->select(
                        'id',
                        'sale_id',
                        'original_price_per_unit',
                        'quantity',
                        'total_discount_amount',
                        'total_tax_amount',
                        'total_price_paid',
                        'returned_quantity',
                        'price_paid_per_unit',
                        'product_id'
                    )
                        ->where(function ($query) use ($saleQueries, $saleReturnItemQueries, $filterData): void {
                            $query->whereHas('sale', $saleQueries->filterForSalesByPromotersReport($filterData))
                                ->orWhereHas(
                                    'saleReturnItems',
                                    $saleReturnItemQueries->filterForSalesByPromotersReport($filterData)
                                );
                        })
                        ->when(null !== $filterData['department_ids'], function ($query) use (
                            $departmentQueries,
                            $filterData
                        ): void {
                            $query->whereHas('product', function ($query) use (
                                $departmentQueries,
                                $filterData
                            ): void {
                                if (config('app.product_variant')) {
                                    $query->select('id', 'master_product_id')
                                        ->whereHas('masterProduct', function ($query) use (
                                            $departmentQueries,
                                            $filterData
                                        ): void {
                                            $query->select('id', 'department_id')
                                            ->whereHas(
                                                'department',
                                                $departmentQueries->filterByIds($filterData['department_ids'])
                                            );
                                        });
                                } else {
                                    $query->select('id', 'department_id')
                                    ->whereHas(
                                        'department',
                                        $departmentQueries->filterByIds($filterData['department_ids'])
                                    );
                                }
                            });
                        })
                        ->when(null !== $filterData['category_ids'], function ($query) use (
                            $categoryQueries,
                            $filterData
                        ): void {
                            $query->whereHas('product', function ($query) use (
                                $categoryQueries,
                                $filterData
                            ): void {
                                if (config('app.product_variant')) {
                                    $query->select('id', 'master_product_id')
                                        ->whereHas('masterProduct', function ($query) use (
                                            $categoryQueries,
                                            $filterData
                                        ): void {
                                            $query->select('id')
                                                ->whereHas(
                                                    'categories',
                                                    $categoryQueries->filterByIds($filterData['category_ids'])
                                                );
                                        });
                                } else {
                                    $query->select('id')
                                        ->whereHas(
                                            'categories',
                                            $categoryQueries->filterByIds($filterData['category_ids'])
                                        );
                                }
                            });
                        })
                        ->when(null !== $filterData['brand_ids'], function ($query) use (
                            $brandQueries,
                            $filterData
                        ): void {
                            if (config('app.product_variant')) {
                                $query->whereHas('product', function ($query) use (
                                    $brandQueries,
                                    $filterData
                                ): void {
                                    $query->whereHas(
                                        'masterProduct.brand',
                                        $brandQueries->filterByIds($filterData['brand_ids'])
                                    );
                                });
                            } else {
                                $query->whereHas('product.brand', $brandQueries->filterByIds($filterData['brand_ids']));
                            }
                        });
                },
                'saleItems.promoters:' . $this->getBasicColumnNames(),
                'saleItems.sale' => $saleQueries->filterForSalesByPromotersReport($filterData),
                'saleItems.sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'saleItems.sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'saleItems.saleReturnItems' => $saleReturnItemQueries->filterForSalesByPromotersReport($filterData),
                'saleItems.saleReturnItems.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNamesForSaleByPromoterReport());
                    $query->select(...$columns);
                },
                'saleItems.saleReturnItems.saleReturn.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'saleItems.saleReturnItems.saleReturn.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'saleItems.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNamesForSaleByPromoterReport());
                    $query->select(...$columns);
                },
                ...$selectedColumns,
            ])
            ->whereHas('saleItems', function ($query): void {
                $query->select('id', 'product_id')
                    ->whereHas('product', function ($query): void {
                        if (config('app.product_variant')) {
                            $query->select('id', 'master_product_id')
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_selling_item', false);
                                });
                        } else {
                            $query->select('id')
                                ->where('is_non_selling_item', false);
                        }
                    });
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->whereHas('saleItems', function ($query) use (
                $saleQueries,
                $filterData,
                $saleReturnItemQueries,
                $productQueries,
                $categoryQueries,
                $masterProductQueries,
            ): void {
                $query->select('id', 'sale_id')
                    ->where(function ($query) use ($saleQueries, $saleReturnItemQueries, $filterData): void {
                        $query->whereHas('sale', $saleQueries->filterForSalesByPromotersReport($filterData))
                            ->orWhereHas(
                                'saleReturnItems',
                                $saleReturnItemQueries->filterForSalesByPromotersReport($filterData)
                            );
                    })
                    ->when(null !== $filterData['department_ids'], function ($query) use (
                        $productQueries,
                        $masterProductQueries,
                        $filterData
                    ): void {
                        if (config('app.product_variant')) {
                            $query->whereHas('product', function ($query) use (
                                $masterProductQueries,
                                $filterData
                            ): void {
                                $query->whereHas(
                                    'masterProduct',
                                    $masterProductQueries->filterByDepartmentIds($filterData['department_ids'])
                                );
                            });
                        } else {
                            $query->whereHas(
                                'product',
                                $productQueries->filterByDepartmentIds($filterData['department_ids'])
                            );
                        }
                    })
                    ->when(null !== $filterData['category_ids'], function ($query) use (
                        $categoryQueries,
                        $filterData
                    ): void {
                        $query->whereHas('product', function ($query) use ($categoryQueries, $filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('id', 'master_product_id')
                                    ->whereHas('masterProduct', function ($query) use (
                                        $categoryQueries,
                                        $filterData
                                    ): void {
                                        $query->whereHas(
                                            'categories',
                                            $categoryQueries->filterByIds($filterData['category_ids'])
                                        );
                                    });
                            } else {
                                $query->select('id')
                                    ->whereHas(
                                        'categories',
                                        $categoryQueries->filterByIds($filterData['category_ids'])
                                    );
                            }
                        });
                    })
                    ->when(null !== $filterData['brand_ids'], function ($query) use (
                        $productQueries,
                        $masterProductQueries,
                        $filterData
                    ): void {
                        if (config('app.product_variant')) {
                            $query->whereHas('product', function ($query) use (
                                $masterProductQueries,
                                $filterData
                            ): void {
                                $query->whereHas(
                                    'masterProduct',
                                    $masterProductQueries->filterByBrandIds($filterData['brand_ids'])
                                );
                            });
                        } else {
                            $query->whereHas('product', $productQueries->filterByBrandIds($filterData['brand_ids']));
                        }
                    });
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('id', $filterData['promoter_ids']);
            })
            ->when(null !== $filterData['location_ids'], function ($query) use (
                $locationQueries,
                $filterData
            ): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when(null !== $filterData['group_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('group_id', $filterData['group_ids']);
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function changePassword(Promoter $promoter, ChangePasswordData $changePasswordData): void
    {
        $promoter->password = bcrypt($changePasswordData->new_password);
        $promoter->save();
    }

    public function updateUsername(Promoter $promoter, string $username): void
    {
        $promoter->username = $username;
        $promoter->save();
    }

    public function filterByPromoterId(int $promoterId): Closure
    {
        return fn ($query) => $query->where('id', $promoterId);
    }

    public function filterByPromoterIds(array $promoterIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('id', $promoterIds);
    }

    public function filterByGroupIds(array $groupIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('group_id', $groupIds);
    }

    public function loadLocationsWithSearch(Promoter $promoter, array $filterData): Promoter
    {
        return $promoter->load([
            'locations' => function ($query) use ($filterData): void {
                $query->select('id', 'name', 'code')
                    ->when(null !== $filterData['search_text'], function ($query) use ($filterData): void {
                        $query
                            ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                    });
            },
        ]);
    }

    public function loadEmployee(Promoter $promoter): Promoter
    {
        $promoter->refresh();
        $employeeQueries = resolve(EmployeeQueries::class);

        return $promoter->load(['employee:' . $employeeQueries->getFirstAndLastNameColumns()]);
    }

    public function getPromotersWiseSales(array $filterData, int $promoterId): LengthAwarePaginator
    {
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::query()
            ->select(
                'promoters.id',
                'promoter_sale_total.total_units_sold',
                'promoter_sale_total.total_amount_sold',
                'promoter_sale_total.happened_at',
                'promoter_sale_return_total.total_units_returned',
                'promoter_sale_return_total.total_returned_amount',
                DB::raw(
                    'SUM(COALESCE(promoter_sale_total.total_amount_sold, 0) - COALESCE(promoter_sale_return_total.total_returned_amount, 0)) as total_amount'
                )
            )

            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(si.quantity) as total_units_sold'),
                        DB::raw('DATE(sales.happened_at) as happened_at'),
                        DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold')
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select(
                                'id',
                                'quantity',
                                'total_price_paid',
                                'total_discount_amount',
                                'total_tax_amount',
                                'product_id',
                                'sale_id',
                                'promoters_per_sale_item',
                                'is_exchange'
                            )
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('sales', 'sales.id', '=', 'si.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->where('counters.location_id', $filterData['location_id'])
                    ->where('sales.happened_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
                    ->where('sales.happened_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->groupBy('sale_item_promoter.promoter_id', DB::raw('DATE(sales.happened_at)')),
                'promoter_sale_total',
                'promoter_sale_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(sri.quantity) as total_units_returned'),
                        DB::raw('DATE(sr.happened_at) as happened_at'),
                        DB::raw('SUM(sri.total_price_paid / si.promoters_per_sale_item) as total_returned_amount')
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select('id', 'product_id', 'sale_id', 'promoters_per_sale_item', 'is_exchange')
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('sale_return_items as sri', 'sri.original_sale_item_id', '=', 'si.id')
                    ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sr.counter_update_id')
                    ->leftJoin('sale_items as exi', 'exi.sale_return_item_id', '=', 'sri.id')
                    ->leftJoin('sales as exs', 'exs.id', '=', 'sr.original_sale_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->where('counters.location_id', $filterData['location_id'])
                    ->where('sr.happened_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
                    ->where('sr.happened_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
                    ->groupBy('sale_item_promoter.promoter_id', DB::raw('DATE(sr.happened_at)')),
                'promoter_sale_return_total',
                function ($join): void {
                    $join
                        ->on('promoter_sale_return_total.promoter_id', '=', 'promoters.id')
                        ->on(
                            DB::raw('DATE(promoter_sale_total.happened_at)'),
                            '=',
                            DB::raw('DATE(promoter_sale_return_total.happened_at)')
                        );
                }
            )
            ->where(function ($query): void {
                $query
                    ->whereNotNull('promoter_sale_total.total_units_sold')
                    ->orWhereNotNull('promoter_sale_total.total_amount_sold')
                    ->orWhereNotNull('promoter_sale_return_total.total_units_returned')
                    ->orWhereNotNull('promoter_sale_return_total.total_returned_amount');
            })
            ->where('id', $promoterId)
            ->whereHas(
                'locations',
                $locationQueries->filterById((int) $filterData['location_id'], LocationTypes::STORE->value)
            )
            ->when(
                $filterData['sort_by'],
                function ($query) use ($filterData): void {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                },
                function ($query): void {
                    $query->orderBy('id', 'desc');
                }
            )
            ->orderBy('happened_at')
            ->groupBy('happened_at')
            ->paginate($filterData['per_page']);
    }

    public function getItemSoldCountForTheGivenPromoter(array $date, int $locationId, int $promoterId): ?Promoter
    {
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::query()
            ->select(
                'promoters.id',
                'promoter_sale_total.total_units_sold',
                'promoter_sale_return_total.total_units_returned',
                'promoter_sale_return_total.total_amount_return',
                DB::raw('
                    COALESCE(promoter_sale_total.total_amount_sold, 0)
                    - COALESCE(promoter_sale_return_total.total_amount_return, 0) as net_sales
                '),
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(si.quantity) as total_units_sold'),
                        DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select(
                                'id',
                                'quantity',
                                'total_price_paid',
                                'total_discount_amount',
                                'total_tax_amount',
                                'product_id',
                                'sale_id',
                                'promoters_per_sale_item',
                                'is_exchange'
                            )
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('sales', 'sales.id', '=', 'si.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->where('counters.location_id', $locationId)
                    ->where('sales.happened_at', '>=', CommonFunctions::addStartTime($date[0]))
                    ->where('sales.happened_at', '<=', CommonFunctions::addEndTime($date[1]))
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_total',
                'promoter_sale_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(sri.quantity) as total_units_returned'),
                        DB::raw('SUM(sri.total_price_paid / si.promoters_per_sale_item) as total_amount_return'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select('id', 'product_id', 'sale_id', 'promoters_per_sale_item', 'is_exchange')
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('sale_return_items as sri', 'sri.original_sale_item_id', '=', 'si.id')
                    ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sr.counter_update_id')
                    ->leftJoin('sale_items as exi', 'exi.sale_return_item_id', '=', 'sri.id')
                    ->leftJoin('sales as exs', 'exs.id', '=', 'sr.original_sale_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->where('counters.location_id', $locationId)
                    ->where('sr.happened_at', '>=', CommonFunctions::addStartTime($date[0]))
                    ->where('sr.happened_at', '<=', CommonFunctions::addEndTime($date[1]))
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_return_total',
                function ($join): void {
                    $join
                        ->on('promoter_sale_return_total.promoter_id', '=', 'promoters.id');
                }
            )
            ->where(function ($query): void {
                $query
                    ->whereNotNull('promoter_sale_total.total_units_sold')
                    ->orWhereNotNull('promoter_sale_return_total.total_units_returned');
            })
            ->where('id', $promoterId)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->first();
    }

    public function getAllPromoterByCompany(int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getFirstAndLastNameColumns()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->get();
    }

    public function getPromoterByIds(array $promoterIds, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getFirstAndLastNameColumns()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->whereIntegerInRaw('id', $promoterIds)
            ->get();
    }

    public function getPromotersOfStaffIds(array $staffIds, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Promoter::select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getFirstAndLastNameColumns()])
            ->whereHas('employee', function ($query) use ($staffIds, $companyId): void {
                $query->where('company_id', $companyId)
                    ->whereIntegerInRaw('staff_id', $staffIds);
            })
            ->get();
    }

    public function getTotalAmountForSalePromoterTarget(
        string $startDate,
        string $endDate,
        array $promoterIds,
    ): Collection {
        return Promoter::query()
            ->select(
                'promoters.id',
                DB::raw(
                    '(IFNULL(promoter_sale_total.total_amount_sold, 0) - IFNULL(promoter_sale_return_total.total_returned_amount, 0)) as amount_sold'
                ),
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select('id', 'sale_id', 'total_price_paid', 'promoters_per_sale_item')
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('sales', 'sales.id', '=', 'si.sale_id')
                    ->where('sales.happened_at', '>=', CommonFunctions::addStartTime($startDate))
                    ->where('sales.happened_at', '<=', CommonFunctions::addEndTime($endDate))
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_total',
                'promoter_sale_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(sri.total_price_paid / si.promoters_per_sale_item) as total_returned_amount'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select('id', 'promoters_per_sale_item')
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('sale_return_items as sri', 'sri.original_sale_item_id', '=', 'si.id')
                    ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->where('sr.happened_at', '>=', $startDate)
                    ->where('sr.happened_at', '<=', $endDate)
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_return_total',
                'promoter_sale_return_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->where(function ($query): void {
                $query->whereNotNull('promoter_sale_total.total_amount_sold')
                    ->orWhereNotNull('promoter_sale_return_total.total_returned_amount');
            })
            ->whereIntegerInRaw('id', $promoterIds)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getPromoterCount(int $locationId): int
    {
        $locationQueries = resolve(LocationQueries::class);

        return Promoter::query()
            ->whereHas('employee', function ($query): void {
                $query->select('id', 'status')
                    ->where('status', true);
            })
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->count();
    }

    public function filterById(int $promoterId): Closure
    {
        return fn ($query) => $query->select('id')->where('id', $promoterId);
    }

    public function getEmployeeWithRelation(): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function getPromoterById(int $promoterId, int $companyId, bool $status): ?Promoter
    {
        return Promoter::select('id', 'employee_id')
            ->withWhereHas('employee', function ($query) use ($companyId, $status): void {
                $query->select('id', 'status')
                    ->where('company_id', $companyId)
                    ->whereNot('status', $status);
            })
            ->find($promoterId);
    }

    private function getSalesByPromotersQuery(array $filterData, int $companyId): Builder
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterGroupQueries = new PromoterGroupQueries();

        $filterData['sales_filter_types'] = array_map('intval', $filterData['sales_filter_types']);

        return Promoter::query()
            ->with([
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'locations:' . $locationQueries->getBasicColumnNames(),
                'promoterGroup:' . $promoterGroupQueries->getBasicColumnNames(),
            ])
            ->select(
                'promoters.id',
                'promoters.employee_id',
                'promoters.group_id',
                'promoter_sale_total.total_units_sold',
                'promoter_sale_total.total_sales',
                'promoter_sale_total.total_amount_sold',
                'promoter_sale_total.total_discount_amount',
                'promoter_sale_total.total_tax_amount',
                'promoter_sale_return_total.total_units_returned',
                'promoter_sale_return_total.total_return_sales',
                'promoter_sale_return_total.total_returned_amount'
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(si.quantity) as total_units_sold'),
                        DB::raw('count(DISTINCT sales.id) as total_sales'),
                        DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold'),
                        DB::raw('SUM(si.total_discount_amount / si.promoters_per_sale_item) as total_discount_amount'),
                        DB::raw('SUM(si.total_tax_amount / si.promoters_per_sale_item) as total_tax_amount'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select(
                                'id',
                                'quantity',
                                'total_price_paid',
                                'total_discount_amount',
                                'total_tax_amount',
                                'product_id',
                                'sale_id',
                                'promoters_per_sale_item',
                                'is_exchange'
                            )
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('master_products as mp', 'mp.id', '=', 'p.master_product_id')
                    ->leftJoin('sales', 'sales.id', '=', 'si.sale_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.department_id', $filterData['department_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.department_id', $filterData['department_ids']);
                        }
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sales.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sales.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::VOID_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues());
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::PENDING_LAYAWAY_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.layaway_pending_amount');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::COMPLETED_LAYAWAY_SALES->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.layaway_completed_at');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::PENDING_CREDIT_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.credit_pending_amount');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::COMPLETE_CREDIT_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->whereNull('sales.credit_completed_at');
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::RETURN_WITH_NEW_SALE->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->where(function ($query): void {
                                $query->whereNull('sales.sale_return_id')
                                    ->orWhere('si.is_exchange', true);
                            });
                        }
                    )
                    ->when(
                        in_array(
                            SalesByPromoterReportExcludeTypes::EXCHANGE_SALES->value,
                            $filterData['sales_filter_types'],
                            true
                        ),
                        function ($query): void {
                            $query->where('si.is_exchange', false);
                        }
                    )
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_total',
                'promoter_sale_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->leftJoinSub(
                DB::table('sale_item_promoter')
                    ->select(
                        'sale_item_promoter.promoter_id',
                        DB::raw('SUM(sri.quantity) as total_units_returned'),
                        DB::raw('count(DISTINCT sr.id) as total_return_sales'),
                        DB::raw('SUM(sri.total_price_paid / si.promoters_per_sale_item) as total_returned_amount'),
                    )
                    ->leftJoinSub(
                        DB::table('sale_items')
                            ->select('id', 'product_id', 'sale_id', 'promoters_per_sale_item', 'is_exchange')
                            ->leftJoinSub(
                                DB::table('sale_item_promoter')
                                    ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                                    ->groupBy('sale_item_id'),
                                'sip',
                                'sale_items.id',
                                '=',
                                'sip.sale_item_id'
                            ),
                        'si',
                        'si.id',
                        '=',
                        'sale_item_promoter.sale_item_id'
                    )
                    ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
                    ->leftJoin('master_products as mp', 'mp.id', '=', 'p.master_product_id')
                    ->leftJoin('sale_return_items as sri', 'sri.original_sale_item_id', '=', 'si.id')
                    ->leftJoin('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sr.counter_update_id')
                    ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('counters.location_id', $filterData['location_ids']);
                    })
                    ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.brand_id', $filterData['brand_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.brand_id', $filterData['brand_ids']);
                        }
                    })
                    ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->whereIntegerInRaw('mp.department_id', $filterData['department_ids']);
                        } else {
                            $query->whereIntegerInRaw('p.department_id', $filterData['department_ids']);
                        }
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('sr.happened_at', '>=', $filterData['date_range'][0])
                            ->where('sr.happened_at', '<=', $filterData['date_range'][1]);
                    })
                    ->where(function ($query) use ($filterData): void {
                        $query->where(function ($query) use ($filterData): void {
                            $query->when(
                                in_array(
                                    SalesByPromoterReportExcludeTypes::EXCHANGE_SALES->value,
                                    $filterData['sales_filter_types'],
                                    true
                                ),
                                function ($query): void {
                                    $query->whereIn('sri.id', function ($query): void {
                                        $query->select('sale_return_item_id')
                                            ->from('sale_items')
                                            ->where('is_exchange', 1)
                                            ->whereNotNull('sale_return_item_id');
                                    });
                                }
                            )->when(
                                in_array(
                                    SalesByPromoterReportExcludeTypes::RETURN_WITH_NEW_SALE->value,
                                    $filterData['sales_filter_types'],
                                    true
                                ),
                                function ($query): void {
                                    $query->whereNotIn('sri.id', function ($query): void {
                                        $query->select('sale_return_item_id')
                                            ->from('sale_items')
                                            ->where('is_exchange', 1)
                                            ->whereNotNull('sale_return_item_id');
                                    });
                                }
                            );
                        });
                    })
                    ->groupBy('sale_item_promoter.promoter_id'),
                'promoter_sale_return_total',
                'promoter_sale_return_total.promoter_id',
                '=',
                'promoters.id'
            )
            ->where(function ($query): void {
                $query->whereNotNull('promoter_sale_total.total_units_sold')
                    ->orWhereNotNull('promoter_sale_total.total_amount_sold')
                    ->orWhereNotNull('promoter_sale_total.total_discount_amount')
                    ->orWhereNotNull('promoter_sale_total.total_tax_amount')
                    ->orWhereNotNull('promoter_sale_return_total.total_units_returned')
                    ->orWhereNotNull('promoter_sale_return_total.total_returned_amount');
            })
            ->when($filterData['search_text'], function ($query) use ($employeeQueries, $filterData): void {
                $query->whereHas('employee', $employeeQueries->searchByFirstAndLastName($filterData['search_text']));
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['promoter_id'], function ($query) use ($filterData): void {
                $query->where('id', (int) $filterData['promoter_id']);
            })
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when($filterData['group_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('group_id', (array) $filterData['group_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getTokenById(int $promoterId): ?Promoter
    {
        return Promoter::query()
            ->select('id', 'fcm_token')
            ->whereNotNull('fcm_token')
            ->find($promoterId);
    }

    public function getPromoterForBulkUpdate(int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        return Promoter::select(
            'id',
            'code',
            'username',
            'group_id',
            'employee_id',
            'monthly_sales_target',
            'default_commission_amount_percentage',
            'monthly_target_commission_percentage'
        )
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'promoterGroup:' . $promoterGroupQueries->getBasicColumnNames(),
            ])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->orderBy('id', 'desc')
            ->get();
    }

    public function updateByEmployeeId(array $promoterData, int $employeeId, int $companyId): void
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $locationIds = $promoterData['location_ids'];
        unset(
            $promoterData['location_ids'],
            $promoterData['password'],
        );

        $promoter = Promoter::select('id', 'employee_id')->where('employee_id', $employeeId)
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->first();

        if ($promoter instanceof Promoter) {
            $promoter->update($promoterData);
            $promoter->locations()->sync($locationIds);
        }
    }

    public function usernameTakenByAnotherPromoter(string $username, string $mobileNumber, int $companyId): bool
    {
        return Promoter::whereCaseSensitive('username', $username)
            ->whereHas('employee', function ($query) use ($mobileNumber, $companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->whereNot('mobile_number', $mobileNumber);
            })
            ->exists();
    }

    public function createToken(Promoter $promoter): string
    {
        return $promoter->createToken('promoter_app', ['promoter_scope'])->plainTextToken;
    }

    public function getByIdsWithName(array $promoterIds): string
    {
        $promoterNames = [];
        $promoter = Promoter::select('id', 'employee_id')
            ->with('employee')
            ->whereIntegerInRaw('id', values: $promoterIds)
            ->get();

        if ($promoter->isNotEmpty()) {
            foreach ($promoter as $promoterData) {
                if ($promoterData->employee) {
                    $promoterNames[] = $promoterData->employee->getFullName();
                }
            }
        }

        return implode(', ', $promoterNames);
    }

    public function getTopSellingPromoter(
        int $companyId,
        int $targetId,
        array $dateRange,
        array $saleTargetIds
    ): Collection {
        return Promoter::select(
            'promoters.id',
            'employees.first_name',
            'employees.last_name',
            DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold')
        )
            ->join('employees', 'promoters.employee_id', '=', 'employees.id')
            ->join('sale_targets', 'employees.company_id', '=', 'sale_targets.company_id')
            ->join('sale_item_promoter as sip', 'promoters.id', '=', 'sip.promoter_id')
            ->joinSub(
                DB::table('sale_items')
                    ->select('id', 'sale_id', 'total_price_paid', 'promoters_per_sale_item')
                    ->leftJoinSub(
                        DB::table('sale_item_promoter')
                            ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                            ->groupBy('sale_item_id'),
                        'sip_inner',
                        'sale_items.id',
                        '=',
                        'sip_inner.sale_item_id'
                    ),
                'si',
                'sip.sale_item_id',
                '=',
                'si.id'
            )
            ->join('sales', 'si.sale_id', '=', 'sales.id')
            ->with('locations:id,name')
            ->where('employees.company_id', $companyId)
            ->when($targetId && 0 != $targetId && [] === $saleTargetIds, function ($query) use ($targetId): void {
                $query->where('sale_targets.id', $targetId);
            })
            ->when([] !== $saleTargetIds && ! $targetId || 0 === $targetId, function ($query) use (
                $saleTargetIds
            ): void {
                $query->whereIn('sale_targets.id', $saleTargetIds);
            })
            ->whereBetween('sales.happened_at', [$dateRange[0], $dateRange[1]])
            ->groupBy('promoters.id', 'employees.first_name', 'employees.last_name')
            ->orderBy('total_amount_sold', 'desc')
            ->limit(10)
            ->get();
    }

    public function getWorstSellingPromoter(
        int $companyId,
        int $targetId,
        array $dateRange,
        array $saleTargetIds
    ): Collection {
        return Promoter::query()
            ->select(
                'promoters.id',
                'employees.first_name',
                'employees.last_name',
                DB::raw('SUM(si.total_price_paid / si.promoters_per_sale_item) as total_amount_sold')
            )
            ->join('employees', 'promoters.employee_id', '=', 'employees.id')
            ->join('sale_targets', 'employees.company_id', '=', 'sale_targets.company_id')
            ->join('sale_item_promoter as sip', 'promoters.id', '=', 'sip.promoter_id')
            ->joinSub(
                DB::table('sale_items')
                    ->select('id', 'sale_id', 'total_price_paid', 'promoters_per_sale_item')
                    ->leftJoinSub(
                        DB::table('sale_item_promoter')
                            ->select(DB::raw('count(*) as promoters_per_sale_item'), 'sale_item_id')
                            ->groupBy('sale_item_id'),
                        'sip_inner',
                        'sale_items.id',
                        '=',
                        'sip_inner.sale_item_id'
                    ),
                'si',
                'sip.sale_item_id',
                '=',
                'si.id'
            )
            ->join('sales', 'si.sale_id', '=', 'sales.id')
            ->with('locations:id,name')
            ->where('employees.company_id', $companyId)
            ->when($targetId && 0 != $targetId && [] === $saleTargetIds, function ($query) use ($targetId): void {
                $query->where('sale_targets.id', $targetId);
            })
            ->when([] !== $saleTargetIds && ! $targetId || 0 === $targetId, function ($query) use (
                $saleTargetIds
            ): void {
                $query->whereIn('sale_targets.id', $saleTargetIds);
            })
            ->whereBetween('sales.happened_at', [$dateRange[0], $dateRange[1]])
            ->groupBy('promoters.id', 'employees.first_name', 'employees.last_name')
            ->orderBy('total_amount_sold', 'asc')
            ->limit(10)
            ->get();
    }
}
