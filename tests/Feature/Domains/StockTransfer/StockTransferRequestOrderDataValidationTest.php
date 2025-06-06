<?php

declare(strict_types=1);

use App\Domains\StockTransfer\DataObjects\StockTransferRequestOrderData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all stock transfer details are provided', function (): void {
    $request = new Request([
        'source_location_type' => 'Store',
        'source_location_id' => 11,
        'destination_location_type' => 'Store',
        'destination_location_id' => 2,
        'attention' => null,
        'reference_number' => null,
        'remarks' => null,
        'transfer_items' => [
            [
                'product_id' => 1,
                'transfer_stock' => 10,
                'remarks' => null,
                'batch_details' => [],
            ],
        ],
    ]);

    $request->validate(StockTransferRequestOrderData::rules());

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'source_location_type' => null,
        'source_location_id' => null,
        'destination_location_type' => null,
        'destination_location_id' => null,
        'attention' => null,
        'reference_number' => null,
        'remarks' => null,
        'transfer_items' => [
            [
                'product_id' => 1,
                'transfer_stock' => 10,
                'remarks' => null,
                'batch_details' => [],
            ],
        ],
    ]);

    $request->validate(StockTransferRequestOrderData::rules());
})->throws(ValidationException::class);
