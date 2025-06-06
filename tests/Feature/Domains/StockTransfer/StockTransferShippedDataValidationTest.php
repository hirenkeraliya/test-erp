<?php

declare(strict_types=1);

use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all stock transfer details are provided', function (): void {
    $request = new Request([
        'shipped_type' => ShippedTypes::TRANSIT->value,
        'location_id' => '1',
    ]);

    $request->validate(StockTransferShippedData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'shipped_type' => ShippedTypes::TRANSIT->value,
        'location_id' => null,
    ]);
    $request->validate(StockTransferShippedData::rules());
})->throws(ValidationException::class);
