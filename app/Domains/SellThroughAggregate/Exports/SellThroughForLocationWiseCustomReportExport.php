<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SellThroughForLocationWiseCustomReportExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Company $company,
        protected array $records,
        protected array $columns,
        protected array $groupColumns,
        protected ?array $subColumns,
        protected array $locationColumns,
        protected string $date,
        protected string $locations,
    ) {
    }

    public function view(): View
    {
        $viewData = [
            'company' => $this->company,
            'date' => $this->date,
            'locations' => $this->locations,
            'preparedRecords' => $this->records,
            'locationColumns' => $this->locationColumns,
            'columns' => $this->columns,
        ];

        if (config('app.product_variant')) {
            $viewData['variantColumns'] = $this->groupColumns;

            return view('prints.accumulated_sell_through_for_custom_report_for_location_wise_variant', $viewData);
        }

        $viewData['colorColumns'] = $this->groupColumns;
        $viewData['sizeColumns'] = $this->subColumns;

        return view('prints.accumulated_sell_through_for_custom_report_for_location_wise', $viewData);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('C5:XFD5')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [
            5 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
