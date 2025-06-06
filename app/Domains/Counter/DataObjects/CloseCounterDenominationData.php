<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use Spatie\LaravelData\Data;

class CloseCounterDenominationData extends Data
{
    public function __construct(
        public ?float $denomination = null,
        public ?int $quantity = null,
    ) {
    }
}
