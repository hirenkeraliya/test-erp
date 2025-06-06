<?php

declare(strict_types=1);

namespace App\Domains\Common\Interfaces;

interface InventoryLocationsInterface
{
    public function getName(): string;

    public function getCode(): ?string;
}
