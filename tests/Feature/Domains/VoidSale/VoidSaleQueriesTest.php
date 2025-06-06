<?php

declare(strict_types=1);

use App\Domains\VoidSale\DataObjects\PosVoidSaleData;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Sale;
use App\Models\StoreManager;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->voidSaleQueries = new VoidSaleQueries();
});

test('it can return last void sale number', function (): void {
    $company = Company::factory()->create([
        'name' => 'Company A',
        'email' => 'company@test.com',
    ]);

    $location = Location::factory()->create([
        'email' => 'store@test.com',
        'company_id' => $company->id,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
        'counter_update_id' => null,
        'is_locked' => 0,
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'email' => 'store@test.com',
    ]);

    $cashierGroup = CashierGroup::factory()->create([
        'company_id' => $company->id,
        'name' => 'Cashier Group',
    ]);

    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
        'cashier_group_id' => $cashierGroup->id,
        'username' => 'cashier',
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $voidSaleReason = VoidSaleReason::factory()->create([
        'company_id' => $company->id,
    ]);

    $storeManager = StoreManager::factory()->create([
        'username' => 'Store Manager',
        'employee_id' => $employee->id,
    ]);

    $currentTime = Carbon::now();

    VoidSale::factory()->create([
        'void_sale_number' => 1,
        'sale_id' => $sale->id,
        'voided_by_store_manager_id' => $storeManager->id,
        'void_sale_reason_id' => $voidSaleReason->id,
        'created_at' => $currentTime,
        'updated_at' => $currentTime,
    ]);

    $voidSale = VoidSale::factory()->create([
        'void_sale_number' => 2,
        'sale_id' => $sale->id,
        'voided_by_store_manager_id' => $storeManager->id,
        'void_sale_reason_id' => $voidSaleReason->id,
        'created_at' => $currentTime->addMinutes(10),
        'updated_at' => $currentTime->addMinutes(10),
    ]);

    $response = $this->voidSaleQueries->getLastVoidSaleNumber($company->id);
    $this->assertEquals($voidSale->void_sale_number, $response);
});

test('A sale can be voided', function (): void {
    $voidSale = VoidSale::factory()->make()->toArray();

    $posVoidSaleData = new PosVoidSaleData(
        void_sale_reason_id: $voidSale['void_sale_reason_id'],
        voided_by_store_manager_id: $voidSale['voided_by_store_manager_id'],
        passcode: '123456',
    );

    $mock = $this->createPartialMock(VoidSaleQueries::class, ['getLastVoidSaleNumber']);

    $mock->expects($this->once())
        ->method('getLastVoidSaleNumber')
        ->will($this->returnValue(1));

    $response = $mock->addNew($posVoidSaleData, $voidSale['sale_id'], 1);
    expect($response)->toBeObject();

    $this->assertDatabaseHas('void_sales', [
        'sale_id' => $voidSale['sale_id'],
        'void_sale_number' => 2,
        'voided_by_store_manager_id' => $voidSale['voided_by_store_manager_id'],
        'void_sale_reason_id' => $voidSale['void_sale_reason_id'],
    ]);
});
