<?php

declare(strict_types=1);

namespace App\Domains\Member\Exports;

use App\Domains\Common\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $members,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->members->map(function ($member): array {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $member->date);

            $memberReportData = [
                'date' => $date->format('d-m-Y'),
                'location' => $member->createdInLocation?->name,
                'members_count' => $member->members_count,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($memberReportData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
