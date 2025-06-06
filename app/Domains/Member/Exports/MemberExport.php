<?php

declare(strict_types=1);

namespace App\Domains\Member\Exports;

use App\Domains\Member\Services\MemberService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $members
    ) {
    }

    public function collection(): Collection
    {
        $memberService = resolve(MemberService::class);

        return $memberService->preparedMemberRecords($this->members);
    }

    public function headings(): array
    {
        return [
            'type',
            'title',
            'race',
            'first_name',
            'last_name',
            'gender',
            'date_of_birth',
            'mobile_number',
            'email',
            'company_name',
            'company_registration_number',
            'company_tax_number',
            'company_address',
            'company_phone',
            'created_location',
            'notes',
            'loyalty_points',
            'card_number',
        ];
    }
}
