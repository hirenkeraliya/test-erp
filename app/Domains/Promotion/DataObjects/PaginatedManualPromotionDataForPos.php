<?php

declare(strict_types=1);

namespace App\Domains\Promotion\DataObjects;

use Spatie\LaravelData\Data;

class PaginatedManualPromotionDataForPos extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?string $search_text,
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
            'search_text' => ['sometimes', 'string'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
