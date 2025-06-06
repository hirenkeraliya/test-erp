<?php

declare(strict_types=1);

namespace App\Domains\Common\Interfaces;

interface SaleUsersInterface
{
    public function getFullName(): string;
}
