<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClosedCounterExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $closedCounters,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->closedCounters->map(function (CounterUpdate $closedCounter): array {
            /** @var Counter $counter */
            $counter = $closedCounter->getCounter();
            /** @var Location $location */
            $location = $counter->getLocation();
            /** @var Cashier $cashier */
            $cashier = $closedCounter->getCashier();
            /** @var Employee $employee */
            $employee = $cashier->getEmployee();
            /** @var Carbon $createdAt */
            $createdAt = $closedCounter->created_at;
            /** @var Carbon|string $closedAt */
            $closedAt = 'N/A';
            if ($closedCounter->closed_at) {
                /** @var Carbon $closedAtFormat */
                $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $closedCounter->closed_at);
                $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
            }

            /** @var Collection $counterUpdateDeclarationAttempts */
            $counterUpdateDeclarationAttempts = $closedCounter->counterUpdateDeclarationAttempts;

            $closedCounterData = [
                'id' => $closedCounter->id,
                'counter' => $counter->name,
                'cashier_name' => $employee->getFullName(),
                'location_name' => $location->name,
                'opening_balance' => CommonFunctions::currencyFormat((float) $closedCounter->opening_balance),
                'closing_balance' => CommonFunctions::currencyFormat((float) $closedCounter->closing_balance),
                'sales_collection_amount' => $closedCounter->sales_collection_amount,
                'opened_at' => $createdAt->format('d-m-Y h:i:s A'),
                'closed_at' => $closedAt,
                'mismatch_amount' => CommonFunctions::currencyFormat((float) $closedCounter->mismatch_amount),
                'attempt_count' => $counterUpdateDeclarationAttempts->count(),
                'reason' => $closedCounter->amount_mismatch_reason,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($closedCounterData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
