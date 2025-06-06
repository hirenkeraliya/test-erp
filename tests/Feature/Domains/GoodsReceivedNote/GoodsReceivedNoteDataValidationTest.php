<?php

declare(strict_types=1);

use App\Domains\GoodsReceivedNote\DataObjects\GoodsReceivedNoteData;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\Vendor;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

test('user cannot add grn with incomplete details for the admin panel', function (): void {
    $request = new Request([
        'uploaded_file' => [
            [
                'upc' => null,
                'quantity' => null,
            ],
        ],
    ]);
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);
    $admin = Admin::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $request->setUserResolver(fn (): Admin => $admin);
    $request->validate(GoodsReceivedNoteData::rules($request));
})->throws(ValidationException::class);

test('user cannot add grn with incomplete details for the store manager panel', function (): void {
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
    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $request->setUserResolver(fn (): StoreManager => $storeManager);
    $request->validate(GoodsReceivedNoteData::rules($request));
})->throws(ValidationException::class);

test('user cannot add grn with incomplete details for the warehouse manager panel', function (): void {
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
    $request->validate(GoodsReceivedNoteData::rules($request));
})->throws(ValidationException::class);

test('validation passes when all grn details are provided for admin panel', function (): void {
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $admin = Admin::factory()->create([
        'employee_id' => $employee->id,
    ]);

    setCompanyIdInSession($companyId);

    $vendor = Vendor::factory()->create([
        'company_id' => $companyId,
    ]);

    $request = new Request([
        'purchase_order_reference' => 'po_123',
        'delivery_order_reference' => 'do_123',
        'notes' => 'test_notes',
        'vendor_id' => $vendor->id,
        'location_id' => 1,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $request->validate(GoodsReceivedNoteData::rules($request));

    $this->assertTrue(true);
});

test('validation passes when all grn details are provided for store manager panel', function (): void {
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    setStoreManagerStoreCompanyIdInSession($companyId);

    $vendor = Vendor::factory()->create([
        'company_id' => $companyId,
    ]);

    $request = new Request([
        'purchase_order_reference' => 'po_123',
        'delivery_order_reference' => 'do_123',
        'notes' => 'test_notes',
        'vendor_id' => $vendor->id,
        'location_id' => 1,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(GoodsReceivedNoteData::rules($request));

    $this->assertTrue(true);
});

test('validation passes when all grn details are provided for warehouse manager', function (): void {
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $warehouseManager = WarehouseManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    setWarehouseManagerWarehouseCompanyIdInSession($companyId);

    $vendor = Vendor::factory()->create([
        'company_id' => $companyId,
    ]);

    $request = new Request([
        'purchase_order_reference' => 'po_123',
        'delivery_order_reference' => 'do_123',
        'notes' => 'test_notes',
        'vendor_id' => $vendor->id,
        'location_id' => 1,
        'uploaded_file' => new UploadedFile(
            public_path('files/goods-received-note-products-sample-file.xlsx'),
            'example.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ]);

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $request->validate(GoodsReceivedNoteData::rules($request));

    $this->assertTrue(true);
});
