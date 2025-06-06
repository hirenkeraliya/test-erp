<?php

declare(strict_types=1);

namespace App\Domains\Activity;

use App\CommonFunctions;
use App\Domains\Admin\AdminQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Member;
use App\Models\Promoter;
use App\Models\SaleChannel;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogQueries
{
    public function getPaginatedActivityList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getActivityList($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getActivitiesForExport(array $filterData, int $companyId): Collection
    {
        return $this->getActivityList($filterData, $companyId)->get();
    }

    public function getActivitiesWithRelationsForPrint(array $filterData, int $companyId): Collection
    {
        return $this->getActivityList($filterData, $companyId)->get();
    }

    private function getActivityList(array $filterData, int $companyId): Builder
    {
        $adminQueries = resolve(AdminQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        return Activity::query()
            ->select(
                'id',
                'subject_type',
                'event',
                'causer_type',
                'causer_id',
                'parent_module_name',
                'notes',
                'created_at'
            )
            ->with([
                'causer' => function (MorphTo $morphTo) use (
                    $adminQueries,
                    $promoterQueries,
                    $storeManagerQueries,
                    $warehouseManagerQueries,
                    $cashierQueries
                ): void {
                    $morphTo->constrain([
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                        Promoter::class => $promoterQueries->getEmployeeWithRelation(),
                        StoreManager::class => $storeManagerQueries->getEmployeeWithRelation(),
                        WarehouseManager::class => $warehouseManagerQueries->getEmployeeWithRelation(),
                        Cashier::class => $cashierQueries->getEmployeeWithRelation(),
                    ]);
                },
            ])
            ->whereHas('causer', function ($query) use ($companyId): void {
                if ($query->getModel() instanceof SuperAdmin) {
                    return;
                }

                if ($query->getModel() instanceof SaleChannel) {
                    return;
                }

                if ($query->getModel() instanceof Member) {
                    return;
                }

                $query->whereHas('employee', function ($query) use ($companyId): void {
                    $query->select('id', 'first_name', 'last_name', 'staff_id')
                        ->where('company_id', $companyId);
                });
            })
            ->whereNotNull('causer_type')
            ->whereNotNull('causer_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(
                        ['subject_type', 'causer_type'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    )
                        ->orWhereHas('causer', function ($query) use ($filterData): void {
                            if ($query->getModel() instanceof SuperAdmin) {
                                return;
                            }

                            if ($query->getModel() instanceof SaleChannel) {
                                return;
                            }

                            if ($query->getModel() instanceof Member) {
                                return;
                            }

                            $query->whereHas('employee', function ($query) use ($filterData): void {
                                $query->whereAny(
                                    ['first_name', 'staff_id'],
                                    'LIKE',
                                    '%' . $filterData['search_text'] . '%'
                                );
                            });
                        });
                });
            })
            ->when(
                (int) $filterData['module_type'] === ModelMappingTypes::BASE_MODULES->value,
                function ($query): void {
                    $query->where(function ($query): void {
                        $query->whereIn('subject_type', array_keys(ModelMapping::getParentChildModules()))
                            ->orWhereIn('parent_module_name', array_keys(ModelMapping::getParentChildModules()));
                    });
                },
                function ($query): void {
                    $query->whereNotIn('subject_type', array_keys(ModelMapping::getParentChildModules()));
                }
            )
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('causer', function ($query) use ($filterData): void {
                    if ($query->getModel() instanceof SuperAdmin) {
                        return;
                    }

                    if ($query->getModel() instanceof SaleChannel) {
                        return;
                    }

                    if ($query->getModel() instanceof Member) {
                        return;
                    }

                    $query->where('employee_id', $filterData['employee_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
