<?php

declare(strict_types=1);

use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteStoreForWarehouseManagerAppData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Vendor;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

test('store manager cannot add grn with incomplete details for the store manager app', function (): void {
    $request = new Request([
        'uploaded_file' => [
            [
                'location_name' => null,
                'upc' => null,
                'quantity' => null,
            ],
        ],
    ]);
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);
    $warehouseManager = WarehouseManager::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);
    $request->validate(GoodsReceivedNoteStoreForWarehouseManagerAppData::rules());
})->throws(ValidationException::class);

test('validation passes when all parameters are provided is valid for store manager app', function (): void {
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $warehouseManager = WarehouseManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $vendor = Vendor::factory()->create([
        'company_id' => $companyId,
    ]);

    $request = new Request([
        'warehouse_id' => 1,
        'purchase_order_reference' => 'po_123',
        'delivery_order_reference' => 'do_123',
        'notes' => 'test_notes',
        'vendor_id' => $vendor->id,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ]);

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $request->validate(GoodsReceivedNoteStoreForWarehouseManagerAppData::rules());

    $this->assertTrue(true);
});
