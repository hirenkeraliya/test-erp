<?php

declare(strict_types=1);

namespace App\Domains\Voucher\DataObjects;

use Spatie\LaravelData\Data;

class PaginatedVoucherListDataForPos extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?int $page,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
