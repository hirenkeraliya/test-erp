<?php

declare(strict_types=1);

use App\Domains\Vendor\DataObjects\VendorListForStoreManagerAppData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('store manager cannot get vendors list with incomplete details for the store manager app', function (): void {
    $request = new Request([
        'store_id' => '',
    ]);

    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(VendorListForStoreManagerAppData::rules());
})->throws(ValidationException::class);

test('validation passes when all parameters are provided is valid for store manager app', function (): void {
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $request = new Request([
        'store_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(VendorListForStoreManagerAppData::rules());

    $this->assertTrue(true);
});
