<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Exports;

use App\CommonFunctions;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\Common\Services\ExportService;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashMovementExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $cashMovements,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->cashMovements->map(function (CashMovement $cashMovement): array {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $cashMovement->getCounterUpdate();
            /** @var Counter $counter */
            $counter = $counterUpdate->getCounter();
            /** @var Location $location */
            $location = $counter->getLocation();
            $employee = null;
            /** @var StoreManager|Director|null $authorizer */
            $authorizer = $cashMovement->getAuthorizer();
            $employee = $authorizer?->getEmployee();
            /** @var CashMovementReason $cashMovementReason */
            $cashMovementReason = $cashMovement->getCashMovementReason();
            $happenedAt = '';
            if ($cashMovement->getHappenedAt()) {
                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $cashMovement->getHappenedAt());
                $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
            }

            $cashMovementData = [
                'id' => $cashMovement->getKey(),
                'counter_name' => $counter->getName(),
                'location' => $location->getName(),
                'authorizer' => null !== $cashMovement->authorizer_type && $employee instanceof Employee ?
                    $cashMovement->authorizer_type . ': ' . $employee->getFullName() : 'SYSTEM GENERATED',
                'type' => CashMovementTypes::getFormattedCaseName($cashMovement->getCashMovementTypeId()),
                'happened_at' => $happenedAt,
                'cash_movement_reason' => $cashMovement->getCashMovementReasonId() ? $cashMovementReason->getReason() : 'N/A',
                'other_reason' => $cashMovement->getOtherReason() ?? 'N/A',
                'remarks' => $cashMovement->remarks ?? 'N/A',
                'amount' => CommonFunctions::currencyFormat($cashMovement->getAmount()),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($cashMovementData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
