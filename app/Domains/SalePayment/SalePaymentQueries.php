<?php

declare(strict_types=1);

namespace App\Domains\SalePayment;

use App\CommonFunctions;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalePaymentQueries
{
    public function addNew(Sale $sale, string $happenedAt, array $paymentDetails, ?int $counterUpdateId = null): int
    {
        $extraDetails = $paymentDetails['extra_details'] ?? null;

        return SalePayment::create([
            'sale_id' => $sale->getKey(),
            'payment_type_id' => $paymentDetails['type_id'],
            'counter_update_id' => $counterUpdateId,
            'amount' => $paymentDetails['amount'],
            'happened_at' => $happenedAt,
            'extra_details' => $extraDetails,
            'currency_id' => $paymentDetails['currency_id'] ?? null,
            'currency_rate' => $paymentDetails['current_currency_rate'] ?? null,
            'currency_amount' => $paymentDetails['currency_amount'] ?? null,
        ])->id;
    }

    public function getBasicColumnNamesForSale(): string
    {
        return 'id,sale_id,payment_type_id,amount,created_at,happened_at,extra_details,currency_id,currency_rate,currency_amount';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id,payment_type_id,amount,happened_at,currency_id,currency_rate,currency_amount';
    }

    public function getNecessaryColumnNames(): string
    {
        return 'id,sale_id,payment_type_id,amount';
    }

    public function getByCounterUpdateIdWithRelations(int $counterUpdateId): Collection
    {
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        return SalePayment::query()
            ->select('id', 'sale_id', 'payment_type_id', 'amount', 'happened_at')
            ->with('paymentType:' . $paymentTypeQueries->getBasicColumnNames())
            ->where(function ($query) use ($saleQueries, $counterUpdateId): void {
                $query->where('counter_update_id', $counterUpdateId)
                    ->orWhere(function ($query) use ($saleQueries, $counterUpdateId): void {
                        $query->whereHas(
                            'sale',
                            $saleQueries->filterByRegularCreditAndLayawaySaleByCounterUpdateId($counterUpdateId)
                        )->whereNull('counter_update_id');
                    });
            })
            ->get();
    }

    public function getSaleIdColumn(): string
    {
        return 'id,sale_id';
    }

    public function getSaleIdAndPaymentTypeIdColumn(): string
    {
        return 'id,sale_id,payment_type_id';
    }

    public function getByStoreIdForSalesCollectionExport(array $filterData): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SalePayment::query()
            ->groupBy('counters.location_id')
            ->groupBy('date')
            ->groupBy('payment_type_id')
            ->join('payment_types', 'payment_types.id', '=', 'sale_payments.payment_type_id')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
            ->join('counters', 'counters.id', '=', 'counter_updates.counter_id')
            ->select(
                DB::raw('DATE(sale_payments.happened_at) as date'),
                DB::raw('SUM(sale_payments.amount) as amount'),
                DB::raw('payment_types.name as payment_name'),
                'counters.location_id',
                'sale_payments.happened_at',
                'sales.notes',
                'sales.round_off',
                'sales.total_tax_amount',
                DB::raw('GROUP_CONCAT(sale_payments.sale_id) as sale_ids')
            )
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->whereHas('saleItems', function ($query): void {
                        $query->isNotExchange();
                    })
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when($filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when($filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->where('sale_payments.happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('sale_payments.happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->get();
    }

    public function getSalePaymentIdAndAmountOfBookingPayment(int $saleId): Collection
    {
        $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);

        return SalePayment::select('id', 'amount', 'payment_type_id')
            ->with('bookingPaymentUse:' . $bookingPaymentUseQueries->getBasicColumnNames())
            ->where('sale_id', $saleId)
            ->where('payment_type_id', StaticPaymentTypes::BOOKING_PAYMENT->value)
            ->get();
    }

    public function getSalePaymentIdAndAmountOfCreditNote(int $saleId): Collection
    {
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);

        return SalePayment::select('id', 'amount')
            ->with('creditNoteUse:' . $creditNoteUseQueries->getBasicColumnNames())
            ->where('sale_id', $saleId)
            ->where('payment_type_id', StaticPaymentTypes::CREDIT_NOTE->value)
            ->get();
    }

    public function getPaymentTypeListForStoreManager(array $filterData, int $locationId, int $companyId): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return SalePayment::query()
            ->groupBy('payment_type_id')
            ->select(
                'id',
                'payment_type_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(payment_type_id) as total_count'),
            )
            ->with([
                'paymentType' => function ($query): void {
                    $query->select('id', 'name')
                        ->whereNotIn('id', [StaticPaymentTypes::CREDIT_NOTE->value]);
                },
            ])
            ->whereNotIn('payment_type_id', [StaticPaymentTypes::CREDIT_NOTE->value])
            ->whereHas('paymentType', $paymentTypeQueries->filterByCompany($companyId))
            ->whereHas('sale', function ($query) use (
                $counterUpdateQueries,
                $filterData,
                $saleQueries,
                $locationId
            ): void {
                $query->whereIntegerInRaw('status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->with('counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames())
                    ->when($filterData['counter_ids'], function ($query) use ($filterData, $saleQueries): void {
                        $query->where($saleQueries->filterByCounterIds($filterData['counter_ids']));
                    })
                    ->when($filterData['date'], function ($query) use ($filterData, $saleQueries): void {
                        $query->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date']));
                    })
                    ->where($saleQueries->filterByStoreId($locationId));
            })
            ->when($filterData['payment_type_id'], function ($query) use ($filterData): void {
                $query->where('payment_type_id', (int) $filterData['payment_type_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getPaymentTypeListForReport(array $filterData, int $companyId): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SalePayment::query()
            ->groupBy('payment_type_id')
            ->select(
                'id',
                'payment_type_id',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(payment_type_id) as total_count'),
            )
            ->with([
                'paymentType' => function ($query): void {
                    $query->select('id', 'name')
                        ->whereNotIn('id', [StaticPaymentTypes::CREDIT_NOTE->value]);
                },
            ])
            ->whereNotIn('payment_type_id', [StaticPaymentTypes::CREDIT_NOTE->value])
            ->whereHas('sale', function ($query) use ($counterUpdateQueries, $filterData, $saleQueries): void {
                $query->select('id')
                    ->whereIntegerInRaw('status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->with('counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames())
                    ->when($filterData['counter_ids'], function ($query) use ($filterData, $saleQueries): void {
                        $query->where($saleQueries->filterByCounterIds($filterData['counter_ids']));
                    })
                    ->when($filterData['date'], function ($query) use ($filterData, $saleQueries): void {
                        $query->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date']));
                    })
                    ->when($filterData['location_ids'], function ($query) use ($filterData, $saleQueries): void {
                        $query->where($saleQueries->filterByStoreIds($filterData['location_ids']));
                    });
            })
            ->whereIn('payment_type_id', function ($query) use ($companyId): void {
                $query->select('id')
                    ->from('payment_types')
                    ->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->when($filterData['payment_type_id'], function ($query) use ($filterData): void {
                $query->where('payment_type_id', (int) $filterData['payment_type_id']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }
}
