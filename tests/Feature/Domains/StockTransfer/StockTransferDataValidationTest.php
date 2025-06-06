<?php

declare(strict_types=1);

use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation passes when all stock transfer details are provided', function (): void {
    $request = new Request([
        'source_location_type' => 'Store',
        'source_location_id' => 11,
        'destination_location_type' => 'Store',
        'destination_location_id' => 2,
        'transfer_date' => null,
        'attention' => null,
        'reference_number' => null,
        'transfer_type' => 'transfer_order',
        'stock_transfer_reason_id' => null,
        'transfer_items' => [
            [
                'product_id' => 1,
                'transfer_stock' => 10,
                'batch_details' => [],
            ],
        ],
    ]);

    setCompanyIdInSession();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $request->validate(StockTransferData::rules($request));

    $this->assertTrue(true);
});

test('validation exception throw if require field missing', function (): void {
    $request = new Request([
        'source_location_type' => null,
        'source_location_id' => null,
        'destination_location_type' => null,
        'destination_location_id' => null,
        'transfer_date' => null,
        'attention' => null,
        'transfer_items' => null,
        'stock_transfer_reason_id' => null,
    ]);

    setCompanyIdInSession();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $request->validate(StockTransferData::rules($request));
})->throws(ValidationException::class);
