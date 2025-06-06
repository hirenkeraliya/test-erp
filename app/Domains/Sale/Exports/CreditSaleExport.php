<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Sale;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CreditSaleExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $creditSales,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->creditSales->map(function (Sale $creditSale): array {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $creditSale->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            /** @var Location $location */
            $location = $counter->location;

            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;

            /** @var Employee $employee */
            $employee = $cashier->employee;

            /** @var Member|null $member */
            $member = $creditSale->member;

            /** @var ?StoreManager $storeManager */
            $storeManager = $creditSale->creditAuthorizer;

            /** @var ?Employee $storeManagerEmployee */
            $storeManagerEmployee = $storeManager instanceof StoreManager ? $storeManager->employee : null;

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $creditSale->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $creditSaleData = [
                'digital_invoice_number' => $creditSale->digital_invoice_number ?: 'N/A',
                'offline_sale_id' => $creditSale->offline_sale_id,
                'bill_reference_number' => $creditSale->bill_reference_number,
                'location' => $location->getName(),
                'counter' => $counter->getName(),
                'cashier' => $employee->getFullName(),
                'status' => $creditSale->status === SaleStatus::PENDING_CREDIT_SALE->value ? CreditAndLayawaySaleStatuses::getCaseName(
                    CreditAndLayawaySaleStatuses::PENDING->value
                ) : CreditAndLayawaySaleStatuses::getCaseName(CreditAndLayawaySaleStatuses::COMPLETE->value),
                'authorizer' => $storeManagerEmployee instanceof Employee ? $storeManagerEmployee->getFullName() : 'N/A',
                'happened_at' => $happenedAt,
                'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
                'gross_sales' => CommonFunctions::currencyFormat($creditSale->getGrossTotal()),
                'net_sales' => $creditSale->getCreditSaleTotalAmount(),
                'total_amount_paid' => CommonFunctions::currencyFormat($creditSale->getTotalAmountPaid()),
                'credit_pending_amount' => CommonFunctions::currencyFormat($creditSale->getCreditPendingAmount()),
                'notes' => $creditSale->notes ?? 'N/A',
                ...$this->getPayments($creditSale->payments),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($creditSaleData, $this->filteredColumns);
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
