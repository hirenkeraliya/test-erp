<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransferTransaction\StockTransferTransactionQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->store = Location::factory([
        'type_id' => LocationTypes::STORE->value,
    ])->create();

    $this->warehouse = Location::factory([
        'type_id' => LocationTypes::WAREHOUSE->value,
    ])->create();

    $this->stockTransfer = StockTransfer::factory()->create([
        'company_id' => $this->companyId,
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
        'status' => StatusTypes::getValueByCaseName('DRAFT'),
    ]);

    $this->stockTransferTransactionQueries = new StockTransferTransactionQueries();
});

test('stock transfer transaction can be added', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $admin = Admin::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $this->stockTransferTransactionQueries->addNew(
        $this->stockTransfer->id,
        StatusTypes::DRAFT->value,
        StatusTypes::OPEN->value,
        $admin,
        'remarks'
    );

    $this->assertDatabaseHas('stock_transfer_transactions', [
        'stock_transfer_id' => $this->stockTransfer->id,
        'old_status' => StatusTypes::DRAFT->value,
        'new_status' => StatusTypes::OPEN->value,
        'user_id' => $admin->id,
        'remarks' => 'remarks',
    ]);
});
