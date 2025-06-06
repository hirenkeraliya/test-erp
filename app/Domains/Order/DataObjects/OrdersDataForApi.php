<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use Spatie\LaravelData\Data;

class OrdersDataForApi extends Data
{
    public function __construct(
        public ?int $page,
        public ?int $per_page,
        public ?string $sort_by,
        public ?string $sort_direction,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,receipt_number'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ];
    }
}
