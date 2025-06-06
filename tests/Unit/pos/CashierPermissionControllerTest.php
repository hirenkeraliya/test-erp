<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Pos\CashierPermissionController;

test('cashier can fetch the cashier permissions list', function (): void {
    $cashierPermissionController = new CashierPermissionController();
    $response = $cashierPermissionController->getCashierPermissionsList();

    expect($response[0])->toHaveKeys(['id', 'name', 'key']);
});
