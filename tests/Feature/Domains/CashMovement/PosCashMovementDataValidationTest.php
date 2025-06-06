<?php

declare(strict_types=1);

use App\Domains\CashMovement\DataObjects\PosCashMovementData;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\Common\Enums\AuthorizerTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'validation fails while adding cash movement',
    function (): void {
        $cashMovementData = [
            'offline_id' => null,
            'happened_at' => null,
            'cash_movement_reason_id' => null,
            'authorizer_id' => null,
            'authorizer_type' => null,
            'other_reason' => null,
            'type_id' => null,
            'amount' => null,
        ];

        $request = new Request($cashMovementData);

        $request->validate(PosCashMovementData::rules());
    }
)->throws(ValidationException::class);

test(
    'validation works while adding cash movement.',
    function (): void {
        $cashMovementData = [
            'offline_id' => 'a',
            'happened_at' => '2022-01-04 04:20:50',
            'cash_movement_reason_id' => 1,
            'authorizer_id' => 1,
            'authorizer_type' => AuthorizerTypes::STORE_MANAGER->value,
            'other_reason' => '',
            'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
            'amount' => 10.10,
        ];

        $request = new Request($cashMovementData);

        $request->validate(PosCashMovementData::rules());
        $this->assertTrue(true);
    }
);
