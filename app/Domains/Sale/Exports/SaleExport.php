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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $sales,
        protected Collection $filteredColumns
    ) {
        $this->filteredColumns->push('payment_mode');
        $this->filteredColumns->push('paid');
    }

    public function collection(): Collection
    {
        return $this->sales->map(function (Sale $sale): array {
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

            /** @var Collection $saleItems */
            $saleItems = $sale->saleItems;

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $saleData = [
                'digital_invoice_number' => $sale->digital_invoice_number ?: 'N/A',
                'offline_sale_id' => $sale->getOfflineSaleId(),
                'bill_reference_number' => $sale->bill_reference_number,
                'location' => $location->getName(),
                'counter' => $counter->getName(),
                'cashier' => $employee->getFullName(),
                'happened_at' => $happenedAt,
                'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
                'gross_sales' => CommonFunctions::currencyFormat($sale->getGrossTotal()),
                'total_discount_amount' => CommonFunctions::currencyFormat($sale->getTotalDiscountAmount()),
                'units_sold' => $this->getTotalUnitsSold($saleItems),
                'units_returned' => $this->getTotalUnitsReturned($saleItems),
                'total_tax_amount' => CommonFunctions::currencyFormat($sale->getTotalTaxAmount()),
                'total_amount_paid' => CommonFunctions::currencyFormat($sale->getTotalAmountPaid()),
                'notes' => $sale->notes ?? 'N/A',
                'company' => $company->getName(),
                ...$this->getPayments($sale->payments),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($saleData, $this->filteredColumns);
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
                'payment_mode' => $salePayment->paymentType->name,
                'paid' => CommonFunctions::currencyFormat((float) $salePayment->amount),
            ]);
        });

        if ($payments->isEmpty()) {
            return [
                'payment_mode' => '',
                'paid' => '',
            ];
        }

        return $payments->collapse()->toArray();
    }

    private function getTotalUnitsSold(Collection $saleItems): float
    {
        $totalUnitsSold = $saleItems->sum(fn ($saleItem): ?float => $saleItem->getQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsSold);
    }

    private function getTotalUnitsReturned(Collection $saleItems): float
    {
        $totalUnitsReturned = $saleItems->sum(fn ($saleItem): ?float => $saleItem->getReturnedQuantity());

        return CommonFunctions::numberFormat((float) $totalUnitsReturned);
    }
}
