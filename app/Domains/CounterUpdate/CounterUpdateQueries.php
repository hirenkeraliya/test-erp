<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\CloseCounterDenomination\CloseCounterDenominationQueries;
use App\Domains\CloseCounterPayment\CloseCounterPaymentQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterData;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\Counter\DataObjects\OpenCounterData;
use App\Domains\CounterUpdate\Enums\CounterStatus;
use App\Domains\CounterUpdateDeclarationAttempt\CounterUpdateDeclarationAttemptQueries;
use App\Domains\CounterUpdateDeclarationAttemptPayment\CounterUpdateDeclarationAttemptPaymentQueries;
use App\Domains\CounterUpdateEvent\CounterUpdateEventQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\StoreDayClose;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CounterUpdateQueries
{
    public function closedCounterQueryList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getCounterQueryListWithRelation($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function closedCounterTotalSalesCollection(array $filterData, int $companyId): float|string|int
    {
        return $this->getCounterQueryList($filterData, $companyId)->sum('sales_collection_amount');
    }

    public function closedCounterListForExport(array $filterData, int $companyId): Collection
    {
        return $this->getCounterQueryListWithRelation($filterData, $companyId)->get();
    }

    public function getCounterQueryListWithRelation(array $filterData, int $companyId): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);

        return $this->getCounterQueryList($filterData, $companyId)
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counter.location:' . $locationQueries->getBasicColumnNames(),
                'cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
            ]);
    }

    public function getCounterQueryList(array $filterData, int $companyId): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'counter_id',
                'cashier_id',
                'opening_balance',
                'closing_balance',
                'closed_at',
                'mismatch_amount',
                'amount_mismatch_reason',
                'opened_by_pos_at',
                'created_at',
                'sales_collection_amount'
            )
            ->whereNotNull('closed_at')
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData, $employeeQueries): void {
                $query->where(function ($query) use ($filterData, $employeeQueries): void {
                    $query
                        ->whereAny([
                            'opening_balance',
                            'closing_balance',
                            'mismatch_amount',
                        ], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('cashier', function ($query) use ($employeeQueries, $filterData): void {
                            $query->select('id', 'employee_id')
                                ->whereHas(
                                    'employee',
                                    $employeeQueries->searchByFirstAndLastName($filterData['search_text'])
                                );
                        });
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counter', $counterQueries->filterByIds($filterData['counter_ids']));
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $cashierQueries): void {
                $query->whereHas('cashier', $cashierQueries->filterById((int) $filterData['cashier_id']));
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['closed_at'], function ($query) use ($filterData): void {
                $query->where('closed_at', '>=', CommonFunctions::addStartTime($filterData['closed_at'][0]))
                    ->where('closed_at', '<=', CommonFunctions::addEndTime($filterData['closed_at'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getForSalesCollectionByFilter(array $filterData): Collection
    {
        return $this->queryForSalesCollectionByFilter($filterData)->get();
    }

    public function getForSalesOverallByFilter(array $filterData, ?int $locationId = null): Collection
    {
        return $this->queryForSalesOverallByFilter($filterData, $locationId)->get();
    }

    public function getForSalesCollectionByFilterCashier(array $filterData): Collection
    {
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return $this->queryForSalesCollectionByFilter($filterData)
            ->with([
                'cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])->get();
    }

    public function getForSalesCollectionBySummaryDetails(array $filterData): Collection
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'counter_id',
                'cashier_id',
                'sales_collection_amount',
                'total_sales',
                'total_tax_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount'
            )
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'sales' => $saleQueries->getSumOfQuantity(),
                'saleReturns' => $saleReturnQueries->getSumOfQuantity(),
            ])
            ->whereNotNull('opened_by_pos_at')
            ->when($filterData['location_ids'], function ($query) use ($filterData, $locationQueries): void {
                $query->whereHas('counter', function ($query) use ($locationQueries, $filterData): void {
                    $query->select('id', 'location_id')
                        ->whereHas(
                            'location',
                            $locationQueries->filterByIds($filterData['location_ids'], LocationTypes::STORE->value)
                        );
                });
            })
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereHas('sales', function ($query) use ($filterData): void {
                        $query->select('id')
                            ->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                    });
                }
            )
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
            })
            ->when($filterData['cashier_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('cashier_id', $filterData['cashier_ids']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('opened_by_pos_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->orderBy('opened_by_pos_at', 'desc')
            ->get();
    }

    public function addNew(OpenCounterData $openCounterData, int $cashierId): int
    {
        return CounterUpdate::create([
            'counter_id' => $openCounterData->counter_id,
            'cashier_id' => $cashierId,
            'opening_balance' => $openCounterData->opening_balance,
            'opened_by_pos_at' => $openCounterData->opened_by_pos_at,
        ])->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_id,cashier_id,opening_balance,closing_balance,created_at,opened_by_pos_at,closed_by_pos_at,closed_at';
    }

    public function getCounterIdCashierIdColumnNames(): string
    {
        return 'id,counter_id,cashier_id';
    }

    public function getCounterIdColumnName(): string
    {
        return 'id,counter_id';
    }

    public function closeCounterUpdate(
        CounterUpdate $counterUpdate,
        CloseCounterData|CloseCounterDataForStoreManager $closeCounterData,
        array $counterClosingDetails,
        string $closedByType,
        int $closedId,
    ): void {
        $counterUpdate->closing_balance = $closeCounterData->closing_balance;
        $counterUpdate->closed_by_pos_at = $closeCounterData->closed_by_pos_at ?? null;

        $counterUpdate->sales_collection_amount = $counterClosingDetails['sales_collection_amount'];
        $counterUpdate->total_sales = $counterClosingDetails['total_sales'];
        $counterUpdate->total_sales_amount = $counterClosingDetails['total_sales_amount'];
        $counterUpdate->total_sale_returns = $counterClosingDetails['total_sale_returns'];
        $counterUpdate->total_sale_returns_amount = $counterClosingDetails['total_sale_returns_amount'];
        $counterUpdate->total_item_wise_discount_amount = $counterClosingDetails['total_item_wise_discount_amount'];
        $counterUpdate->total_cart_wide_discount_amount = $counterClosingDetails['total_cart_wide_discount_amount'];
        $counterUpdate->total_layaway_sales = $counterClosingDetails['total_layaway_sales'];
        $counterUpdate->total_layaway_sales_amount = $counterClosingDetails['total_layaway_sales_amount'];
        $counterUpdate->total_credit_sales = $counterClosingDetails['total_credit_sales'];
        $counterUpdate->total_credit_sales_amount = $counterClosingDetails['total_credit_sales_amount'];
        $counterUpdate->total_voided_sales = $counterClosingDetails['total_voided_sales'];
        $counterUpdate->total_voided_sales_amount = $counterClosingDetails['total_voided_sales_amount'];
        $counterUpdate->total_tax_amount = $counterClosingDetails['total_tax_amount'];
        $counterUpdate->total_cash_ins_amount = $counterClosingDetails['total_cash_ins_amount'];
        $counterUpdate->total_cash_outs_amount = $counterClosingDetails['total_cash_outs_amount'];
        $counterUpdate->total_credit_notes_used_amount = $counterClosingDetails['total_credit_notes_used_amount'];
        $counterUpdate->total_credit_notes_used = $counterClosingDetails['total_credit_notes_used'];
        $counterUpdate->total_credit_notes_refunded_amount = $counterClosingDetails['total_credit_notes_refunded_amount'];
        $counterUpdate->total_credit_notes_refunded = $counterClosingDetails['total_credit_notes_refunded'];
        $counterUpdate->total_booking_payment_amount = $counterClosingDetails['total_booking_payment_amount'];
        $counterUpdate->total_booking_payment_refunded_amount = $counterClosingDetails['total_booking_payment_refunded_amount'];
        $counterUpdate->total_booking_payment_used_amount = $counterClosingDetails['total_booking_payment_used_amount'];
        $counterUpdate->total_sales_round_off = $counterClosingDetails['total_sales_round_off'];
        $counterUpdate->total_sale_returns_round_off = $counterClosingDetails['total_sale_returns_round_off'];
        $counterUpdate->total_cashback = $counterClosingDetails['total_cashback'];
        $counterUpdate->total_cashback_amount = $counterClosingDetails['total_cashback_amount'];
        $counterUpdate->total_vouchers_used = $counterClosingDetails['total_vouchers_used'];
        $counterUpdate->total_voucher_discount_amount = $counterClosingDetails['total_voucher_discount_amount'];
        $counterUpdate->total_vouchers_generated = $counterClosingDetails['total_vouchers_generated'];
        $counterUpdate->total_sale_promotion_used = $counterClosingDetails['total_sale_promotion_used'];
        $counterUpdate->total_sale_promotion_discount_amount = $counterClosingDetails['total_sale_promotion_discount_amount'];
        $counterUpdate->total_sale_item_promotion_used = $counterClosingDetails['total_sale_item_promotion_used'];
        $counterUpdate->total_sale_item_promotion_discount_amount = $counterClosingDetails['total_sale_item_promotion_discount_amount'];
        $counterUpdate->total_dream_price_used = $counterClosingDetails['total_dream_price_used'];
        $counterUpdate->total_dream_price_discount_amount = $counterClosingDetails['total_dream_price_discount_amount'];
        $counterUpdate->total_complimentary_item_discount_used = $counterClosingDetails['total_complimentary_item_discount_used'];
        $counterUpdate->total_complimentary_item_discount_amount = $counterClosingDetails['total_complimentary_item_discount_amount'];
        $counterUpdate->total_price_override_used = $counterClosingDetails['total_price_override_used'];
        $counterUpdate->total_price_override_discount_amount = $counterClosingDetails['total_price_override_discount_amount'];
        $counterUpdate->total_cash_amount_in_sales = $counterClosingDetails['total_cash_amount_in_sales'];
        $counterUpdate->total_cash_amount_in_booking_payment = $counterClosingDetails['total_cash_amount_in_booking_payment'];
        $counterUpdate->total_cash_amount_in_booking_payment_refunded = $counterClosingDetails['total_cash_amount_in_booking_payment_refunded'];
        $counterUpdate->total_cash_amount_in_credit_note_refunded = $counterClosingDetails['total_cash_amount_in_credit_note_refunded'];
        $counterUpdate->closed_at = now()->format('Y-m-d H:i:s');
        $counterUpdate->total_new_booking_payments = $counterClosingDetails['total_new_booking_payments'];
        $counterUpdate->total_used_booking_payments = $counterClosingDetails['total_used_booking_payments'];
        $counterUpdate->total_cancel_layaway_sales = $counterClosingDetails['total_cancel_layaway_sales'];
        $counterUpdate->total_cancel_layaway_sales_amount = $counterClosingDetails['total_cancel_layaway_sales_amount'];
        $counterUpdate->closed_by_type = $closedByType;
        $counterUpdate->closed_by_id = $closedId;

        if (! CommonFunctions::compareFloatNumbers(
            $closeCounterData->closing_balance,
            $counterClosingDetails['closing_balance']
        )) {
            $counterUpdate->amount_mismatch_reason = $closeCounterData->mismatch_amount_reason;
            $counterUpdate->mismatch_amount =
                $closeCounterData->closing_balance -
                $counterClosingDetails['closing_balance'];
        }

        $counterUpdate->save();
    }

    public function filterByCounter(int $locationId): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')->whereHas(
            'counter',
            $counterQueries->filterByLocation($locationId)
        );
    }

    public function filterByCounterIdAndLocationId(int $locationId, int $counterId): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->where('counter_id', $counterId)
            ->whereHas('counter', $counterQueries->filterByLocation($locationId));
    }

    public function searchByCashierName(string $searchText): Closure
    {
        $cashierQueries = resolve(CashierQueries::class);

        return fn ($query) => $query->select('id', 'cashier_id')->whereHas(
            'cashier',
            $cashierQueries->searchByName($searchText)
        );
    }

    public function searchByCounterAndStoreName(string $searchText): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->searchByNameAndLocationName($searchText));
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId));
    }

    public function filterByStoreId(int $locationId): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->filterByLocation($locationId));
    }

    public function filterByStoreIds(array $locationIds): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->filterByLocations($locationIds));
    }

    public function getByDayCloseAndStore(
        int $locationId,
        int $companyId,
        ?StoreDayClose $storePreviousDayClose
    ): Collection {
        return $this->commonQueryForDayClose($locationId, $companyId, $storePreviousDayClose)->get();
    }

    public function getByIdFilterByStore(int $locationId, int $counterUpdateId): CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);

        return CounterUpdate::query()
            ->select('id', 'counter_id', 'opening_balance', 'closed_at', 'mismatch_amount', 'amount_mismatch_reason')
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->findOrFail($counterUpdateId);
    }

    public function getByIdFilterByCompany(int $counterUpdateId, int $companyId): CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $counterUpdateEventQueries = resolve(CounterUpdateEventQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'mismatch_amount',
                'amount_mismatch_reason',
                'sales_collection_amount',
                'opening_balance',
                'closing_balance',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_tax_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_booking_payment',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
            )
            ->with([
                'denominations:' . $closeCounterDenominationQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdateEvents:' . $counterUpdateEventQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments:' . $counterUpdateDeclarationAttemptPaymentQueries->getBasicColumns(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId))
            ->findOrFail($counterUpdateId);
    }

    public function getByIdFilterByCompanyForPrint(int $counterUpdateId, int $companyId): CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        return CounterUpdate::query()
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'counter.location.company:' . $companyQueries->getBasicColumnNames(),
                'denominations:' . $closeCounterDenominationQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments:' . $counterUpdateDeclarationAttemptPaymentQueries->getBasicColumns(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId))
            ->findOrFail($counterUpdateId);
    }

    public function getByIdFilterByCompanyAndStore(int $counterUpdateId, int $locationId): CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $counterUpdateEventQueries = resolve(CounterUpdateEventQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'mismatch_amount',
                'amount_mismatch_reason',
                'sales_collection_amount',
                'opening_balance',
                'closing_balance',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_tax_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_booking_payment',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
            )
            ->with([
                'denominations:' . $closeCounterDenominationQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdateEvents:' . $counterUpdateEventQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments:' . $counterUpdateDeclarationAttemptPaymentQueries->getBasicColumns(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->findOrFail($counterUpdateId);
    }

    public function filterByCounterStores(array $locationIds): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')->whereHas(
            'counter',
            $counterQueries->filterByLocations($locationIds)
        );
    }

    public function getCompanyIdByCounterUpdateId(int $counterUpdateId): int
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $counterUpdate = CounterUpdate::query()
            ->select('id', 'counter_id')
            ->with(
                'counter:' . $counterQueries->getLocationIdColumn(),
                'counter.location:' . $locationQueries->getLocationCompanyId(),
            )
            ->findOrFail($counterUpdateId);

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        return $location->company_id;
    }

    public function getStoreIdByCounterUpdateId(int $counterUpdateId): int
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $counterUpdate = CounterUpdate::query()
            ->select('id', 'counter_id')
            ->with(
                'counter:' . $counterQueries->getLocationIdColumn(),
                'counter.location:' . $locationQueries->getLocationCompanyId(),
            )
            ->findOrFail($counterUpdateId);

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        return $location->id;
    }

    public function getOpenCountersCountFilterByStoreAndDates(
        int $locationId,
        ?StoreDayClose $storePreviousDayClose
    ): int {
        $counterQueries = resolve(CounterQueries::class);

        return CounterUpdate::query()
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->when($storePreviousDayClose?->closed_at, function ($query) use ($storePreviousDayClose): void {
                $query->where('created_at', '>=', $storePreviousDayClose?->closed_at)
                    ->where('created_at', '<=', now()->format('Y-m-d H:i:s'));
            })
            ->whereNull('closed_at')
            ->count();
    }

    public function getFirstCounterOpenDate(int $locationId, StoreDayClose $lastStoreDayClose): ?CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);

        return CounterUpdate::query()
            ->select('id', 'opened_by_pos_at')
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->where('opened_by_pos_at', '>=', $lastStoreDayClose->closed_at)
            ->where('opened_by_pos_at', '<=', now()->format('Y-m-d H:i:s'))
            ->orderBy('opened_by_pos_at', 'asc')
            ->first();
    }

    public function getByStoreWithPaymentsFilterByDates(
        int $locationId,
        ?StoreDayClose $storePreviousDayClose
    ): Collection {
        $counterQueries = resolve(CounterQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'total_sales',
                'total_sales_amount',
                'sales_collection_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_tax_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_booking_payment',
                'opening_balance',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
            )
            ->with('payments:' . $closeCounterPaymentQueries->getBasicColumnNames())
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->when($storePreviousDayClose?->closed_at, function ($query) use ($storePreviousDayClose): void {
                $query->where('created_at', '>=', $storePreviousDayClose?->closed_at)
                    ->where('created_at', '<=', now()->format('Y-m-d H:i:s'));
            })
            ->get();
    }

    public function getByIdWithRelationsFilterByStore(int $locationId, int $counterUpdateId): CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'opening_balance',
                'closing_balance',
                'closed_at',
                'mismatch_amount',
                'amount_mismatch_reason',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_tax_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_booking_payment',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
            )
            ->with([
                'denominations:' . $closeCounterDenominationQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->findOrFail($counterUpdateId);
    }

    public function getPaginatedLastThirtyDaysClosedCountersForPos(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'counter_id',
                'cashier_id',
                'opening_balance',
                'closing_balance',
                'mismatch_amount',
                'amount_mismatch_reason',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_tax_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'closed_at',
                'created_at',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
                'opened_by_pos_at',
            )
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counter', $counterQueries->filterByLocationAndCompany($locationId, $companyId))
            ->where('closed_at', '>=', CommonFunctions::addStartTime(now()->subDays(30)->format('Y-m-d')))
            ->whereNotNull('closed_at')
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $cashierQueries,
                $counterQueries
            ): void {
                $query->where(function ($query) use ($filterData, $cashierQueries, $counterQueries): void {
                    $query->whereHas(
                        'counter',
                        $counterQueries->searchByNameAndLocationName($filterData['search_text'])
                    )
                        ->orWhereHas('cashier', $cashierQueries->searchByName($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getByIdWithClosedAtColumn(int $counterUpdateId): CounterUpdate
    {
        return CounterUpdate::query()
            ->select('id', 'closed_at')
            ->findOrFail($counterUpdateId);
    }

    public function filterByCounterId(int $counterId): Closure
    {
        return fn ($query) => $query->select('id')->where('counter_id', $counterId);
    }

    public function filterByCashierId(int $cashierId): Closure
    {
        return fn ($query) => $query->select('id')->where('cashier_id', $cashierId);
    }

    public function getPaginatedClosedCounterListForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->closedCounterQueryForStoreManagerWithRelations($filterData, $companyId, $locationId)
            ->paginate($filterData['per_page']);
    }

    public function closedCounterQueryListForExportInStoreManagerPanel(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->closedCounterQueryForStoreManagerWithRelations($filterData, $companyId, $locationId)->get();
    }

    public function filterByCounterIds(array $counterIds): Closure
    {
        return fn ($query) => $query->whereIntegerInRaw('counter_id', $counterIds);
    }

    public function filterByCashierIds(array $cashierIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('cashier_id', $cashierIds);
    }

    public function getCounterUpdateAttemptDetailsByIdAndFilterByCompany(
        int $counterUpdateId,
        int $companyId
    ): Model {
        $counterQueries = resolve(CounterQueries::class);

        return $this->getCounterUpdateAttemptDetailsQuery()
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId))
            ->findOrFail($counterUpdateId);
    }

    public function getCounterUpdateAttemptDetailsByIdAndFilterByStore(int $counterUpdateId, int $locationId): Model
    {
        $counterQueries = resolve(CounterQueries::class);

        return $this->getCounterUpdateAttemptDetailsQuery()
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->findOrFail($counterUpdateId);
    }

    public function getCounterUpdateTillDetailsByIdAndFilterByCompany(int $counterUpdateId, int $companyId): Model
    {
        $counterQueries = resolve(CounterQueries::class);

        return $this->getCounterUpdateTillDetailsQuery()
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId))
            ->findOrFail($counterUpdateId);
    }

    public function getCounterUpdateTillDetailsByIdAndFilterByStore(int $counterUpdateId, int $locationId): Model
    {
        $counterQueries = resolve(CounterQueries::class);

        return $this->getCounterUpdateTillDetailsQuery()
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->findOrFail($counterUpdateId);
    }

    public function getLastClosedTimeOfCounter(int $counterId): ?CounterUpdate
    {
        return CounterUpdate::where('counter_id', $counterId)
            ->select('id', 'counter_id', 'closed_at', 'closed_by_pos_at')
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->first();
    }

    public function getByIdOrByCounterIdAndOpenedByPosAt(
        ?int $id,
        ?int $counterId,
        ?string $openedByPosAt
    ): ?CounterUpdate {
        return CounterUpdate::query()
            ->select('id', 'closed_by_pos_at')
            ->where('id', $id)
            ->orWhere(function ($query) use ($counterId, $openedByPosAt): void {
                $query->where('counter_id', $counterId)
                    ->where('opened_by_pos_at', $openedByPosAt);
            })
            ->first();
    }

    public function closedCounterTotalSalesCollectionForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): float {
        return (float) $this->closedCounterQueryForStoreManager($filterData, $companyId, $locationId)->sum(
            'sales_collection_amount'
        );
    }

    public function getOpenCounterDetailsForReportsList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->openCounterDetailsQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getOpenCounterDetailsExport(array $filterData, int $companyId): Collection
    {
        return $this->openCounterDetailsQuery($filterData, $companyId)->get();
    }

    public function sellThroughReportDateConditionCheck(array $dateRange): Closure
    {
        return fn ($query) => $query->where(
            'counter_updates.opened_by_pos_at',
            '>=',
            CommonFunctions::addStartTime($dateRange[0])
        )
            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($dateRange[1]))
            ->orWhere(function ($query) use ($dateRange): void {
                $query->whereNull('counter_updates.opened_by_pos_at')
                    ->where('counter_updates.created_at', '>=', CommonFunctions::addStartTime($dateRange[0]))
                    ->where('counter_updates.created_at', '<=', CommonFunctions::addEndTime($dateRange[1]));
            });
    }

    public function getByDayCloseAndStoreByType(
        array $filterData,
        int $companyId,
        ?StoreDayClose $storePreviousDayClose
    ): Collection {
        return $this->commonQueryForDayClose(
            $filterData['location_id'],
            $companyId,
            $storePreviousDayClose,
            $filterData['search_text']
        )
            ->when($filterData['status'], function ($query) use ($filterData): void {
                if ($filterData['status'] === CounterStatus::OPEN->value) {
                    $query->whereNull('closed_at');
                }

                if ($filterData['status'] === CounterStatus::CLOSE->value) {
                    $query->whereNotNull('closed_at');
                }
            })
            ->get();
    }

    public function findByIdAndFilterByStore(int $locationId, int $companyId, int $counterUpdateId): ?CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);

        return CounterUpdate::query()
            ->select('id', 'counter_id', 'opening_balance', 'closed_at', 'mismatch_amount', 'amount_mismatch_reason')
            ->whereHas('counter', $counterQueries->filterByLocationAndCompany($locationId, $companyId))
            ->find($counterUpdateId);
    }

    public function findByIdWithRelationsFilterByStore(int $locationId, int $counterUpdateId): ?CounterUpdate
    {
        $counterQueries = resolve(CounterQueries::class);
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'opening_balance',
                'closing_balance',
                'closed_at',
                'mismatch_amount',
                'amount_mismatch_reason',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_tax_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_booking_payment',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
            )
            ->with([
                'denominations:' . $closeCounterDenominationQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counter', $counterQueries->filterByLocation($locationId))
            ->find($counterUpdateId);
    }

    public function findByIdFilterByCompanyAndStore(
        int $counterUpdateId,
        int $companyId,
        int $locationId
    ): ?CounterUpdate {
        $counterQueries = resolve(CounterQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $closeCounterDenominationQueries = resolve(CloseCounterDenominationQueries::class);
        $counterUpdateEventQueries = resolve(CounterUpdateEventQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'mismatch_amount',
                'amount_mismatch_reason',
                'sales_collection_amount',
                'opening_balance',
                'closing_balance',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_credit_sales',
                'total_credit_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_tax_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_used',
                'total_credit_notes_refunded_amount',
                'total_credit_notes_refunded',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_voucher_discount_amount',
                'total_vouchers_generated',
                'total_sale_promotion_used',
                'total_sale_promotion_discount_amount',
                'total_sale_item_promotion_used',
                'total_sale_item_promotion_discount_amount',
                'total_dream_price_used',
                'total_dream_price_discount_amount',
                'total_complimentary_item_discount_used',
                'total_complimentary_item_discount_amount',
                'total_price_override_used',
                'total_price_override_discount_amount',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_booking_payment',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
            )
            ->with([
                'denominations:' . $closeCounterDenominationQueries->getBasicColumnNames(),
                'payments:' . $closeCounterPaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'counterUpdateEvents:' . $counterUpdateEventQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments:' . $counterUpdateDeclarationAttemptPaymentQueries->getBasicColumns(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('counter', $counterQueries->filterByLocationAndCompany($locationId, $companyId))
            ->find($counterUpdateId);
    }

    public function filterByOpenedByPosAtWithinDateRange(array $date): Closure
    {
        return fn ($query) => $query->where('opened_by_pos_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('opened_by_pos_at', '<=', CommonFunctions::addEndTime($date[1]));
    }

    public function getOpenCounterIds(): Collection
    {
        return CounterUpdate::query()
            ->select('id')
            ->whereNull('closed_at')
            ->get();
    }

    public function getClosedCounterIds(string $toData, string $fromDate): Collection
    {
        return CounterUpdate::query()
            ->select('id')
            ->where('closed_at', '>=', $toData)
            ->where('closed_at', '<=', $fromDate)
            ->get();
    }

    public function filterByStoreIdAndCompanyId(int $locationId, int $companyId): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->filterByLocationAndCompany($locationId, $companyId));
    }

    public function filterByStoreIdsAndCompanyId(array $locationIds, int $companyId): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->filterByLocationsAndCompany($locationIds, $companyId));
    }

    public function filterByCompanyIdAndStoreId(int $companyId, ?int $locationId = null): Closure
    {
        $counterQueries = resolve(CounterQueries::class);

        return fn ($query) => $query->select('id', 'counter_id')
            ->whereHas('counter', $counterQueries->filterByCompanyIdAndStoreId($companyId, $locationId));
    }

    private function getCounterUpdateTillDetailsQuery(): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $counterUpdateEventQueries = resolve(CounterUpdateEventQueries::class);

        return CounterUpdate::query()
            ->select('id', 'counter_id')
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdateEvents:' . $counterUpdateEventQueries->getBasicColumnNames(),
            ]);
    }

    private function getCounterUpdateAttemptDetailsQuery(): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);
        $counterUpdateDeclarationAttemptPaymentQueries = resolve(CounterUpdateDeclarationAttemptPaymentQueries::class);

        return CounterUpdate::query()
            ->select('id', 'counter_id')
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counter.location:' . $locationQueries->getNameColumnName(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments:' . $counterUpdateDeclarationAttemptPaymentQueries->getBasicColumns(),
                'counterUpdateDeclarationAttempts.counterUpdateDeclarationAttemptPayments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ]);
    }

    private function closedCounterQueryForStoreManager(array $filterData, int $companyId, int $locationId): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'counter_id',
                'cashier_id',
                'opening_balance',
                'closing_balance',
                'closed_at',
                'mismatch_amount',
                'amount_mismatch_reason',
                'opened_by_pos_at',
                'created_at',
                'sales_collection_amount'
            )
            ->whereNotNull('closed_at')
            ->whereHas('counter', $counterQueries->filterByLocationAndCompany($locationId, $companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData, $employeeQueries): void {
                $query->where(function ($query) use ($filterData, $employeeQueries): void {
                    $query->whereAny([
                        'opening_balance',
                        'closing_balance',
                        'mismatch_amount',
                    ], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('cashier', function ($query) use ($employeeQueries, $filterData): void {
                            $query->select('id', 'employee_id')
                                ->whereHas(
                                    'employee',
                                    $employeeQueries->searchByFirstAndLastName($filterData['search_text'])
                                );
                        });
                });
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counter', $counterQueries->filterByIds($filterData['counter_ids']));
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $cashierQueries): void {
                $query->whereHas('cashier', $cashierQueries->filterById((int) $filterData['cashier_id']));
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['closed_at'], function ($query) use ($filterData): void {
                $query->where('closed_at', '>=', CommonFunctions::addStartTime($filterData['closed_at'][0]))
                    ->where('closed_at', '<=', CommonFunctions::addEndTime($filterData['closed_at'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function closedCounterQueryForStoreManagerWithRelations(
        array $filterData,
        int $companyId,
        int $locationId
    ): Builder {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterUpdateDeclarationAttemptQueries = resolve(CounterUpdateDeclarationAttemptQueries::class);

        return $this->closedCounterQueryForStoreManager($filterData, $companyId, $locationId)
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdateDeclarationAttempts:' . $counterUpdateDeclarationAttemptQueries->getBasicColumnNames(),
                'counter.location:' . $locationQueries->getBasicColumnNames(),
                'cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ]);
    }

    private function queryForSalesCollectionByFilter(array $filterData): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'counter_id',
                'cashier_id',
                DB::raw('DATE_FORMAT(opened_by_pos_at,"%Y-%m-%d") as opened_by_pos_at'),
                'sales_collection_amount',
                'total_sales',
                'total_sale_returns',
                'total_sales_round_off',
                'total_tax_amount',
            )
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'payments' => $closeCounterPaymentQueries->getExcludeCreditNote(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            ])
            ->whereNotNull('opened_by_pos_at')
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereHas('sales', function ($query) use ($filterData): void {
                        $query->select('id')
                            ->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                    });
                }
            )
           ->when(null !== $filterData['location_ids'], function ($query) use (
               $filterData,
               $locationQueries
           ): void {
               $query->whereHas('counter', function ($query) use ($locationQueries, $filterData): void {
                   $query->select('id', 'location_id')
                       ->whereHas(
                           'location',
                           $locationQueries->filterByIds($filterData['location_ids'], LocationTypes::STORE->value)
                       );
               });
           })
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
            })
            ->when(null !== $filterData['cashier_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('cashier_id', $filterData['cashier_ids']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('opened_by_pos_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->orderBy('opened_by_pos_at', 'asc');
    }

    public function getSalesCollectionReportByDateAndBrand(array $filterData): Collection
    {
        $brandColumn = config('app.product_variant') ? 'mp.brand_id' : 'p.brand_id';

        $salesSubquery = DB::table('sale_items as si')
            ->select(
                DB::raw($brandColumn . ' as brand_id'),
                'c.location_id',
                DB::raw('SUM(si.total_price_paid) as total_sales'),
                DB::raw('0 as total_returns'),
                DB::raw('DATE_FORMAT(cu.opened_by_pos_at, "%Y-%m-%d") as opened_by_pos_at')
            )
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('counter_updates as cu', 's.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('products as p', 'si.product_id', '=', 'p.id')
                    ->leftJoin('master_products as mp', 'p.master_product_id', '=', 'mp.id');
            }, function ($query): void {
                $query->leftJoin('products as p', 'si.product_id', '=', 'p.id');
            })
            ->whereIntegerInRaw('s.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->when(array_key_exists('date_range', $filterData) && [] !== $filterData['date_range'], function ($query) use (
                $filterData
            ): void {
                $query->whereBetween('cu.opened_by_pos_at', [
                    CommonFunctions::addStartTime($filterData['date_range'][0]),
                    CommonFunctions::addEndTime($filterData['date_range'][1]),
                ]);
            })
            ->when(
                array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIn('c.location_id', $filterData['location_ids']);
                }
            )
            ->groupBy(DB::raw($brandColumn), 'c.location_id', 'opened_by_pos_at');

        $returnsSubquery = DB::table('sale_return_items as sri')
            ->select(
                DB::raw($brandColumn . ' as brand_id'),
                'c.location_id',
                DB::raw('0 as total_sales'),
                DB::raw('SUM(sri.total_price_paid) as total_returns'),
                DB::raw('DATE_FORMAT(cu.opened_by_pos_at, "%Y-%m-%d") as opened_by_pos_at')
            )
            ->join('sale_returns as sr', 'sri.sale_return_id', '=', 'sr.id')
            ->join('counter_updates as cu', 'sr.counter_update_id', '=', 'cu.id')
            ->join('counters as c', 'cu.counter_id', '=', 'c.id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->join('products as p', 'sri.product_id', '=', 'p.id')
                    ->leftJoin('master_products as mp', 'p.master_product_id', '=', 'mp.id');
            }, function ($query): void {
                $query->join('products as p', 'sri.product_id', '=', 'p.id');
            })
            ->when(array_key_exists('date_range', $filterData) && [] !== $filterData['date_range'], function ($query) use (
                $filterData
            ): void {
                $query->whereBetween('cu.opened_by_pos_at', [
                    CommonFunctions::addStartTime($filterData['date_range'][0]),
                    CommonFunctions::addEndTime($filterData['date_range'][1]),
                ]);
            })
            ->when(
                array_key_exists('location_ids', $filterData) && null !== $filterData['location_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIn('c.location_id', $filterData['location_ids']);
                }
            )
            ->groupBy(DB::raw($brandColumn), 'c.location_id', 'opened_by_pos_at');

        $salesAndReturns = $salesSubquery->unionAll($returnsSubquery);

        return DB::query()
            ->fromSub($salesAndReturns, 'combined')
            ->select(
                'combined.brand_id',
                'combined.location_id',
                'combined.opened_by_pos_at',
                DB::raw('SUM(combined.total_sales) as total_sales'),
                DB::raw('SUM(combined.total_returns) as total_returns'),
                DB::raw('SUM(combined.total_sales) - SUM(combined.total_returns) as sales_collection_amount')
            )
            ->join('brands as b', 'combined.brand_id', '=', 'b.id')
            ->join('locations as l', 'combined.location_id', '=', 'l.id')
            ->addSelect('b.name as brand_name', 'l.name as location_name')
            ->when(
                array_key_exists('counter_ids', $filterData) && null !== $filterData['counter_ids'],
                function ($query) use ($filterData): void {
                    $query->join('counters as c', 'l.id', '=', 'c.location_id')
                        ->whereIn('c.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('cashier_ids', $filterData) && null !== $filterData['cashier_ids'],
                function ($query) use ($filterData): void {
                    $query->join('counters as c', 'l.id', '=', 'c.location_id')
                        ->join('counter_updates as cu', function ($join): void {
                            $join->on('cu.opened_by_pos_at', '=', 'combined.opened_by_pos_at')
                                ->on('cu.counter_id', '=', 'c.id');
                        })
                        ->whereIn('cu.cashier_id', $filterData['cashier_ids']);
                }
            )
            ->when(
                array_key_exists('e_invoice_submitted', $filterData) && null !== $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->join('counters as c', 'l.id', '=', 'c.location_id')
                        ->join('counter_updates as cu', function ($join): void {
                            $join->on('cu.opened_by_pos_at', '=', 'combined.opened_by_pos_at')
                                ->on('cu.counter_id', '=', 'c.id');
                        })
                        ->join('sales as sa', function ($join): void {
                            $join->on('sa.counter_update_id', '=', 'cu.id');
                        })
                    ->whereNot('sa.digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
            )
            ->groupBy('combined.brand_id', 'combined.location_id', 'combined.opened_by_pos_at', 'b.name', 'l.name')
            ->orderBy('combined.opened_by_pos_at', 'asc')
            ->get()
            ->groupBy(['brand_id', 'location_id', 'opened_by_pos_at']);
    }

    private function queryForSalesOverallByFilter(array $filterData, ?int $locationId = null): Builder
    {
        return CounterUpdate::query()
            ->select(
                'counter_updates.id',
                DB::raw('DATE_FORMAT(opened_by_pos_at,"%Y-%m-%d") as opened_by_pos_at'),
                DB::raw('MONTH(opened_by_pos_at) as month'),
                DB::raw('SUM(sales_collection_amount) as sale_collection_amount'),
                DB::raw('SUM(total_sales) as total_sales'),
                DB::raw('SUM(total_sale_returns) as total_sale_returns'),
                'counters.location_id as location_id',
                'locations.name as location_name'
            )
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->when(null !== $locationId, function ($query) use ($locationId): void {
                $query->where('counters.location_id', $locationId);
            })
            ->whereNotNull('opened_by_pos_at')
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('opened_by_pos_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_by_pos_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->groupBy('location_id', 'month')
            ->orderBy('opened_by_pos_at', 'desc');
    }

    private function openCounterDetailsQuery(array $filterData, int $companyId): Builder
    {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);

        $sortOptions = [
            'location' => function ($query, $sortDirection): void {
                $query->join('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->join('locations', 'locations.id', '=', 'counters.location_id')
                    ->orderBy('locations.name', $sortDirection);
            },
            'cashier_name' => function ($query, $sortDirection): void {
                $query->join('cashiers', 'cashiers.id', '=', 'counter_updates.cashier_id')
                    ->join('employees', 'employees.id', '=', 'cashiers.employee_id')
                    ->orderBy('employees.first_name', $sortDirection);
            },
            'counter_name' => function ($query, $sortDirection): void {
                $query->join('counters', 'counters.id', '=', 'counter_updates.counter_id')
                    ->orderBy('counters.name', $sortDirection);
            },
        ];

        return CounterUpdate::query()
            ->select('counter_updates.id', 'counter_id', 'cashier_id', 'opening_balance', 'opened_by_pos_at')
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counter.location:' . $locationQueries->getBasicColumnNames(),
                'cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'counter.location.company:' . $companyQueries->getBasicColumnNames(),
                'counter.location.company.defaultCountry:' . $countryQueries->getColumnId(),
                'counter.location.company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
            ])
            ->whereNull('closed_by_pos_at')
            ->whereNull('closed_at')
            ->whereHas('counter', $counterQueries->filterByCompanyId($companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
            })
            ->when($filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData): void {
                $query->where('cashier_id', $filterData['cashier_id']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $employeeQueries): void {
                $query->where(function ($query) use ($filterData, $employeeQueries): void {
                    $query->where('opening_balance', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('cashier', function ($query) use ($employeeQueries, $filterData): void {
                            $query->select('id', 'employee_id')
                                ->whereHas(
                                    'employee',
                                    $employeeQueries->searchByFirstAndLastName($filterData['search_text'])
                                );
                        });
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData, $sortOptions): void {
                if (isset($sortOptions[$filterData['sort_by']])) {
                    $sortOptions[$filterData['sort_by']]($query, $filterData['sort_direction']);
                } else {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function commonQueryForDayClose(
        int $locationId,
        int $companyId,
        ?StoreDayClose $storePreviousDayClose,
        ?string $searchText = null
    ): Builder {
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return CounterUpdate::query()
            ->select(
                'id',
                'counter_id',
                'opening_balance',
                'closing_balance',
                'opened_by_pos_at',
                'closed_by_pos_at',
                'closed_at',
                'created_at'
            )
            ->with([
                'counter:' . $counterQueries->getBasicColumnNames(),
                'counter.location:' . $locationQueries->getNameColumnName(),
            ])
            ->whereHas('counter', function ($query) use (
                $searchText,
                $locationId,
                $companyId,
                $locationQueries
            ): void {
                $query->select('id', 'location_id')
                    ->where('location_id', $locationId)
                    ->when(null !== $searchText, function ($query) use ($searchText): void {
                        $query->where('name', 'like', '%' . $searchText . '%');
                    })
                    ->whereHas(
                        'location',
                        $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)
                    );
            })
            ->when($storePreviousDayClose?->closed_at, function ($query) use ($storePreviousDayClose): void {
                $query->where('created_at', '>=', $storePreviousDayClose?->closed_at)
                    ->where('created_at', '<=', now()->format('Y-m-d H:i:s'));
            })
            ->orderBy('id', 'desc');
    }

    public function getSalesAndReturnDataByDate(array $filterData): Collection
    {
        $currentDate = $filterData['date'];
        /** @var Carbon $yesterdayDateCarbon */
        $yesterdayDateCarbon = Carbon::createFromFormat('Y-m-d', $currentDate);
        $yesterdayDate = $yesterdayDateCarbon->subDays()->format('m-d');

        $sales = DB::table('counter_updates')
            ->select(
                DB::raw('SUM(sales.total_amount_paid) as total_price_paid'),
                'locations.name as location_name',
                'locations.code as location_code',
                'regions.name as region_name',
                DB::raw('DATE(counter_updates.opened_by_pos_at)  as date'),
            )
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
            ->leftJoin('regions', 'locations.region_id', '=', 'regions.id')
            ->leftJoin('sales', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->whereNotNull('counter_updates.opened_by_pos_at')
            ->whereDate('counter_updates.opened_by_pos_at', $currentDate)
            ->when(
                array_key_exists('counter_ids', $filterData) && null !== $filterData['counter_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('cashier_ids', $filterData) && null !== $filterData['cashier_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counter_updates.cashier_id', $filterData['cashier_ids']);
                }
            )
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereNot('sales.digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
            )
            ->whereIntegerInRaw('locations.id', $filterData['location_ids'])
            ->where('sales.status', '!=', SaleStatus::VOID_SALE->value)
            ->groupBy(['regions.id', 'locations.id', DB::raw('DATE(counter_updates.opened_by_pos_at)')]);

        $saleReturn = DB::table('counter_updates')
            ->select(
                DB::raw('-SUM(sale_returns.total_price_paid) as total_price_paid'),
                'locations.name as location_name',
                'locations.code as location_code',
                'regions.name as region_name',
                DB::raw('DATE(counter_updates.opened_by_pos_at) as date'),
            )
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
            ->leftJoin('regions', 'locations.region_id', '=', 'regions.id')
            ->leftJoin('sale_returns', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->whereNotNull('counter_updates.opened_by_pos_at')
            ->whereDate('counter_updates.opened_by_pos_at', $currentDate)
            ->when(
                array_key_exists('counter_ids', $filterData) && null !== $filterData['counter_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('cashier_ids', $filterData) && null !== $filterData['cashier_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counter_updates.cashier_id', $filterData['cashier_ids']);
                }
            )
            ->whereIntegerInRaw('locations.id', $filterData['location_ids'])
            ->groupBy(['regions.id', 'locations.id', DB::raw('DATE(counter_updates.opened_by_pos_at)')]);

        $yesterDaySales = DB::table('counter_updates')
            ->select(
                DB::raw('SUM(sales.total_amount_paid) as total_price_paid'),
                'locations.name as location_name',
                'locations.code as location_code',
                'regions.name as region_name',
                DB::raw('DATE(counter_updates.opened_by_pos_at)  as date'),
            )
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
            ->leftJoin('regions', 'locations.region_id', '=', 'regions.id')
            ->leftJoin('sales', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->whereNotNull('counter_updates.opened_by_pos_at')
            ->where(DB::raw("DATE_FORMAT(counter_updates.opened_by_pos_at, '%m-%d')"), '=', $yesterdayDate)
            ->when(
                array_key_exists('counter_ids', $filterData) && null !== $filterData['counter_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('cashier_ids', $filterData) && null !== $filterData['cashier_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counter_updates.cashier_id', $filterData['cashier_ids']);
                }
            )
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereNot('sales.digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
            )
            ->whereIntegerInRaw('locations.id', $filterData['location_ids'])
            ->where('sales.status', '!=', SaleStatus::VOID_SALE->value)
            ->groupBy(['regions.id', 'locations.id', DB::raw('DATE(counter_updates.opened_by_pos_at)')]);

        $yesterDaySaleReturn = DB::table('counter_updates')
            ->select(
                DB::raw('-SUM(sale_returns.total_price_paid) as total_price_paid'),
                'locations.name as location_name',
                'locations.code as location_code',
                'regions.name as region_name',
                DB::raw('DATE(counter_updates.opened_by_pos_at) as date'),
            )
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
            ->leftJoin('regions', 'locations.region_id', '=', 'regions.id')
            ->leftJoin('sale_returns', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->whereNotNull('counter_updates.opened_by_pos_at')
            ->where(DB::raw("DATE_FORMAT(counter_updates.opened_by_pos_at, '%m-%d')"), '=', $yesterdayDate)
            ->when(
                array_key_exists('counter_ids', $filterData) && null !== $filterData['counter_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
                }
            )
            ->when(
                array_key_exists('cashier_ids', $filterData) && null !== $filterData['cashier_ids'],
                function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('counter_updates.cashier_id', $filterData['cashier_ids']);
                }
            )
            ->whereIntegerInRaw('locations.id', $filterData['location_ids'])
            ->groupBy(['regions.id', 'locations.id', DB::raw('DATE(counter_updates.opened_by_pos_at)')]);

        return DB::table(
            DB::raw(
                sprintf(
                    ' (( %s UNION %s ) UNION ( %s UNION %s ))  as sales_and_returns',
                    $sales->toSql(),
                    $saleReturn->toSql(),
                    $yesterDaySales->toSql(),
                    $yesterDaySaleReturn->toSql()
                )
            ),
        )
            ->mergeBindings($sales)
            ->mergeBindings($saleReturn)
            ->mergeBindings($yesterDaySales)
            ->mergeBindings($yesterDaySaleReturn)
            ->select(
                DB::raw('SUM(total_price_paid) as total'),
                DB::raw("CONCAT(location_name, ' (', location_code, ')') AS location_name"),
                'region_name',
                DB::raw("DATE_FORMAT(`date`, '%d-%m-%Y') AS date")
            )
            ->having('total', '>', 0)
            ->groupBy(['region_name', 'location_name', 'date'])
            ->get();
    }
}
