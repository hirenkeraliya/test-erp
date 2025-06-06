<?php

declare(strict_types=1);

use App\Domains\Vendor\DataObjects\VendorListForWarehouseManagerAppData;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'warehouse manager cannot get vendors list with incomplete details for the warehouse manager app',
    function (): void {
        $request = new Request([
            'warehouse_id' => '',
        ]);

        $companyId = Company::factory()->create()->id;
        $employee = Employee::factory()->create([
            'company_id' => $companyId,
        ]);

        $warehouseManager = WarehouseManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

        $request->validate(VendorListForWarehouseManagerAppData::rules());
    }
)->throws(ValidationException::class);

test('validation passes when all parameters are provided is valid for warehouse manager app', function (): void {
    $companyId = Company::factory()->create()->id;
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
    ]);

    $warehouseManager = WarehouseManager::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $request = new Request([
        'warehouse_id' => 1,
    ]);

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $request->validate(VendorListForWarehouseManagerAppData::rules());

    $this->assertTrue(true);
});
