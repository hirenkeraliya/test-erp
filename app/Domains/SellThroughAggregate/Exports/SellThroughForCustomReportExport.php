<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SellThroughForCustomReportExport implements FromView, ShouldAutoSize, WithDrawings, WithColumnWidths, WithStyles, WithEvents
{
    protected array $drawings = [];

    protected array $imageSizes = [];

    public function __construct(
        protected Company $company,
        protected array $records,
        protected array $columns,
        protected array $mainColumns,
        protected string $date,
        protected string $locations,
    ) {
        $this->prepareDrawings();
    }

    public function view(): View
    {
        return view(
            config(
                'app.product_variant'
            ) ? 'prints.accumulated_sell_through_for_custom_report_for_variant' : 'prints.accumulated_sell_through_for_custom_report',
            [
                'company' => $this->company,
                'date' => $this->date,
                'locations' => $this->locations,
                'preparedData' => $this->records,
                'columns' => $this->columns,
                'mainColumns' => $this->mainColumns,
            ]);
    }

    public function drawings()
    {
        return $this->drawings;
    }

    protected function prepareDrawings(): void
    {
        $productVariant = config('app.product_variant');
        $indexCount = 6;
        foreach ($this->records as $record) {
            if ('' === $record['image']) {
                $indexCount += ($productVariant ? count($record['variants']) : count($record['colors']) + 3);
                continue;
            }

            $rowIndex = $indexCount;
            $path = $record['image'];
            $imageSize = getimagesize($path);

            $drawing = new Drawing();
            $drawing->setName($record['name']);
            $drawing->setDescription($record['name']);
            $drawing->setIsURL(true);
            $drawing->setPath($path);
            $drawing->setHeight(250);
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(2);
            $drawing->setCoordinates('A' . $indexCount);

            $this->imageSizes[$rowIndex] = $imageSize;

            $this->drawings[] = $drawing;
            $indexCount += ($productVariant ? count($record['variants']) : count($record['colors']) + 3);
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('B:B')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);

        $sheet->getStyle('C:C')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (array_keys($this->imageSizes) as $rowIndex) {
            $sheet->getRowDimension($rowIndex)->setRowHeight(210);
        }

        return [
            5 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        $productVariant = config('app.product_variant');

        return [
            AfterSheet::class => function (AfterSheet $event) use ($productVariant): void {
                $worksheet = $event->sheet->getDelegate();
                $startRow = 6;
                foreach ($this->records as $record) {
                    $currentRow = $startRow + ($productVariant ? count($record['variants']) : count($record['colors']));
                    $worksheet->mergeCells(sprintf('A%s:A', $startRow) . $currentRow);
                    $worksheet->mergeCells(sprintf('B%s:B', $startRow) . $currentRow);
                    $worksheet->mergeCells(sprintf('C%s:C', $startRow) . $currentRow);
                    $startRow += $productVariant ? count($record['variants']) : count($record['colors']) + 3;
                }
            },
        ];
    }
}
