<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class OrderECommercePickupStoreData extends Data
{
    public function __construct(
        public int $order_id,
        public int $location_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
        ];
    }
}
