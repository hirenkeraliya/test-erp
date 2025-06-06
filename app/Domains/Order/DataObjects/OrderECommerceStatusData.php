<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use App\Domains\Order\Enums\OrderStatus;
use Spatie\LaravelData\Data;

class OrderECommerceStatusData extends Data
{
    public function __construct(
        public ?int $order_id,
        public ?int $external_order_id,
        public ?string $tracking_number,
        public ?string $shipment_order_number,
        public string $status,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'order_id' => [
                'required_without_all:tracking_number,shipment_order_number,external_order_id',
                'nullable',
                'integer',
            ],
            'external_order_id' => [
                'required_without_all:order_id,tracking_number,shipment_order_number',
                'nullable',
                'integer',
            ],
            'tracking_number' => [
                'required_without_all:order_id,shipment_order_number,external_order_id',
                'nullable',
                'string',
            ],
            'shipment_order_number' => [
                'required_without_all:order_id,tracking_number,external_order_id',
                'nullable',
                'string',
            ],
            'status' => ['required', 'string', 'in:' . OrderStatus::getOriginalNames()],
        ];
    }
}
