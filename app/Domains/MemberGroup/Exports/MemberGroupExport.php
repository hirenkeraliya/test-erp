<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Exports;

use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Models\MemberGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberGroupExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $memberGroups
    ) {
    }

    public function collection(): Collection
    {
        return $this->memberGroups->map(fn (MemberGroup $memberGroup): array => [
            'name' => $memberGroup->name,
            'code' => $memberGroup->code,
            'type' => GroupTypes::getFormattedCaseName($memberGroup->type_id),
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code', 'Type'];
    }
}
