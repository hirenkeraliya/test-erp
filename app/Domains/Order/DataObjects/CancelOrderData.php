<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class CancelOrderData extends Data
{
    public function __construct(
        public int $cancelOrderReasonId,
        public string $orderId,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'cancelOrderReasonId' => ['required', 'exists:void_sale_reasons,id'],
            'orderId' => ['required', 'exists:orders,id'],
        ];
    }
}
