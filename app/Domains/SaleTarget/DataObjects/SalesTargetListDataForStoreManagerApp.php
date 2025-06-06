<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\DataObjects;

use App\Domains\SaleTarget\Enums\TimeIntervalType;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class SalesTargetListDataForStoreManagerApp extends Data
{
    public function __construct(
        public ?int $time_interval_type_id,
        public ?int $store_id,
        public ?int $location_id,
        public ?int $page,
        public ?int $per_page,
        public ?string $sort_by,
        public ?string $search_text,
        public ?string $sort_direction,
    ) {
    }

    public static function rules(): array
    {
        return [
            'time_interval_type_id' => ['sometimes', 'integer', new Enum(TimeIntervalType::class)],
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'page' => ['required', 'integer'],
            'per_page' => ['required', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,name'],
            'search_text' => ['sometimes', 'string'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ];
    }
}
