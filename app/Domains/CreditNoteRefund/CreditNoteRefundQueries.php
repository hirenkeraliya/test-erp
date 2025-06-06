<?php

declare(strict_types=1);

namespace App\Domains\CreditNoteRefund;

use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Models\CreditNoteRefund;
use Illuminate\Support\Collection;

class CreditNoteRefundQueries
{
    public function getByCounterUpdateIdWithPaymentType(int $counterUpdateId): Collection
    {
        $paymentTypeQueries = resolve(PaymentTypeQueries::class);

        return CreditNoteRefund::select('id', 'payment_type_id', 'amount')
            ->where('counter_update_id', $counterUpdateId)
            ->with('paymentType:' . $paymentTypeQueries->getBasicColumnNames())
            ->get();
    }

    public function addNew(int $creditNoteId, int $counterUpdateId, CreditNoteRefundData $creditNoteRefundData): void
    {
        CreditNoteRefund::create([
            'credit_note_id' => $creditNoteId,
            'counter_update_id' => $counterUpdateId,
            'payment_type_id' => $creditNoteRefundData->payment_type_id,
            'amount' => $creditNoteRefundData->amount,
            'store_manager_id' => $creditNoteRefundData->store_manager_id,
            'currency_id' => $creditNoteRefundData->currency_id,
            'currency_rate' => $creditNoteRefundData->current_currency_rate,
            'currency_amount' => $creditNoteRefundData->currency_amount,
        ]);
    }

    public function addNewForCancelLayawaySale(
        int $creditNoteId,
        int $counterUpdateId,
        int $paymentTypeId,
        float $amount,
        int $storeManagerId
    ): void {
        CreditNoteRefund::create([
            'credit_note_id' => $creditNoteId,
            'counter_update_id' => $counterUpdateId,
            'payment_type_id' => $paymentTypeId,
            'amount' => $amount,
            'store_manager_id' => $storeManagerId,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,credit_note_id,payment_type_id,amount,store_manager_id,created_at,currency_id,currency_rate,currency_amount';
    }

    public function getDayCloseCreditNoteRefundForExport(array $counterUpdateIds): Collection
    {
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return CreditNoteRefund::query()
            ->select('id', 'credit_note_id', 'counter_update_id', 'amount', 'store_manager_id')
            ->with([
                'creditNote:' . $creditNoteQueries->getBasicColumnNames(),
                'creditNote.saleReturn:' . $saleReturnQueries->getOfflineIdAndSaleReturnIdColumnNames(),
                'creditNote.saleReturn.saleReturnItems:' . $saleReturnItemQueries->getColumnNamesForPos(),
                'creditNote.saleReturn.saleReturnItems.product:' . $productQueries->getBasicColumnNames(),
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForAdminSaleReports(),
                'counterUpdate.counter.location.company.defaultCountry.currency:'. $currencyQueries->getBasicColumnNames(),
            ])
            ->whereIn('counter_update_id', $counterUpdateIds)
            ->get();
    }
}
