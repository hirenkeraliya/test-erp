<?php

declare(strict_types=1);

namespace App\Domains\StockTake\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class StockTakesBulkData extends Data
{
    public function __construct(
        public UploadedFile $stock_take_bulk_submitted_stocks,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'stock_take_bulk_submitted_stocks' => ['required'],
        ];
    }
}
