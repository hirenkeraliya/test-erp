<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Models\CancelLayawaySale;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Employee;
use App\Models\Location;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CreditNoteExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $creditNotes,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->creditNotes->map(function (CreditNote $creditNote): array {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $creditNote->counterUpdate;
            /** @var ?SaleReturn $saleReturn */
            $saleReturn = $creditNote->saleReturn;
            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;
            /** @var Employee $employee */
            $employee = $cashier->getEmployee();
            /** @var Counter $counter */
            $counter = $counterUpdate->getCounter();
            /** @var Location $location */
            $location = $counter->location;
            $creditNoteExpiryDate = 'N/A';
            /** @var ?CancelLayawaySale $cancelLayawaySale */
            $cancelLayawaySale = $creditNote->cancelLayawaySale;
            if ($creditNote->expiry_date) {
                /** @var Carbon $creditNoteExpiryDate */
                $creditNoteExpiryDate = Carbon::createFromFormat('Y-m-d', $creditNote->expiry_date);
                $creditNoteExpiryDate = $creditNoteExpiryDate->format('d-m-Y');
            }

            /** @var Carbon $createdAt */
            $createdAt = $creditNote->created_at;

            $creditNoteData = [
                'digital_invoice_number' => $creditNote->digital_invoice_number ?: 'N/A',
                'id' => $creditNote->id,
                'receipt_id' => $saleReturn->offline_sale_return_id ?? $cancelLayawaySale->sale->offline_sale_id ?? 'N/A',
                'location' => $location->name,
                'counter' => $counter->getName(),
                'cashier' => $employee->getFullName(),
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'member' => $creditNote->member ? $creditNote->member->getFullName() : 'Walk in Member',
                'expiry_date' => $creditNoteExpiryDate,
                'total_amount' => CommonFunctions::currencyFormat((float) $creditNote->total_amount),
                'available_amount' => CommonFunctions::currencyFormat((float) $creditNote->available_amount),
                'status' => CreditNoteStatuses::getCaseNameByValue($creditNote->status),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($creditNoteData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
