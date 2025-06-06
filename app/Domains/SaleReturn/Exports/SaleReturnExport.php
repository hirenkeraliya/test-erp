<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleReturnExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $saleReturns,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->saleReturns->map(function (SaleReturn $saleReturn): array {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $saleReturn->counterUpdate;
            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;
            /** @var Employee $employee */
            $employee = $cashier->employee;
            /** @var Counter $counter */
            $counter = $counterUpdate->counter;
            /** @var Location $location */
            $location = $counter->location;
            /** @var Member|null $member */
            $member = $saleReturn->member;
            /** @var Sale $sale */
            $sale = $saleReturn->originalSale;
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleReturn->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $saleReturnData = [
                'digital_invoice_number' => $saleReturn->digital_invoice_number ?: 'N/A',
                'offline_sale_return_id' => $saleReturn->getOfflineSaleReturnId(),
                'original_receipt_id' => $sale->offline_sale_id,
                'location' => $location->getName(),
                'counter' => $counter->getName(),
                'cashier' => $employee->getFullName(),
                'happened_at' => $happenedAt,
                'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
                'return_amount' => CommonFunctions::currencyFormat($saleReturn->getTotalPricePaid()),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($saleReturnData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
