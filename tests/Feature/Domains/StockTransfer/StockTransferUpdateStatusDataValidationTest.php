<?php

declare(strict_types=1);

use App\Domains\StockTransfer\DataObjects\StockTransferUpdateStatusData;
use App\Domains\StockTransfer\Enums\StatusTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all stock transfer details are provided', function (): void {
    $request = new Request([
        'status_id' => StatusTypes::OPEN->value,
        'remarks' => null,
    ]);

    $request->validate(StockTransferUpdateStatusData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'status_id' => StatusTypes::CANCELLED->value,
        'remarks' => null,
    ]);

    $request->validate(StockTransferUpdateStatusData::rules());
})->throws(ValidationException::class);
