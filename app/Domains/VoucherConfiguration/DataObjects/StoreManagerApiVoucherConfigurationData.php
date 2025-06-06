<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiVoucherConfigurationData extends Data
{
    public function __construct(
        public int $page,
        public string $selected_date,
        public ?int $per_page = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'page' => ['required', 'integer'],
            'selected_date' => ['required', 'date', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => [
                'sometimes',
                'nullable',
                'string',
                'in:id,restricted_by_type,voucher_type,discount_type,get_value,start_date,end_date,status',
            ],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
