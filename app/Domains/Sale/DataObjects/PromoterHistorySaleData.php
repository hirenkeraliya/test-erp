<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use Spatie\LaravelData\Data;

class PromoterHistorySaleData extends Data
{
    public function __construct(
        public string $selected_date,
        public int $per_page,
        public int $page,
        public ?int $store_id,
        public ?int $location_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'selected_date' => ['required', 'date', 'date_format:Y-m-d'],
            'per_page' => ['required', 'integer'],
            'page' => ['required', 'integer'],
        ];
    }
}
