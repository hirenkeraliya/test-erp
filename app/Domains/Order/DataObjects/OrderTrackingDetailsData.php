<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use Spatie\LaravelData\Data;

class OrderTrackingDetailsData extends Data
{
    public function __construct(
        public string $tracking_number,
        public string $courier_name,
        public string $tracking_url,
        public string $shipment_order_number,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string'],
            'courier_name' => ['required', 'string'],
            'tracking_url' => ['required', 'string', 'url'],
            'shipment_order_number' => ['required', 'string', 'max:255'],
        ];
    }
}
