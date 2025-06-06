<?php

declare(strict_types=1);

namespace App\Domains\CreditNote;

use App\CommonFunctions;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\CancelLayawaySale\CancelLayawaySaleQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteExpiration\CreditNoteExpirationQueries;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\CreditNote;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CreditNoteQueries
{
    public function getPaginatedListOfActiveCreditNotes(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return CreditNote::select(
            'id',
            'counter_update_id',
            'sale_return_id',
            'expiry_date',
            'total_amount',
            'available_amount',
            'status',
            'member_id'
        )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'creditNoteRefund:' . $creditNoteRefundQueries->getBasicColumnNames(),
                'creditNoteRefund.currency:' . $currencyQueries->getBasicColumnNames(),
                'creditNoteRefund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'creditNoteRefund.storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'creditNoteRefund.storeManager.employee:' . $employeeQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'saleReturn.originalSale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', $filterData['member_id']);
            })->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->with('mismatches')
            ->orderBy('id', 'desc')
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->whereHas('member', $this->searchByName($filterData['search_text']));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            }, function ($query): void {
                $query->where('status', CreditNoteStatuses::ACTIVE->value);
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(
        int $counterUpdateId,
        int $saleReturnId,
        string $digitalInvoiceNumber,
        float $amount,
        string $happenedAt,
        ?int $creditNoteExpirationDays,
        ?int $member_id,
    ): CreditNote {
        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        $expiryDate = $creditNoteExpirationDays > 0 && $carbonDate
            ? $carbonDate->addDays($creditNoteExpirationDays)->format('Y-m-d')
            : null;

        return CreditNote::create([
            'counter_update_id' => $counterUpdateId,
            'sale_return_id' => $saleReturnId,
            'member_id' => $member_id,
            'expiry_date' => $expiryDate,
            'total_amount' => $amount,
            'available_amount' => $amount,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function addNewForCancelLayawaySale(
        int $counterUpdateId,
        int $cancelLayawaySaleId,
        string $digitalInvoiceNumber,
        float $amount,
        string $happenedAt,
        ?int $creditNoteExpirationDays,
        ?int $member_id,
    ): CreditNote {
        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        $expiryDate = $creditNoteExpirationDays > 0 && $carbonDate
            ? $carbonDate->addDays($creditNoteExpirationDays)->format('Y-m-d')
            : null;

        return CreditNote::create([
            'counter_update_id' => $counterUpdateId,
            'cancel_layaway_sale_id' => $cancelLayawaySaleId,
            'member_id' => $member_id,
            'expiry_date' => $expiryDate,
            'total_amount' => $amount,
            'available_amount' => $amount,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function addNewForCancelCreditSale(
        int $counterUpdateId,
        int $cancelCreditSaleId,
        string $digitalInvoiceNumber,
        float $amount,
        string $happenedAt,
        ?int $creditNoteExpirationDays,
        ?int $member_id,
    ): CreditNote {
        $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        $expiryDate = $creditNoteExpirationDays > 0 && $carbonDate
            ? $carbonDate->addDays($creditNoteExpirationDays)->format('Y-m-d')
            : null;

        return CreditNote::create([
            'counter_update_id' => $counterUpdateId,
            'cancel_credit_sale_id' => $cancelCreditSaleId,
            'member_id' => $member_id,
            'expiry_date' => $expiryDate,
            'total_amount' => $amount,
            'available_amount' => $amount,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'digital_invoice_number' => $digitalInvoiceNumber,
        ]);
    }

    public function getById(int $creditNoteId): CreditNote
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return CreditNote::query()
            ->select(
                'id',
                'sale_return_id',
                'counter_update_id',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'saleReturn.originalSale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->findOrFail($creditNoteId);
    }

    public function markAsRefunded(CreditNote $creditNote): void
    {
        $creditNote->status = CreditNoteStatuses::REFUNDED->value;
        $creditNote->available_amount = 0;
        $creditNote->save();
    }

    public function getByIds(array $creditNoteIds, int $locationId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);

        return CreditNote::query()
            ->select(
                'id',
                'sale_return_id',
                'counter_update_id',
                'total_amount',
                'available_amount',
                'status',
                'expiry_date',
                'member_id'
            )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'saleReturn.originalSale:' . $saleQueries->getOfflineSaleId(),
                'mismatches',
                'creditNoteRefund:' . $creditNoteRefundQueries->getBasicColumnNames(),
                'creditNoteRefund.currency:' . $currencyQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounter($locationId))
            ->whereIntegerInRaw('id', $creditNoteIds)
            ->get();
    }

    public function decreaseAvailableAmountAndMarkAsUsed(CreditNote $creditNote, float $amount): void
    {
        $creditNote->available_amount -= $amount;

        if ($creditNote->available_amount <= 0) {
            $creditNote->status = CreditNoteStatuses::USED->value;
        }

        $creditNote->save();
    }

    public function markAseExpired(CreditNote $creditNote): void
    {
        $creditNote->status = CreditNoteStatuses::EXPIRED->value;
        $creditNote->save();
    }

    public function getActiveWithExpirationDue(): Collection
    {
        $memberQueries = resolve(MemberQueries::class);

        return CreditNote::select('id', 'total_amount', 'available_amount', 'status', 'member_id')
            ->where('status', CreditNoteStatuses::ACTIVE->value)
            ->with(['member:' . $memberQueries->getBasicColumnNamesForPosSale()])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->format('Y-m-d'))
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_update_id,sale_return_id,expiry_date,total_amount,available_amount,status,cancel_layaway_sale_id,member_id,cancel_credit_sale_id,digital_invoice_submitted';
    }

    public function getCancelLayawaySaleColumn(): string
    {
        return 'id,cancel_layaway_sale_id';
    }

    public function getCreditNoteDetails(int $locationId, int $companyId, int|string $creditNoteId): ?CreditNote
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return CreditNote::select(
            'id',
            'counter_update_id',
            'sale_return_id',
            'expiry_date',
            'total_amount',
            'available_amount',
            'status',
            'member_id'
        )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'saleReturn.originalSale:' . $saleQueries->getOfflineSaleId(),
                'creditNoteRefund:' . $creditNoteRefundQueries->getBasicColumnNames(),
                'creditNoteRefund.currency:' . $currencyQueries->getBasicColumnNames(),
            ])
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->findOrFail($creditNoteId);
    }

    public function getPaginatedListByCompanyWithRelations(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getCreditNoteQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getSumOfAvailableAmountByCompany(array $filterData, int $companyId): float
    {
        return (float) $this->getCreditNoteQuery($filterData, $companyId)->sum('available_amount');
    }

    private function getCreditNoteQuery(array $filterData, int $companyId): Builder
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $creditNoteExpirationQueries = resolve(CreditNoteExpirationQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cancelLayawaySaleQueries = resolve(CancelLayawaySaleQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);

        return CreditNote::select(
            'id',
            'counter_update_id',
            'sale_return_id',
            'cancel_layaway_sale_id',
            'expiry_date',
            'total_amount',
            'available_amount',
            'status',
            'created_at',
            'member_id',
            'digital_invoice_submitted',
            'digital_invoice_number',
        )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'uses:' . $creditNoteUseQueries->getColumnNamesForReports(),
                'creditNoteRefund:' . $creditNoteRefundQueries->getBasicColumnNames(),
                'creditNoteRefund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'creditNoteRefund.storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'creditNoteRefund.storeManager.employee:' . $employeeQueries->getBasicColumnNames(),
                'creditNoteExpiration:' . $creditNoteExpirationQueries->getBasicColumnNames(),
                'uses.salePayment:' . $salePaymentQueries->getSaleIdColumn(),
                'uses.BookingPaymentPayment:' . $bookingPaymentPaymentQueries->getBookingPaymentIdColumn(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'cancelLayawaySale:' . $cancelLayawaySaleQueries->getSaleIdColumn(),
                'cancelLayawaySale.sale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when(
                array_key_exists(
                    'credit_note_id',
                    $filterData
                ) && (null !== $filterData['credit_note_id'] && '0' !== $filterData['credit_note_id'] && 0 !== $filterData['credit_note_id']),
                function ($query) use ($filterData): void {
                    $query->where('id', (int) $filterData['credit_note_id']);
                }
            )
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['status_id'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function loadMismatches(CreditNote $creditNote): CreditNote
    {
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);

        return $creditNote->load([
            'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
            'creditNoteRefund:' . $creditNoteRefundQueries->getBasicColumnNames(),
            'creditNoteRefund.currency:' . $currencyQueries->getBasicColumnNames(),
        ]);
    }

    public function getCreditNoteListByCompanyWithRelationsForExport(array $filterData, int $companyId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cancelLayawaySaleQueries = resolve(CancelLayawaySaleQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        return CreditNote::select(
            'id',
            'counter_update_id',
            'sale_return_id',
            'cancel_layaway_sale_id',
            'expiry_date',
            'total_amount',
            'available_amount',
            'status',
            'created_at',
            'member_id',
            'digital_invoice_number'
        )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'creditNoteRefund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'creditNoteRefund.storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'cancelLayawaySale:' . $cancelLayawaySaleQueries->getSaleIdColumn(),
                'cancelLayawaySale.sale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('id', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCompanyId($companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData, $counterQueries): void {
                $query->whereHas('counterUpdate', function ($query) use ($filterData, $counterQueries): void {
                    $query->select('id', 'counter_id')
                        ->whereHas('counter', $counterQueries->filterByLocations($filterData['location_ids']));
                });
            })
            ->when(null !== $filterData['e_invoice_submitted'], function ($query) use ($filterData): void {
                $query->where('digital_invoice_submitted', (bool) $filterData['e_invoice_submitted']);
            })
            ->when(
                array_key_exists(
                    'credit_note_id',
                    $filterData
                ) && (null !== $filterData['credit_note_id'] && '0' !== $filterData['credit_note_id'] && 0 !== $filterData['credit_note_id']),
                function ($query) use ($filterData): void {
                    $query->where('id', (int) $filterData['credit_note_id']);
                }
            )
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
            ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                );
            })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['status_id'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                $query->whereHas('member', function ($query) use ($filterData): void {
                    $query->where('employee_id', (int) $filterData['employee_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getPaginatedListByCompanyWithRelationsForStoreManager(
        array $filterData,
        int $companyId,
        int $locationId
    ): LengthAwarePaginator {
        return $this->getCreditNotesListForStoreManager($filterData, $companyId, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function getCreditNoteListByCompanyWithRelationsForExportInStoreManagerPanel(
        array $filterData,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->getCreditNotesListForStoreManager($filterData, $companyId, $locationId)->get();
    }

    public function incrementAvailableAmountAndActivate(int $creditNoteId, float $amount): void
    {
        $creditNote = CreditNote::findOrFail($creditNoteId);

        $creditNote->available_amount += $amount;
        $creditNote->status = CreditNoteStatuses::ACTIVE->value;
        $creditNote->save();
    }

    public function digitalInvoiceUpdate(int $creditNoteId): void
    {
        $creditNote = CreditNote::select('id', 'digital_invoice_submitted')
            ->where('digital_invoice_submitted', false)
            ->findOrFail($creditNoteId);
        $creditNote->update([
            'digital_invoice_submitted' => true,
        ]);
    }

    public function getCreditNoteReturnByStoreIdCounterId(
        string $receiptNumber,
        int $locationId,
        int $counterId
    ): ?CreditNote {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return CreditNote::select('id', 'digital_invoice_submitted')
            ->whereHas('saleReturn', function ($query) use ($receiptNumber): void {
                $query->select('id', 'offline_sale_return_id')
                    ->where('offline_sale_return_id', $receiptNumber);
            })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByCounterIdAndLocationId($locationId, $counterId))
            ->first();
    }

    public function getBasicColumnForDigitalInvoice(): Closure
    {
        return fn ($query) => $query->select(
            'id',
            'digital_invoice_number',
            'sale_return_id',
            'cancel_layaway_sale_id'
        );
    }

    private function getCreditNotesListForStoreManager(array $filterData, int $companyId, int $locationId): Builder
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $creditNoteExpirationQueries = resolve(CreditNoteExpirationQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $cancelLayawaySaleQueries = resolve(CancelLayawaySaleQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $bookingPaymentPaymentQueries = resolve(BookingPaymentPaymentQueries::class);

        return CreditNote::select(
            'id',
            'counter_update_id',
            'sale_return_id',
            'cancel_layaway_sale_id',
            'expiry_date',
            'total_amount',
            'available_amount',
            'status',
            'created_at',
            'member_id',
            'digital_invoice_number'
        )
            ->with([
                'member:' . $memberQueries->getBasicColumnNamesForSale(),
                'uses:' . $creditNoteUseQueries->getColumnNamesForReports(),
                'creditNoteRefund:' . $creditNoteRefundQueries->getBasicColumnNames(),
                'creditNoteRefund.paymentType:' . $paymentTypeQueries->getBasicColumnNames(),
                'creditNoteRefund.storeManager:' . $storeManagerQueries->getEmployeeIdColumnNames(),
                'creditNoteRefund.storeManager.employee:' . $employeeQueries->getBasicColumnNames(),
                'creditNoteExpiration:' . $creditNoteExpirationQueries->getBasicColumnNames(),
                'uses.salePayment:' . $salePaymentQueries->getSaleIdColumn(),
                'uses.BookingPaymentPayment:' . $bookingPaymentPaymentQueries->getBookingPaymentIdColumn(),
                'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'mismatches:' . $posMismatchQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'cancelLayawaySale:' . $cancelLayawaySaleQueries->getSaleIdColumn(),
                'cancelLayawaySale.sale:' . $saleQueries->getOfflineSaleId(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('id', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('member', $this->searchByName($filterData['search_text']));
                });
            })
            ->whereHas('counterUpdate', $counterUpdateQueries->filterByStoreIdAndCompanyId($locationId, $companyId))
            ->when($filterData['counter_ids'], function ($query) use ($filterData, $counterUpdateQueries): void {
                $query->whereHas(
                    'counterUpdate',
                    $counterUpdateQueries->filterByCounterIds((array) $filterData['counter_ids'])
                );
            })
                ->when($filterData['cashier_id'], function ($query) use ($filterData, $counterUpdateQueries): void {
                    $query->whereHas(
                        'counterUpdate',
                        $counterUpdateQueries->filterByCashierId((int) $filterData['cashier_id'])
                    );
                })
            ->when($filterData['member_id'], function ($query) use ($filterData): void {
                $query->where('member_id', (int) $filterData['member_id']);
            })
            ->when($filterData['status_id'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['status_id']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
        ->whereAny(['first_name', 'last_name'], 'LIKE', '%' . $searchText . '%');
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $creditNotes = CreditNote::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($creditNotes as $creditNote) {
            $creditNote->member_id = $newMemberId;
            $creditNote->save();
        }
    }
}
