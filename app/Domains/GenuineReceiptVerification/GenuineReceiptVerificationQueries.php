<?php

declare(strict_types=1);

namespace App\Domains\GenuineReceiptVerification;

use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Models\GenuineReceiptVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GenuineReceiptVerificationQueries
{
    public function getById(int $genuineReceiptVerificationId): GenuineReceiptVerification
    {
        return GenuineReceiptVerification::findOrFail($genuineReceiptVerificationId);
    }

    public function addNew(array $genuineReceiptVerificationData): GenuineReceiptVerification
    {
        return GenuineReceiptVerification::create($genuineReceiptVerificationData);
    }

    public function update(
        GenuineReceiptVerification $genuineReceiptVerification,
        array $genuineReceiptVerificationData
    ): void {
        $genuineReceiptVerification->remarks = $genuineReceiptVerificationData['remarks'];
        $genuineReceiptVerification->save();
    }

    public function getPaginatedReceiptVerificationReport(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getGenuineReceiptVerificationQuery($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getReceiptVerificationReportDataForExport(array $filterData, int $companyId): Collection
    {
        return $this->getGenuineReceiptVerificationQuery($filterData, $companyId)
            ->get();
    }

    private function getGenuineReceiptVerificationQuery(array $filterData, int $companyId): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return GenuineReceiptVerification::query()
            ->select(
                'name',
                'is_genuine',
                'receipt_number',
                'mobile_number',
                'email',
                'member_id',
                'sale_id',
                'remarks',
                'created_at'
            )
            ->when('' !== $filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(
                        ['name', 'mobile_number', 'email', 'receipt_number'],
                        'LIKE',
                        '%' . $filterData['search_text'] . '%'
                    );
                });
            })
            ->when(null !== $filterData['is_genuine'], function ($query) use ($filterData): void {
                $query->where('is_genuine', (bool) $filterData['is_genuine']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('sale', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id')
                        ->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                            $query->select('id', 'counter_id')
                                ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                        });
                });
            }, function ($query) use ($companyId, $counterUpdateQueries): void {
                $query->where(function ($query) use ($companyId, $counterUpdateQueries): void {
                    $query->whereHas('sale', function ($query) use ($companyId, $counterUpdateQueries): void {
                        $query->select('id')
                            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId));
                    })
                        ->orWhereNull('sale_id');
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query
                    ->where('created_at', '>=', $filterData['date_range'][0])
                    ->where('created_at', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
