<?php

declare(strict_types=1);

namespace App\Domains\Voucher\DataObjects;

use Spatie\LaravelData\Data;

class GenerateVoucherData extends Data
{
    public function __construct(
        public int $voucher_configuration_id,
        public int $discount_type,
        public string $number,
        public float $minimum_spend_amount,
        public ?string $expired_at = null,
        public ?float $percentage = null,
        public ?float $flat_amount = null,
    ) {
    }
}
