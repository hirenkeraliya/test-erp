<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Exports;

use App\Models\Counter;
use App\Models\CounterUpdate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreDayClosePageExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $storeDayCloses
    ) {
    }

    public function collection(): Collection
    {
        return $this->storeDayCloses->map(function (CounterUpdate $counterUpdate): array {
            /** @var Counter $counter */
            $counter = $counterUpdate->getCounter();

            $closedCounterAt = $counterUpdate->closed_by_pos_at ?? $counterUpdate->closed_at;

            $closedAt = null;

            if ($closedCounterAt) {
                /** @var Carbon $closedAtFormat */
                $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $closedCounterAt);
                $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');
            }

            $openedAt = '';

            if ($counterUpdate->opened_by_pos_at) {
                /** @var Carbon $openedAtFormat */
                $openedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $counterUpdate->opened_by_pos_at);
                $openedAt = $openedAtFormat->format('d-m-Y h:i:s A');
            }

            /** @var Carbon $createdAt */
            $createdAt = $counterUpdate->created_at ? $counterUpdate->created_at->format('d-m-Y h:i:s A') : '';

            return [
                'counter_name' => $counter->getName(),
                'opened_at' => $openedAt ?: $createdAt,
                'closed_at' => $closedAt,
                'opening_balance' => $counterUpdate->opening_balance,
                'closing_balance' => $counterUpdate->closing_balance,
            ];
        });
    }

    public function headings(): array
    {
        return ['Counter Name', 'Opened At', 'Closed At', 'Opening Balance', 'Closing Balance'];
    }
}
