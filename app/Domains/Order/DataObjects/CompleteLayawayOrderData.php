<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class CompleteLayawayOrderData extends Data
{
    public function __construct(
        public array $paymentTypes,
        public int $orderId,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'paymentTypes' => ['required', 'array'],
            'paymentTypes.*.type_id' => ['required', 'integer'],
            'paymentTypes.*.amount' => ['required', 'numeric'],
            'orderId' => ['required', 'exists:orders,id'],
        ];
    }
}
