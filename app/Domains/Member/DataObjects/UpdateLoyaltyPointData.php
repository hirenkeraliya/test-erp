<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use Spatie\LaravelData\Data;

class UpdateLoyaltyPointData extends Data
{
    public function __construct(
        public int $loyalty_points,
        public string $remarks,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'loyalty_points' => ['required', 'integer', 'min:0'],
            'remarks' => ['required', 'string', 'max:255'],
        ];
    }
}
