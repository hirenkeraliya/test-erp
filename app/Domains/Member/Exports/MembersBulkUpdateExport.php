<?php

declare(strict_types=1);

namespace App\Domains\Member\Exports;

use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MembersBulkUpdateExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        protected Collection $members
    ) {
    }

    public function styles(Worksheet $sheet): void
    {
        $headerCellNumbers = $this->getRequiredFieldsHeaderCellNumbers();

        foreach ($headerCellNumbers as $headerCellNumber) {
            $sheet->getStyle($headerCellNumber)
                ->getFont()
                ->getColor()
                ->setARGB(Color::COLOR_RED);
        }
    }

    public function collection(): Collection
    {
        return $this->members->map(fn (Member $member): array => [
            'type' => $member->type_id ? Types::getFormattedCaseName($member->type_id) : null,
            'title' => $member->title_id ? Titles::getFormattedCaseName($member->title_id) : null,
            'race' => $member->race_id ? Races::getFormattedCaseName($member->race_id) : null,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'gender' => $member->gender_id ? Genders::getFormattedCaseName($member->gender_id) : null,
            'date_of_birth' => $member->date_of_birth,
            'mobile_number' => $member->mobile_number,
            'email' => $member->email,
            'company_name' => $member->company_name,
            'company_registration_number' => $member->company_registration_number,
            'company_tax_number' => $member->company_tax_number,
            'company_address' => $member->company_address,
            'company_phone' => $member->company_phone,
            'pic_name' => $member->pic_name,
            'pic_contact' => $member->pic_contact,
            'created_location' => $member->createdInLocation?->name,
            'last_purchase_date' => $member->last_purchase_date ? $member->last_purchase_date->format(
                'Y-m-d H:i:s'
            ) : null,
            'registered_date' => $member->created_at ? $member->created_at->format('Y-m-d H:i:s') : null,
        ]);
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
            'pic_name',
            'pic_contact',
            'created_location',
            'last_purchase_date',
            'registered_date',
        ];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['D1', 'H1'];
    }
}
