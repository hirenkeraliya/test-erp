<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\DataObjects;

use Spatie\LaravelData\Data;

class GoodsReceivedNoteUpdateStatusData extends Data
{
    public function __construct(
        public string $remarks,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'remarks' => ['required', 'string'],
        ];
    }
}
