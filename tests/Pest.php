<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Permission\Services\PermissionModuleService;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\SaleChannel;
use App\Models\SaleChannelInventoryRollbackOrderStatus;
use App\Models\StockTransfer;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Tests\FeatureTestCase;
use Tests\UnitTestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(UnitTestCase::class)->beforeEach(fn () => $this->withoutVite())->in('Unit');
uses(FeatureTestCase::class)->beforeEach(fn () => $this->withoutVite())->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function loginSuperAdmin(?SuperAdmin $superAdmin = null)
{
    return test()->actingAs($superAdmin ?? SuperAdmin::factory()->create(), 'super_admin');
}

function loginAdmin(?Admin $admin = null)
{
    $adminUser = $admin ?? Admin::factory()->create();
    session()->put('admin_company_id', $adminUser->employee->company_id);

    return test()->actingAs($adminUser, 'admin');
}

function loginStoreManager(?StoreManager $storeManager = null, int $locationId = 1)
{
    $storeManagerUser = $storeManager ?? StoreManager::factory()->create();
    session()->put('store_manager_selected_location_id', $locationId);

    return test()->actingAs($storeManagerUser, 'store_manager');
}

function setCompanyIdInSession(int $companyId = 1): void
{
    session([
        'admin_company_id' => $companyId,
    ]);
}

function setStoreManagerStoreIdInSession(int $locationId = 1): void
{
    session([
        'store_manager_selected_location_id' => $locationId,
    ]);
}

function setWarehouseManagerWarehouseIdInSession(int $locationId = 1): void
{
    session([
        'warehouse_manager_selected_location_id' => $locationId,
    ]);
}

function setStoreManagerStoreCompanyIdInSession(int $companyId = 1): void
{
    session([
        'store_manager_selected_location_company_id' => $companyId,
    ]);
}

function setWarehouseManagerWarehouseCompanyIdInSession(int $companyId = 1): void
{
    session([
        'warehouse_manager_selected_location_company_id' => $companyId,
    ]);
}

function setStoreIdInSession(int $locationId = 1): void
{
    session([
        'store_manager_selected_location_id' => $locationId,
    ]);
}

function commonGetProductDetails(bool $hasBatch = true): Product
{
    return Product::factory()->make([
        'id' => 1,
        'name' => 'Product 1',
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'has_batch' => $hasBatch,
        'type_id' => 1,
        'vendor_id' => null,
    ]);
}

function makeCashierAndEmployeeForPosWithCounterUpdateId(int $counterUpdateId = 1): array
{
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
        'email' => 'employee@test.com',
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => $counterUpdateId,
        'username' => 'Cashier',
    ]);

    return [
        'cashier' => $cashier,
        'employee' => $employee,
    ];
}

function makeCashierAndEmployeeForPosWithoutCounterUpdateId(bool $employeeStatus = true): array
{
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
        'email' => 'employee@test.com',
        'status' => $employeeStatus,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => null,
        'username' => 'Cashier',
    ]);

    return [
        'cashier' => $cashier,
        'employee' => $employee,
    ];
}

function makeCashierForPosWithoutCounterUpdateId(): Cashier
{
    return Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);
}

function makeCashierForPosWithCounterUpdateId(int $counterUpdateId = 1): Cashier
{
    return Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => $counterUpdateId,
    ]);
}

function setRequestUserForAdmin($requestData = []): array
{
    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request($requestData);
    $request->setUserResolver(fn (): Admin => $admin);

    return [$admin, $request];
}

function setRequestUserForStoreManager($requestData = []): array
{
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request($requestData);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    return [$storeManager, $request];
}

function setRequestUserForWarehouseManager($requestData = []): array
{
    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request($requestData);
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    return [$warehouseManager, $request];
}

function setRequestUserForSaleChannel(
    $requestData = [],
    $saleChannelData = [
        'id' => 1,
        'company_id' => 1,
        'default_location_id' => 1,
        'inventory_deduct_order_status' => OrderStatus::PLACED,
    ],
): array {
    $saleChannel = SaleChannel::factory()->make($saleChannelData);
    $saleChannelInventoryRollbackOrderStatus = SaleChannelInventoryRollbackOrderStatus::factory()->make([
        'sale_channel_id' => $saleChannel->id,
        'order_status' => OrderStatus::PLACED->value,
    ]);

    $saleChannel->saleChannelInventoryRollbackOrderStatus = collect([$saleChannelInventoryRollbackOrderStatus]);

    $request = new Request($requestData);
    $request->setUserResolver(fn (): SaleChannel => $saleChannel);

    return [$saleChannel, $request];
}

function allProductPermission(): array
{
    return array_map(
        fn ($value): string => 'product_' . $value,
        PermissionModuleService::getModuleSubLists()['Product']
    );
}

function loadStockTransferLocationRelation(int $companyId, int $status, int $transferType): array
{
    [$storeOne, $storeTwo, $storeManagerOne] = seedStoreAndStoreManagers($companyId);

    $stockTransfer = StockTransfer::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'source_location_id' => $storeOne->id,
        'stock_transfer_reason_id' => null,
        'destination_location_id' => $storeTwo->id,
        'requested_by_id' => 1,
        'status' => $status,
        'transfer_type' => $transferType,
    ]);

    $stockTransfer->sourceLocation = $storeOne;
    $stockTransfer->destinationLocation = $storeTwo;

    return [$stockTransfer, $storeOne, $storeManagerOne];
}

function seedStoreAndStoreManagers(int $companyId): array
{
    $storeOne = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $storeManagerOne = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 2,
    ]);

    $storeManagerTwo = StoreManager::factory()->make([
        'id' => 2,
        'employee_id' => $companyId,
    ]);

    $storeTwo = Location::factory()->make([
        'id' => 2,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $storeOne->storeManagers = collect([$storeManagerOne]);
    $storeTwo->storeManagers = collect([$storeManagerTwo]);

    return [$storeOne, $storeTwo, $storeManagerOne, $storeManagerTwo];
}
