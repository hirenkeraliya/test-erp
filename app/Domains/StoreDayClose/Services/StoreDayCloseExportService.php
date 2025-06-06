<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Services;

use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StoreDayClose\Exports\StoreDayCloseFtpExport;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Product;
use App\Models\StoreDayClose;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class StoreDayCloseExportService
{
    public function storeDayCloseExport(StoreDayClose $storeDayClose): void
    {
        $storeDayCloseQuery = resolve(StoreDayCloseQueries::class);
        $storeDayClose = $storeDayCloseQuery->loadLocationRelation($storeDayClose);

        /** @var array $counterUpdateIds */
        $counterUpdateIds = $storeDayClose->counter_update_ids;

        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $sales = $saleQueries->getDayCloseSalesForExport($counterUpdateIds);
        $saleReturns = $saleReturnQueries->getDayCloseSaleReturnsForExport($counterUpdateIds);
        $creditNoteRefunds = $creditNoteRefundQueries->getDayCloseCreditNoteRefundForExport($counterUpdateIds);

        $exportRows = [];

        foreach ($sales as $sale) {
            $exportRows = array_merge(
                $exportRows,
                $this->preparedExportData(
                    $sale->saleItems,
                    $sale->counterUpdate,
                    $sale->getHappenedAt(),
                    $sale->offline_sale_id,
                    $sale->digital_invoice_number,
                    '01'
                )
            );
        }

        foreach ($saleReturns as $return) {
            $exportRows = array_merge(
                $exportRows,
                $this->preparedExportData(
                    $return->saleReturnItems,
                    $return->counterUpdate,
                    $return->getHappenedAt(),
                    $return->offline_sale_return_id,
                    $return->digital_invoice_number,
                    '02'
                )
            );
        }

        foreach ($creditNoteRefunds as $refund) {
            $creditNote = $refund->creditNote;
            $saleReturn = $creditNote->saleReturn;

            $exportRows = array_merge(
                $exportRows,
                $this->preparedExportData(
                    $saleReturn->saleReturnItems,
                    $refund->counterUpdate,
                    $saleReturn->getHappenedAt(),
                    $saleReturn->offline_sale_return_id,
                    $creditNote->digital_invoice_number,
                    '04'
                )
            );
        }

        $sftpConfig = config('services.sftp');

        if (
            empty($sftpConfig) ||
            empty($sftpConfig['ip_address']) &&
            empty($sftpConfig['username']) &&
            empty($sftpConfig['password']) &&
            empty($sftpConfig['port'])
        ) {
            return;
        }

        $filePath = $this->generateAndStoreExcel($storeDayClose->id, $exportRows);

        $this->uploadInFtp($filePath, $storeDayClose);
    }

    private function preparedExportData(
        Collection $items,
        CounterUpdate $counterUpdate,
        string $happenedAt,
        string $invoiceNumber,
        string $digitalInvoiceNumber,
        string $type
    ): array {
        /** @var Counter $counter */
        $counter = $counterUpdate->getCounter();

        /** @var Location $location */
        $location = $counter->location;

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $formattedDate = now()->parse($happenedAt)->format('d-m-Y h:i:s A');

        return $items->map(function ($item) use (
            $company,
            $location,
            $currency,
            $formattedDate,
            $invoiceNumber,
            $digitalInvoiceNumber,
            $type
        ): array {
            /** @var Product $product */
            $product = $item->product;

            return [
                'supplier_code' => $company->code,
                'buyer_code' => '',
                'annex_k1' => '',
                'annex_incoterms' => '',
                'annex_fta' => '',
                'annex_atiga' => '',
                'annex_k2' => '',
                'annex_other_charges_description' => '',
                'annex_other_charges_amount' => '',
                'header_source' => 'JPOS',
                'header_einvoice_type' => $type,
                'header_invoice_number' => $digitalInvoiceNumber,
                'header_original_invoice_number' => $invoiceNumber,
                'header_invoice_date' => $formattedDate,
                'header_outlet_code' => $location->code,
                'header_currency_code' => $currency->code,
                'header_currency_rate' => 1,
                'header_discount_amount' => $item->total_discount_amount,
                'header_charge_amount' => 0,
                'header_total_payable_amount' => $item->total_price_paid,
                'header_payment_terms' => '',
                'item_classification' => '',
                'item_code' => '01',
                'item_description' => $product->name,
                'item_quantity' => $item->quantity,
                'item_unit_price' => '01' === $type ? $item->original_price_per_unit : '',
                'item_discount_amount' => $item->item_discount_amount,
                'item_charge_amount' => 0,
                'item_tax_type' => '',
                'item_tax_rate' => '',
                'item_details_tax_exemption' => '',
                'item_exempt_tax_amount' => '',
            ];
        })->toArray();
    }

    private function generateAndStoreExcel(int $storeDayCloseId, array $rows): string
    {
        $filePath = 'store_day_close_export/' . $storeDayCloseId . '.csv';
        Storage::disk(config('filesystems.default'))->makeDirectory('store_day_close_export');
        Excel::store(new StoreDayCloseFtpExport(collect($rows)), $filePath, config('filesystems.default'));

        return $filePath;
    }

    private function uploadInFtp(string $filePath, StoreDayClose $storeDayClose): void
    {
        $fileContent = Storage::disk(config('filesystems.default'))->get($filePath);
        /** @var Location $location */
        $location = $storeDayClose->location;

        $locationCode = Str::upper($location->code);
        $destinationFilePath = now()->format('Y') . '/'. Str::upper(
            now()->format('M')
        ) . '/' . $locationCode . '/B2C_' . $locationCode . '_' . now()->format('Ymd') . '.csv';

        // for uploading the file to FTP server
        if ($fileContent) {
            Storage::disk('e_invoice_summary_ftp')->put($destinationFilePath, $fileContent);
            Storage::disk(config('filesystems.default'))->delete($filePath);
        }
    }
}
