<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Exports;

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Models\AutomatedNotification;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AutomatedNotificationExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $automatedNotifications
    ) {
    }

    public function collection(): Collection
    {
        return $this->automatedNotifications->map(fn (AutomatedNotification $automatedNotification): array => [
            'type' => AutomatedNotificationTypes::getFormattedCaseName($automatedNotification->type_id),
            'name' => $automatedNotification->name,
            'description' => $automatedNotification->description,
            'timeframe_type' => AutomatedNotificationTimeframeTypes::getFormattedCaseName(
                (int) $automatedNotification->timeframe_type_id
            ),
        ]);
    }

    public function headings(): array
    {
        return ['Type', 'Name', 'Description', 'Timeframe'];
    }
}
