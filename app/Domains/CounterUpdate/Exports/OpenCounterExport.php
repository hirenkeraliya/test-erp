<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Exports;

use App\CommonFunctions;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OpenCounterExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $openCounters
    ) {
    }

    public function collection(): Collection
    {
        return $this->openCounters->map(function (CounterUpdate $counterUpdate): array {
            /** @var Counter $counter */
            $counter = $counterUpdate->counter;
            /** @var Location $location */
            $location = $counter->location;
            /** @var Cashier $cashier */
            $cashier = $counterUpdate->cashier;
            /** @var Employee $employee */
            $employee = $cashier->employee;

            return [
                'location_name' => $location->name,
                'counter_name' => $counter->name,
                'cashier_name' => $employee->getFullName(),
                'opening_balance' => CommonFunctions::numberFormat((float) $counterUpdate->opening_balance),
            ];
        });
    }

    public function headings(): array
    {
        return ['Location Name', 'Counter Name', 'Cashier Name', 'Opening Balance'];
    }
}
