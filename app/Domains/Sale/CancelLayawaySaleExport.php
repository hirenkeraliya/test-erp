<?php

declare(strict_types=1);

namespace App\Domains\Sale;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
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
use Maatwebsite\Excel\Concerns\WithHeadings;

class CancelLayawaySaleExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $cancelLayawaySales,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->cancelLayawaySales->map(function (Sale $cancelLayawaySale): array {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $cancelLayawaySale->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            /** @var Location $location */
            $location = $counter->location;

            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;

            /** @var Employee $employee */
            $employee = $cashier->employee;

            /** @var Member|null $member */
            $member = $cancelLayawaySale->member;

            /** @var ?StoreManager $storeManager */
            $storeManager = $cancelLayawaySale->layawayAuthorizer;

            /** @var ?Employee $storeManagerEmployee */
            $storeManagerEmployee = $storeManager instanceof StoreManager ? $storeManager->employee : null;

            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $cancelLayawaySale->getHappenedAt());
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');

            $cancelLayawaySaleData = [
                'digital_invoice_number' => $cancelLayawaySale->digital_invoice_number ?: 'N/A',
                'offline_sale_id' => $cancelLayawaySale->offline_sale_id,
                'bill_reference_number' => $cancelLayawaySale->bill_reference_number,
                'location' => $location->getName(),
                'counter' => $counter->getName(),
                'cashier' => $employee->getFullName(),
                'authorizer' => $storeManagerEmployee instanceof Employee ? $storeManagerEmployee->getFullName() : 'N/A',
                'happened_at' => $happenedAt,
                'member' => null !== $member ? $member->getFullName() : 'Walk In Member',
                'gross_sales' => CommonFunctions::currencyFormat($cancelLayawaySale->getGrossTotal()),
                'total_amount_paid' => CommonFunctions::currencyFormat($cancelLayawaySale->getTotalAmountPaid()),
                'layaway_pending_amount' => CommonFunctions::currencyFormat(
                    $cancelLayawaySale->getLayawayPendingAmount()
                ),
                'notes' => $cancelLayawaySale->notes ?? 'N/A',
                ...$this->getPayments($cancelLayawaySale->payments),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($cancelLayawaySaleData, $this->filteredColumns);
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
