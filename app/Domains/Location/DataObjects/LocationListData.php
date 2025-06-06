<?php

declare(strict_types=1);

namespace App\Domains\Location\DataObjects;

use App\Domains\Location\Enums\LocationTypes;
use Spatie\LaravelData\Data;

class LocationListData extends Data
{
    public function __construct(
        public ?string $search_text = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?int $per_page = null,
        public ?int $page = null,
        public ?int $type_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'search_text' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
            'sort_by' => ['nullable', 'string'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'type_id' => ['nullable', 'integer', 'in:' . LocationTypes::getValues()],
        ];
    }
}
