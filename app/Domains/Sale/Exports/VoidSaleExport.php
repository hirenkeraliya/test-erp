<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\StoreManager;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoidSaleExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $voidSales,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->voidSales->map(function (Sale $sale): array {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $sale->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            /** @var Location $location */
            $location = $counter->location;

            /** @var Company $company */
            $company = $location->company;

            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;

            /** @var Employee $employee */
            $employee = $cashier->employee;

            /** @var Member|null $member */
            $member = $sale->member;

            /** @var VoidSale $voidSale */
            $voidSale = $sale->voidSale;

            /** @var VoidSaleReason $voidSaleReason */
            $voidSaleReason = $voidSale->voidSaleReason;

            /** @var StoreManager $storeManager */
            $storeManager = $voidSale->voidedByStoreManager;

            /** @var Employee $storeManagerEmployee */
            $storeManagerEmployee = $storeManager->employee;

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $voidSaleData = [
                'digital_invoice_number' => $sale->digital_invoice_number ?: 'N/A',
                'offline_sale_id' => $sale->getOfflineSaleId(),
                'bill_reference_number' => $sale->bill_reference_number,
                'void_sale_number' => $voidSale->getVoidSaleNumber($company->getVoidSaleNumberPrefix()),
                'location' => $location->getName(),
                'counter' => $counter->getName(),
                'cashier' => $employee->getFullName(),
                'happened_at' => $happenedAt,
                'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
                'void_reason' => $voidSaleReason->getReason(),
                'voided_by' => $storeManagerEmployee->getFullName(),
                'total_amount_paid' => CommonFunctions::currencyFormat($sale->getTotalAmountPaid()),
                ...$this->getPayments($sale->payments),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($voidSaleData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }

    /**
     * @return mixed[]
     */
    private function getPayments(Collection $salePayments): array
    {
        $payments = collect([]);
        $salePayments->each(function ($salePayment, string $key) use ($payments): void {
            $payments->push([
                'name' . $key => $salePayment->paymentType->name,
                'amount' . $key => CommonFunctions::currencyFormat((float) $salePayment->amount),
            ]);
        });

        return $payments->collapse()->toArray();
    }
}
