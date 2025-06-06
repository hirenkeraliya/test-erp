<?php

declare(strict_types=1);

namespace App\Domains\OrderReturn\DataObjects;

use Spatie\LaravelData\Data;

class OrderReturnData extends Data
{
    public function __construct(
        public array $order_return_items,
        public ?int $member_id = null,
        public ?float $order_return_round_off_amount = 0,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'order_return_items' => ['required', 'array'],
            'order_return_items.*.id' => ['required', 'integer'],
            'order_return_items.*.order_item_id' => ['required', 'integer'],
            'order_return_items.*.product_id' => ['required', 'integer'],
            'order_return_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'order_return_items.*.total_price_paid' => ['required', 'numeric'],
            'order_return_items.*.return_quantity' => ['required', 'numeric', 'min:0.01'],
            'order_return_items.*.order_return_reason_id' => ['required', 'integer'],
            'order_return_round_off_amount' => ['sometimes', 'numeric'],
            'member_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
