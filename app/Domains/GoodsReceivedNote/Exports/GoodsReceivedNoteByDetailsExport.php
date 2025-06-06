<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GoodsReceivedNoteByDetailsExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $goodsReceivedNotes,
        protected array $columns,
        protected Company $company,
        protected array $dateRange,
        protected string $filterBy
    ) {
    }

    public function view(): View
    {
        return view('prints.goods_received_note_by_details', [
            'goodsReceivedNotes' => $this->goodsReceivedNotes,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'filterBy' => $this->filterBy,
        ]);
    }

    /**
     * @return mixed[]
     */
    public function headings(): array
    {
        return [
            ...$this->columns,
            '',
            'Name',
            'Article Number',
            'Product Quantity',
            'Location',
            'Upc',
            'Color',
            'Size',
            'Quantity',
        ];
    }
}
