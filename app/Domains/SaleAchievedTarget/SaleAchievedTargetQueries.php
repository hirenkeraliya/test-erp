<?php

declare(strict_types=1);

namespace App\Domains\SaleAchievedTarget;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\SaleAchievedTarget;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SaleAchievedTargetQueries
{
    public function addNew(array $saleAchievedTargetData): void
    {
        SaleAchievedTarget::create($saleAchievedTargetData);
    }

    public function updateAchievedValue(SaleAchievedTarget $saleAchievedTarget, array $saleAchievedTargetDate): void
    {
        $saleAchievedTarget->achieved_value = $saleAchievedTargetDate['achieved_value'];
        $saleAchievedTarget->save();
    }

    public function refresh(SaleAchievedTarget $saleAchievedTarget): SaleAchievedTarget
    {
        return $saleAchievedTarget->refresh();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_target_timeframe_id,targetable_id,targetable_type,target_value,achieved_value';
    }

    public function getBasicColumnNamesForSaleTarget(): string
    {
        return 'id,sale_target_timeframe_id';
    }

    public function getPaginatedSaleTargetAchievedList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getSaleTargetAchievedList($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getSaleAchievedTargetForExport(array $filterData, int $companyId): Collection
    {
        return $this->getSaleTargetAchievedList($filterData, $companyId)->get();
    }

    public function getPaginatedSaleTargetAchievedListForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getSaleTargetAchievedListForStoreManager($filterData, $locationId, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function getSaleAchievedTargetExportForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId
    ): Collection {
        return $this->getSaleTargetAchievedListForStoreManager($filterData, $locationId, $companyId)->get();
    }

    public function getColumnsFilterBySaleTargetId(int $salesTargetId): Closure
    {
        return fn ($query) => $query->select(
            'sale_target_timeframe_id',
            'targetable_id',
            'targetable_type',
            'target_value',
            'achieved_value'
        )->whereHas(
            'saleTargetTimeframe',
            function ($query) use ($salesTargetId): void {
                $query->where('sale_target_id', $salesTargetId);
            }
        );
    }

    public function deleteBySaleTarget(int $saleTargetId): void
    {
        SaleAchievedTarget::query()
            ->select('id')
            ->whereHas(
                'saleTargetTimeframe',
                function ($query) use ($saleTargetId): void {
                    $query->where('sale_target_id', $saleTargetId);
                }
            )
            ->delete();
    }

    public function getByIdWithSaleTargetAndTimeframe(int $saleAchievedTargetId, int $companyId): SaleAchievedTarget
    {
        $saleTargetTimeframeQueries = new SaleTargetTimeframeQueries();
        $saleTargetQueries = new SaleTargetQueries();
        $promoterQueries = new PromoterQueries();
        $locationQueries = new LocationQueries();

        return SaleAchievedTarget::query()
            ->select('id', 'sale_target_timeframe_id')
            ->withWhereHas('saleTargetTimeframe', function ($query) use (
                $saleTargetTimeframeQueries,
                $saleTargetQueries,
                $promoterQueries,
                $locationQueries,
                $companyId
            ): void {
                $query->select(...explode(',', $saleTargetTimeframeQueries->getBasicColumnNames()))
                    ->with([
                        'saleTarget:' . $saleTargetQueries->getBasicColumnNames(),
                        'saleTarget.promoters:' . $promoterQueries->getBasicColumnNames(),
                        'saleTarget.locations:' . $locationQueries->getNameColumnName(),
                    ])
                    ->where($saleTargetTimeframeQueries->filterByCompany($companyId));
            })
            ->findOrFail($saleAchievedTargetId);
    }

    private function getSaleTargetAchievedList(array $filterData, int $companyId): Builder
    {
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleTargetQueries = new SaleTargetQueries();

        return SaleAchievedTarget::query()
            ->select(
                'id',
                'sale_target_timeframe_id',
                'targetable_id',
                'targetable_type',
                'target_value',
                'achieved_value',
            )
            ->with([
                'saleTargetTimeframe:' . $saleTargetTimeframeQueries->getBasicColumnNames(),
                'saleTargetTimeframe.saleTarget:' . $saleTargetQueries->getBasicColumnNames(),
                'targetable' => function (MorphTo $morphTo) use ($employeeQueries): void {
                    $morphTo->constrain([
                        Promoter::class => function ($query) use ($employeeQueries): void {
                            $query->select('id', 'employee_id')
                                ->with(['employee:' . $employeeQueries->getFirstAndLastNameColumns()]);
                        },
                        Location::class => function ($query): void {
                            $query->select('id', 'name');
                        },
                        Company::class => function ($query): void {
                            $query->select('id', 'name');
                        },
                    ]);
                },
            ])
            ->whereHas('saleTargetTimeframe', function ($query) use (
                $companyId,
                $filterData,
                $saleTargetTimeframeQueries
            ): void {
                $query->where($saleTargetTimeframeQueries->filterByCompany($companyId))
                    ->whereHas('saleTarget', function ($query) use ($filterData): void {
                        $query->when(0 !== $filterData['time_interval_type'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where('time_interval_type', $filterData['time_interval_type']);
                        });
                    })
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::DAILY->value && [] !== $filterData['date_range'],
                        function ($query) use ($filterData): void {
                            $query->where('start_date', '>=', $filterData['date_range'][0])
                                ->where('start_date', '<=', $filterData['date_range'][1]);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::CUSTOM_PERIOD->value && [] !== $filterData['date_range'],
                        function ($query) use ($filterData): void {
                            $query->where('start_date', '>=', $filterData['date_range'][0])
                                ->where('start_date', '<=', $filterData['date_range'][1]);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::YEARLY->value && '' !== $filterData['year'],
                        function ($query) use ($filterData): void {
                            /** @var Carbon $date */
                            $date = Carbon::create($filterData['year']);
                            $startOfYear = $date->startOfYear()->format('Y-m-d');
                            $endOfYear = $date->endOfYear()->format('Y-m-d');

                            $query->where('start_date', '>=', $startOfYear)
                                ->where('start_date', '<=', $endOfYear);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::MONTHLY->value && [] !== $filterData['month'],
                        function ($query) use ($filterData): void {
                            /** @var Carbon $date */
                            $date = Carbon::create($filterData['month']['year'], $filterData['month']['month'] + 1);
                            $startOfMonth = $date->startOfMonth()->format('Y-m-d');
                            $endOfMonth = $date->endOfMonth()->format('Y-m-d');

                            $query->where('start_date', '>=', $startOfMonth)
                                ->where('start_date', '<=', $endOfMonth);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::WEEKLY->value && [] !== $filterData['week'],
                        function ($query) use ($filterData): void {
                            $query->where('start_date', '>=', $filterData['week'][0])
                                ->where('start_date', '<=', $filterData['week'][1]);
                        }
                    );
            })
            ->when($filterData['target_type'], function ($query) use ($filterData): void {
                $query->whereHas('saleTargetTimeframe.saleTarget', function ($query) use ($filterData): void {
                    $query->where('target_type', $filterData['target_type']);
                });
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['target_value', 'achieved_value'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('targetable_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['promoter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('targetable_id', (array) $filterData['promoter_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getSaleTargetAchievedListForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId
    ): Builder {
        $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleTargetQueries = new SaleTargetQueries();

        return SaleAchievedTarget::query()
            ->select(
                'id',
                'sale_target_timeframe_id',
                'targetable_id',
                'targetable_type',
                'target_value',
                'achieved_value',
            )
            ->with([
                'saleTargetTimeframe:' . $saleTargetTimeframeQueries->getBasicColumnNames(),
                'saleTargetTimeframe.saleTarget:' . $saleTargetQueries->getBasicColumnNames(),
                'targetable' => function (MorphTo $morphTo) use ($employeeQueries): void {
                    $morphTo->constrain([
                        Promoter::class => function ($query) use ($employeeQueries): void {
                            $query->select('id', 'employee_id')
                                ->with(['employee:' . $employeeQueries->getFirstAndLastNameColumns()]);
                        },
                        Location::class => function ($query): void {
                            $query->select('id', 'name');
                        },
                    ]);
                },
            ])
            ->whereHas('saleTargetTimeframe', function ($query) use ($filterData): void {
                $query->whereHas('saleTarget', function ($query) use ($filterData): void {
                    $query->when(0 !== $filterData['time_interval_type'], function ($query) use ($filterData): void {
                        $query->where('time_interval_type', $filterData['time_interval_type']);
                    });
                })
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::DAILY->value && [] !== $filterData['date_range'],
                        function ($query) use ($filterData): void {
                            $query->where('start_date', '>=', $filterData['date_range'][0])
                                ->where('start_date', '<=', $filterData['date_range'][1]);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::CUSTOM_PERIOD->value && [] !== $filterData['date_range'],
                        function ($query) use ($filterData): void {
                            $query->where('start_date', '>=', $filterData['date_range'][0])
                                ->where('start_date', '<=', $filterData['date_range'][1]);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::YEARLY->value && '' !== $filterData['year'],
                        function ($query) use ($filterData): void {
                            /** @var Carbon $date */
                            $date = Carbon::create($filterData['year']);
                            $startOfYear = $date->startOfYear()->format('Y-m-d');
                            $endOfYear = $date->endOfYear()->format('Y-m-d');

                            $query->where('start_date', '>=', $startOfYear)
                                ->where('start_date', '<=', $endOfYear);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::MONTHLY->value && [] !== $filterData['month'],
                        function ($query) use ($filterData): void {
                            /** @var Carbon $date */
                            $date = Carbon::create($filterData['month']['year'], $filterData['month']['month'] + 1);
                            $startOfMonth = $date->startOfMonth()->format('Y-m-d');
                            $endOfMonth = $date->endOfMonth()->format('Y-m-d');

                            $query->where('start_date', '>=', $startOfMonth)
                                ->where('start_date', '<=', $endOfMonth);
                        }
                    )
                    ->when(
                        $filterData['time_interval_type'] === TimeIntervalType::WEEKLY->value && [] !== $filterData['week'],
                        function ($query) use ($filterData): void {
                            $query->where('start_date', '>=', $filterData['week'][0])
                                ->where('start_date', '<=', $filterData['week'][1]);
                        }
                    );
            })
            ->when($filterData['target_type'], function ($query) use ($filterData): void {
                $query->whereHas('saleTargetTimeframe', function ($query) use ($filterData): void {
                    $query->whereHas('saleTarget', function ($query) use ($filterData): void {
                        $query->where('target_type', $filterData['target_type']);
                    });
                });
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['target_value', 'achieved_value'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['promoter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('targetable_id', (array) $filterData['promoter_ids'])
                    ->where('targetable_type', ModelMapping::PROMOTER->name);
            }, function ($query) use ($locationId, $locationQueries, $companyId): void {
                $query->where(function ($query) use ($locationId, $locationQueries, $companyId): void {
                    $query->where(function ($query) use ($locationId): void {
                        $query->where('targetable_id', $locationId)
                            ->where('targetable_type', ModelMapping::LOCATION->name);
                    })
                        ->orWhereHasMorph(
                            'targetable',
                            ModelMapping::PROMOTER->name,
                            function ($query) use ($locationId, $companyId, $locationQueries): void {
                                $query->whereHas(
                                    'locations',
                                    $locationQueries->filterByStoreIdAndCompanyId($locationId, $companyId)
                                );
                            }
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function deleteSaleAchievedTargetFromSaleTarget(int $saleTargetId): void
    {
        SaleAchievedTarget::query()
            ->whereHas('saleTargetTimeframe', function ($query) use ($saleTargetId): void {
                $query->where('sale_target_id', $saleTargetId);
            })
            ->update([
                'achieved_value' => 0.0,
            ]);
    }
}
