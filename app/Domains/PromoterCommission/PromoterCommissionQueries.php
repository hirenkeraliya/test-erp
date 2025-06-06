<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission;

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Models\PromoterCommission;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;

class PromoterCommissionQueries
{
    public function addNew(array $promoterCommissionDetails): PromoterCommission
    {
        return PromoterCommission::create($promoterCommissionDetails);
    }

    public function entryExistsForPeriod(string $date): bool
    {
        return PromoterCommission::where('commission_date', $date)->exists();
    }

    public function getIdsByPeriod(string $date): Collection
    {
        return PromoterCommission::select('id')->where('commission_date', $date)->get();
    }

    public function deleteByPeriod(string $date): void
    {
        PromoterCommission::where('commission_date', $date)->delete();
    }

    public function updateCommissionAmount(
        PromoterCommission $promoterCommission,
        float $totalCommissionAmount,
        float $commissionAmountRounding,
        float $totalReturnAmount,
        float $totalReturnAmountRounding,
        float $totalAmount,
        float $totalAmountRounding
    ): void {
        $promoterCommission->commission_amount = $totalCommissionAmount;
        $promoterCommission->commission_amount_rounding = $commissionAmountRounding;
        $promoterCommission->total_sales_amount = $totalAmount;
        $promoterCommission->total_sales_amount_rounding = $totalAmountRounding;
        $promoterCommission->total_return_sales_amount -= $totalReturnAmount;
        $promoterCommission->total_return_sales_amount_rounding = $totalReturnAmountRounding;
        $promoterCommission->save();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,promoter_id,commission_amount,total_sales_amount,total_return_sales_amount,monthly_sales_target,commission_date,commission_amount_rounding,total_sales_amount_rounding,total_return_sales_amount_rounding';
    }

    public function getPaginatedCommissionByPromotersForMonth(array $filterData, int $companyId): array
    {
        $promoterCommissionQuery = $this->getCommissionByPromotersQuery($filterData, $companyId);
        $promoterCommissionRecords = $promoterCommissionQuery->get();

        return [
            $promoterCommissionQuery->paginate($filterData['per_page']),
            $promoterCommissionRecords->sum('promoter_commission_updates_sum_amount'),
            $promoterCommissionRecords->sum('promoter_commission_updates_sum_commission_amount'),
        ];
    }

    public function getPaginatedCommissionByPromotersForMonthForExport(array $filterData, int $companyId): Collection
    {
        return $this->getCommissionByPromotersQuery($filterData, $companyId)->get();
    }

    public function getCommissionByPromotersQuery(array $filterData, int $companyId): mixed
    {
        /** @var PromoterQueries $promoterQueries */
        $promoterQueries = resolve(PromoterQueries::class);

        /** @var EmployeeQueries $employeeQueries */
        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var LocationQueries $locationQueries */
        $locationQueries = resolve(LocationQueries::class);

        $designationQueries = resolve(DesignationQueries::class);

        return PromoterCommission::query()
            ->select(explode(',', $this->getBasicColumnNames()))
            ->with([
                'promoter:' . $promoterQueries->getBasicColumnNames(),
                'promoter.employee:' . $employeeQueries->getBasicColumnNames(),
                'promoter.employee.designation:' . $designationQueries->getBasicColumnNames(),
                'promoter.locations:' . $locationQueries->getBasicColumnNames(),
                'promoterCommissionUpdates' => function ($query) use ($filterData): void {
                    $query->select('promoter_commission_id', 'amount', 'commission_amount')
                        ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                        })
                        ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        })
                        ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                },
            ])
            ->withSum([
                'promoterCommissionUpdates' => function ($query) use ($filterData): void {
                    $query->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                    })->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                    })->when($filterData['department_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    });
                },
            ], 'amount')
            ->withSum([
                'promoterCommissionUpdates' => function ($query) use ($filterData): void {
                    $query->when($filterData['location_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                    })->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                    })->when($filterData['department_ids'], function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    });
                },
            ], 'commission_amount')
            ->whereHas('promoter', function ($query) use ($employeeQueries, $companyId): void {
                $query->select('id', 'employee_id')
                    ->whereHas('employee', $employeeQueries->filterByCompany($companyId));
            })
            ->when($filterData['search_text'], function ($query) use ($employeeQueries, $filterData): void {
                $query->whereHas('promoter', function ($query) use ($employeeQueries, $filterData): void {
                    $query->select('id', 'employee_id')
                        ->whereHas('employee', $employeeQueries->searchByFirstAndLastName($filterData['search_text']));
                });
            })
            ->when(
                $filterData['location_ids'] || $filterData['brand_ids'] || $filterData['department_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIn('id', function ($query) use ($filterData): void {
                        $query->select('promoter_commission_id')
                        ->from('promoter_commission_updates')
                        ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
                        })
                        ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        })
                        ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                    });
                }
            )
            ->when($filterData['month_range'], function ($query) use ($filterData): void {
                /** @var Carbon $date */
                $date = Carbon::createFromFormat(
                    'Y-m',
                    $filterData['month_range'][1] . '-' . $filterData['month_range'][0]
                );
                $query->where('commission_date', '>=', $date->startOfMonth()->format('Y-m-d'))
                    ->where('commission_date', '<=', $date->endOfMonth()->format('Y-m-d'));
            })
            ->when($filterData['promoter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('promoter_id', $filterData['promoter_ids']);
            })
           ->when($filterData['group_ids'], function ($query) use ($filterData, $promoterQueries): void {
               $query->whereHas('promoter', $promoterQueries->filterByGroupIds((array) $filterData['group_ids']));
           })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function filterByCommissionDate(array $date): Closure
    {
        /** @var Carbon $date */
        $date = Carbon::createFromFormat('Y-m-d', $date[1] . '-' . $date[0] . '-01');

        return fn ($query) => $query->where('commission_date', $date->format('Y-m-d'));
    }
}
