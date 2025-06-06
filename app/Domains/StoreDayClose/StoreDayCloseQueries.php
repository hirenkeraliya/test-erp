<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose;

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\StoreDayClosePayment\StoreDayClosePaymentQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Location;
use App\Models\StoreDayClose;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StoreDayCloseQueries
{
    public function getPaginatedDayCloseReportList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->dayCloseReportQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function totalSaleCollectionAmount(array $filterData, int $companyId): array
    {
        $dayClosesData = $this->dayCloseReportQuery($filterData, $companyId)->get();

        return [$dayClosesData->sum('sales_collection_amount'), $dayClosesData->sum('orders_collection_amount')];
    }

    public function getPaginatedDayCloseListForExport(array $filterData, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreDayClose::query()
            ->select(
                'id',
                'location_id',
                'closed_by_store_manager_id',
                'opened_at',
                'closed_at',
                'created_at',
                'sales_collection_amount',
                'orders_collection_amount'
            )
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->with(
                'location:' . $locationQueries->getBasicColumnNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            )
            ->when($filterData['search_text'], function ($query) use (
                $locationQueries,
                $storeManagerQueries,
                $filterData
            ): void {
                $query->where(function ($query) use ($locationQueries, $storeManagerQueries, $filterData): void {
                    $query->whereHas('location', $locationQueries->searchByName($filterData['search_text']))
                        ->orWhereHas(
                            'storeManager',
                            $storeManagerQueries->searchByEmployeeName($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
            })
            ->when($filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('storeManager', function ($query) use ($filterData): void {
                    $query->select('id')->where('employee_id', $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('opened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['closed_at'], function ($query) use ($filterData): void {
                $query->where('closed_at', '>=', CommonFunctions::addStartTime($filterData['closed_at'][0]))
                    ->where('closed_at', '<=', CommonFunctions::addEndTime($filterData['closed_at'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getLastDayClose(int $locationId): ?StoreDayClose
    {
        return StoreDayClose::query()
            ->select('id', 'closed_at')
            ->where('location_id', $locationId)
            ->latest()
            ->first();
    }

    public function addNew(
        Location $location,
        ?int $storeManagerId,
        Collection $counterUpdates,
        array $orderDetails,
        ?string $dateOfFirstCounterOfTheDay = null,
    ): StoreDayClose {
        /** @var Carbon $createdAt */
        $createdAt = $location->created_at;

        return StoreDayClose::create([
            'location_id' => $location->id,
            'opened_at' => $dateOfFirstCounterOfTheDay ?? $createdAt->format('Y-m-d H:i:s'),
            'closed_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'closed_by_store_manager_id' => $storeManagerId,
            'sales_collection_amount' => $counterUpdates->sum('sales_collection_amount'),
            'total_sales' => $counterUpdates->sum('total_sales'),
            'total_sales_amount' => $counterUpdates->sum('total_sales_amount'),
            'total_layaway_sales' => $counterUpdates->sum('total_layaway_sales'),
            'total_layaway_sales_amount' => $counterUpdates->sum('total_layaway_sales_amount'),
            'total_credit_sales' => $counterUpdates->sum('total_credit_sales'),
            'total_credit_sales_amount' => $counterUpdates->sum('total_credit_sales_amount'),
            'total_voided_sales' => $counterUpdates->sum('total_voided_sales'),
            'total_voided_sales_amount' => $counterUpdates->sum('total_voided_sales_amount'),
            'total_tax_amount' => $counterUpdates->sum('total_tax_amount'),
            'total_item_wise_discount_amount' => $counterUpdates->sum('total_item_wise_discount_amount'),
            'total_cart_wide_discount_amount' => $counterUpdates->sum('total_cart_wide_discount_amount'),
            'total_sales_round_off' => $counterUpdates->sum('total_sales_round_off'),
            'total_sale_returns' => $counterUpdates->sum('total_sale_returns'),
            'total_sale_returns_amount' => $counterUpdates->sum('total_sale_returns_amount'),
            'total_credit_notes_used_amount' => $counterUpdates->sum('total_credit_notes_used_amount'),
            'total_credit_notes_used' => $counterUpdates->sum('total_credit_notes_used'),
            'total_credit_notes_refunded_amount' => $counterUpdates->sum('total_credit_notes_refunded_amount'),
            'total_credit_notes_refunded' => $counterUpdates->sum('total_credit_notes_refunded'),
            'total_sale_returns_round_off' => $counterUpdates->sum('total_sale_returns_round_off'),
            'total_cashback' => $counterUpdates->sum('total_cashback'),
            'total_cashback_amount' => $counterUpdates->sum('total_cashback_amount'),
            'total_vouchers_used' => $counterUpdates->sum('total_vouchers_used'),
            'total_voucher_discount_amount' => $counterUpdates->sum('total_voucher_discount_amount'),
            'total_vouchers_generated' => $counterUpdates->sum('total_vouchers_generated'),
            'total_sale_promotion_used' => $counterUpdates->sum('total_sale_promotion_used'),
            'total_sale_promotion_discount_amount' => $counterUpdates->sum('total_sale_promotion_discount_amount'),
            'total_sale_item_promotion_used' => $counterUpdates->sum('total_sale_item_promotion_used'),
            'total_sale_item_promotion_discount_amount' => $counterUpdates->sum(
                'total_sale_item_promotion_discount_amount'
            ),
            'total_dream_price_used' => $counterUpdates->sum('total_dream_price_used'),
            'total_dream_price_discount_amount' => $counterUpdates->sum('total_dream_price_discount_amount'),
            'total_complimentary_item_discount_used' => $counterUpdates->sum('total_complimentary_item_discount_used'),
            'total_complimentary_item_discount_amount' => $counterUpdates->sum(
                'total_complimentary_item_discount_amount'
            ),
            'total_price_override_used' => $counterUpdates->sum('total_price_override_used'),
            'total_price_override_discount_amount' => $counterUpdates->sum('total_price_override_discount_amount'),
            'total_booking_payment_amount' => $counterUpdates->sum('total_booking_payment_amount'),
            'total_booking_payment_refunded_amount' => $counterUpdates->sum('total_booking_payment_refunded_amount'),
            'total_booking_payment_used_amount' => $counterUpdates->sum('total_booking_payment_used_amount'),
            'total_cash_ins_amount' => $counterUpdates->sum('total_cash_ins_amount'),
            'total_cash_outs_amount' => $counterUpdates->sum('total_cash_outs_amount'),
            'total_cash_amount_in_sales' => $counterUpdates->sum('total_cash_amount_in_sales'),
            'total_cash_amount_in_booking_payment' => $counterUpdates->sum('total_cash_amount_in_booking_payment'),
            'total_cash_amount_in_booking_payment_refunded' => $counterUpdates->sum(
                'total_cash_amount_in_booking_payment_refunded'
            ),
            'total_cash_amount_in_credit_note_refunded' => $counterUpdates->sum(
                'total_cash_amount_in_credit_note_refunded'
            ),
            'counter_update_ids' => $counterUpdates->pluck('id')->toArray(),
            'opening_balance' => $counterUpdates->sum('opening_balance'),
            'total_new_booking_payments' => $counterUpdates->sum('total_new_booking_payments'),
            'total_used_booking_payments' => $counterUpdates->sum('total_used_booking_payments'),
            'total_cancel_layaway_sales' => $counterUpdates->sum('total_cancel_layaway_sales'),
            'total_cancel_layaway_sales_amount' => $counterUpdates->sum('total_cancel_layaway_sales_amount'),
            ...$orderDetails,
        ]);
    }

    public function loadRelations(StoreDayClose $storeDayClose): StoreDayClose
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $storeDayClosePaymentQueries = resolve(StoreDayClosePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return $storeDayClose->load(
            'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
            'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            'payments:' . $storeDayClosePaymentQueries->getBasicColumnNames(),
            'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
        );
    }

    public function loadLocationRelation(StoreDayClose $storeDayClose): StoreDayClose
    {
        $locationQueries = resolve(LocationQueries::class);

        return $storeDayClose->load('location:' . $locationQueries->getBasicColumnNames());
    }

    public function getPaginatedDayCloseReportListForStoreManager(
        array $filterData,
        int $locationId
    ): LengthAwarePaginator {
        return $this->getDayCloseReportListForStoreManager($filterData, $locationId)->paginate($filterData['per_page']);
    }

    public function getDayCloseListForExportInStoreManagerPanel(array $filterData, int $locationId): Collection
    {
        return $this->getDayCloseReportListForStoreManager($filterData, $locationId)->get();
    }

    public function getDayCloseReportById(int $companyId, int $dayCloseId): StoreDayClose
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $storeDayClosePaymentQueries = resolve(StoreDayClosePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return StoreDayClose::query()
            ->select(
                'id',
                'location_id',
                'opened_at',
                'closed_at',
                'opening_balance',
                'closed_by_store_manager_id',
                'sales_collection_amount',
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
                'total_cash_amount_in_booking_payment',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_credit_note_refunded',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
                'created_at',
                'orders_collection_amount',
                'total_orders',
                'total_orders_amount',
                'total_layaway_orders',
                'total_layaway_orders_amount',
                'total_credit_orders',
                'total_credit_orders_amount',
                'total_cancelled_orders',
                'total_cancelled_orders_amount',
                'total_order_item_wise_discount_amount',
                'total_order_cart_wide_discount_amount',
                'total_order_tax_amount',
                'total_orders_round_off',
                'total_order_returns',
                'total_order_returns_amount',
                'total_order_returns_round_off',
                'total_order_complimentary_item_discount_used',
                'total_order_complimentary_item_discount_amount',
            )
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->with(
                'location:' . $locationQueries->getBasicColumnNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $storeDayClosePaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->findOrFail($dayCloseId);
    }

    private function dayCloseReportQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $storeDayClosePaymentQueries = resolve(StoreDayClosePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return StoreDayClose::query()
            ->select(
                'id',
                'location_id',
                'opened_at',
                'closed_at',
                'closed_by_store_manager_id',
                'sales_collection_amount',
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
                'total_cash_amount_in_sales',
                'total_cash_amount_in_booking_payment',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_credit_note_refunded',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_new_booking_payments',
                'total_used_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
                'created_at',
                'opening_balance',
                'orders_collection_amount',
                'total_orders',
                'total_orders_amount',
                'total_layaway_orders',
                'total_layaway_orders_amount',
                'total_credit_orders',
                'total_credit_orders_amount',
                'total_cancelled_orders',
                'total_cancelled_orders_amount',
                'total_order_item_wise_discount_amount',
                'total_order_cart_wide_discount_amount',
                'total_order_tax_amount',
                'total_orders_round_off',
                'total_order_returns',
                'total_order_returns_amount',
                'total_order_returns_round_off',
                'total_order_complimentary_item_discount_used',
                'total_order_complimentary_item_discount_amount',
            )
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->with(
                'location:' . $locationQueries->getBasicColumnNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $storeDayClosePaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->when($filterData['search_text'], function ($query) use (
                $locationQueries,
                $storeManagerQueries,
                $filterData
            ): void {
                $query->where(function ($query) use ($locationQueries, $storeManagerQueries, $filterData): void {
                    $query->whereHas('location', $locationQueries->searchByName($filterData['search_text']))
                        ->orWhereHas(
                            'storeManager',
                            $storeManagerQueries->searchByEmployeeName($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
            })
            ->when($filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('storeManager', function ($query) use ($filterData): void {
                    $query->where('employee_id', $filterData['employee_id']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('opened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
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

    private function getDayCloseReportListForStoreManager(array $filterData, int $locationId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $storeDayClosePaymentQueries = resolve(StoreDayClosePaymentQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return StoreDayClose::query()
            ->select(
                'id',
                'location_id',
                'opened_at',
                'closed_at',
                'closed_by_store_manager_id',
                'sales_collection_amount',
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
                'total_cash_amount_in_booking_payment',
                'total_cash_amount_in_booking_payment_refunded',
                'total_cash_amount_in_credit_note_refunded',
                'created_at',
                'opening_balance',
                'total_used_booking_payments',
                'total_new_booking_payments',
                'total_cancel_layaway_sales',
                'total_cancel_layaway_sales_amount',
                'orders_collection_amount',
                'total_orders',
                'total_orders_amount',
                'total_layaway_orders',
                'total_layaway_orders_amount',
                'total_credit_orders',
                'total_credit_orders_amount',
                'total_cancelled_orders',
                'total_cancelled_orders_amount',
                'total_order_item_wise_discount_amount',
                'total_order_cart_wide_discount_amount',
                'total_order_tax_amount',
                'total_orders_round_off',
                'total_order_returns',
                'total_order_returns_amount',
                'total_order_returns_round_off',
                'total_order_complimentary_item_discount_used',
                'total_order_complimentary_item_discount_amount',
            )
            ->whereHas('location', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->with(
                'location:' . $locationQueries->getBasicColumnNames(),
                'storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'storeManager.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'payments:' . $storeDayClosePaymentQueries->getBasicColumnNames(),
                'payments.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
            )
            ->when($filterData['search_text'], function ($query) use (
                $locationQueries,
                $storeManagerQueries,
                $filterData
            ): void {
                $query->where(function ($query) use ($locationQueries, $storeManagerQueries, $filterData): void {
                    $query->whereHas('location', $locationQueries->searchByName($filterData['search_text']))
                        ->orWhereHas(
                            'storeManager',
                            $storeManagerQueries->searchByEmployeeName($filterData['search_text'])
                        );
                });
            })
            ->when($filterData['store_manager_id'], function ($query) use ($filterData): void {
                $query->where('closed_by_store_manager_id', (int) $filterData['store_manager_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('opened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('opened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
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
}
