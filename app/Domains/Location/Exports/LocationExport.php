<?php

declare(strict_types=1);

namespace App\Domains\Location\Exports;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\City;
use App\Models\Location;
use App\Models\State;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Nnjeim\World\Models\Country;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LocationExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $locations
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
        return $this->locations->map(function (Location $location): array {
            /** @var Collection $brands */
            $brands = $location->brands;
            $names = $brands->pluck('name')->toArray();
            /** @var ?Country $country */
            $country = $location->country;
            /** @var ?State $state */
            $state = $location->state;
            /** @var ?City $city */
            $city = $location->getRelation('city');
            $countryName = $country instanceof Country ? $country->name : '';
            $stateName = $state instanceof State ? $state->name : '';
            $cityName = $city?->name;

            return [
                'type' => LocationTypes::getFormattedCaseName($location->type_id),
                'name' => $location->name,
                'code' => $location->code,
                'brands' => implode(', ', $names),
                'registration_number' => $location->registration_number,
                'sst_number' => $location->sst_number,
                'email' => $location->email,
                'phone' => $location->phone,
                'mobile' => $location->mobile,
                'fax' => $location->fax,
                'address_line_1' => $location->address_line_1,
                'address_line_2' => $location->address_line_2,
                'city' => $cityName,
                'area_code' => $location->area_code,
                'web_site' => $location->web_site,
                'sales_tax_percentage' => $location->sales_tax_percentage,
                'sales_return_days_limit' => $location->sales_return_days_limit,
                'credit_note_expiration_days' => $location->credit_note_expiration_days,
                'loyalty_point_expiration_days' => $location->loyalty_point_expiration_days,
                'receipt_footer' => $location->receipt_footer,
                'disclaimer' => $location->disclaimer,
                'cash_out_limit_info' => $location->cash_out_limit_info,
                'cash_out_limit_warning' => $location->cash_out_limit_warning,
                'cash_out_limit_restrict' => $location->cash_out_limit_restrict,
                'price_fall_down_percentage' => $location->price_fall_down_percentage,
                'open_time' => $location->open_time,
                'close_time' => $location->close_time,
                'country' => $countryName,
                'state' => $stateName,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'type',
            'name',
            'code',
            'brands',
            'registration_number',
            'sst_number',
            'email',
            'phone',
            'mobile',
            'fax',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'website',
            'sales_tax_percentage',
            'sales_return_days_limit',
            'credit_note_expiration_days',
            'receipt_footer',
            'disclaimer',
            'cash_out_limit_info',
            'cash_out_limit_warning',
            'cash_out_limit_restrict',
            'price_fall_down_percentage',
            'open_time',
            'close_time',
            'country',
            'state',
        ];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return [
            'A1',
            'B1',
            'C1',
            'D1',
            'E1',
            'F1',
            'G1',
            'H1',
            'K1',
            'M1',
            'N1',
            'P1',
            'Q1',
            'T1',
            'U1',
            'AB1',
            'AC1',
        ];
    }
}
