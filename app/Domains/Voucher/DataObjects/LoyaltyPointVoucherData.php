<?php

declare(strict_types=1);

namespace App\Domains\Voucher\DataObjects;

use Spatie\LaravelData\Data;

class LoyaltyPointVoucherData extends Data
{
    public function __construct(
        public int $voucher_configuration_id,
        public int $member_id,
        public int $loyalty_points,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'voucher_configuration_id' => ['required', 'integer'],
            'member_id' => ['required', 'integer'],
            'loyalty_points' => ['required', 'integer', 'min:1'],
        ];
    }
}
