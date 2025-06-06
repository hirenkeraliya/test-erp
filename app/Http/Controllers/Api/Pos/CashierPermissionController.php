<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Http\Controllers\Controller;

class CashierPermissionController extends Controller
{
    /**
     * @return mixed[]
     */
    public function getCashierPermissionsList(): array
    {
        return PermissionTypes::getList();
    }
}
