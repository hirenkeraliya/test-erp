<?php

declare(strict_types=1);

use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForStoreManagerData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('user cannot add shipping details with incomplete details for the store manager app', function (): void {
    $request = new Request([
        'store_id' => '',
        'purchase_order_id' => '',
        'happened_at' => '',
        'transfer_items' => [],
    ]);

    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(PurchaseOrderFulfillmentStoreForStoreManagerData::rules());
})->throws(ValidationException::class);

test('validation passes when shipping details with valid details for the store manager app', function (): void {
    $request = new Request([
        'store_id' => 1,
        'purchase_order_id' => 1,
        'happened_at' => now()->format('Y-m-d h:i:s'),
        'transfer_items' => [
            [
                'purchase_order_item_id' => 1,
                'product_id' => 1,
                'transfer_quantity' => 1,
                'remarks' => 'remark',
                'package_type_id' => 1,
                'package_quantity' => 1,
                'package_total_quantity' => 1,
                'batch_details' => [
                    [
                        'batch_number' => 'wqwe2',
                        'quantity' => 20,
                    ],
                ],
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

    $request->validate(PurchaseOrderFulfillmentStoreForStoreManagerData::rules());

    $this->assertTrue(true);
});
