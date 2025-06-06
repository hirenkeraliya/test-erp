<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Exports;

use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GoodsReceivedNoteByArticleNumberExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $goodsReceivedNoteProducts,
        protected array $columns,
        protected Location $location,
        protected Company $company,
        protected array $dateRange
    ) {
    }

    public function view(): View
    {
        return view('prints.goods_received_note_by_article_number', [
            'goodsReceivedNoteProducts' => $this->goodsReceivedNoteProducts,
            'location' => $this->location,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
        ]);
    }
}
