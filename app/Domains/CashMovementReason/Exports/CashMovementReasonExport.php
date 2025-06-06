<?php

declare(strict_types=1);

namespace App\Domains\CashMovementReason\Exports;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Models\CashMovementReason;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashMovementReasonExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $cashMovementReasons
    ) {
    }

    public function collection(): Collection
    {
        return $this->cashMovementReasons->map(fn (CashMovementReason $cashMovementReason): array => [
            'reason' => $cashMovementReason->reason,
            'type' => $cashMovementReason->type_id ? CashMovementTypes::getFormattedCaseName(
                $cashMovementReason->type_id
            ) : 'N/A',
        ]);
    }

    public function headings(): array
    {
        return ['Reason', 'Type'];
    }
}
