<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Product;
use App\Models\StoreManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->checkOrderDetailsService = new CheckOrderDetailsService();
    $this->companyId = 1;
    $this->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $this->orderDetails = [
        'order_type' => OrderTypes::PENDING_LAYAWAY_ORDER->value,
        'channel_type' => OrderChannels::B2B_ORDERS->value,
        'member_id' => null,
        'notes' => 'Notes goes here',
        'bill_reference_number' => null,
        'return_items' => null,
        'order_items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'order_round_off_amount' => 0.0,
        'order_return_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'cart_discount_amount' => 0.0,
        'member_details' => [],
        'location_id' => 1,
        'cart_price_override_amount' => 0.01,
        'cart_price_override_percentage' => 0.01,
        'is_layaway' => true,
        'layaway_pending_amount' => 2,
    ];

    $this->orderData = new OrderData(...$this->orderDetails);
    $this->checkOrderDetailsService->orderData = $this->orderData;

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'retail_price' => 10.00,
        'has_batch' => false,
        'status' => false,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->inventory = Inventory::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'stock' => 40.00,
    ]);

    $this->inventoryUnitA = InventoryUnit::factory()->make([
        'id' => 1,
        'inventory_id' => $this->inventory->id,
        'purchase_amount_id' => 1,
        'batch_id' => null,
        'quantity' => 30.00,
    ]);

    $this->inventoryUnitB = InventoryUnit::factory()->make([
        'id' => 2,
        'inventory_id' => $this->inventory->id,
        'purchase_amount_id' => 2,
        'batch_id' => null,
        'quantity' => 10.00,
    ]);

    $this->inventory->inventoryUnits = collect([$this->inventoryUnitA]);

    $this->cartItems = collect($this->orderData->order_items);
});

test(
    'checkLayawayAmounts method return null when is_layaway not set',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $storeManager->employee = $employee;

        $this->orderData->is_layaway = false;
        $this->checkOrderDetailsService->orderData = $this->orderData;
        $this->checkOrderDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderDetailsService->checkLayawayAmounts(100);
        $this->assertNull($response);
    }
);

test(
    'checkLayawayAmounts method throws exception when layaway_pending_amount is null',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $storeManager->employee = $employee;

        $this->orderData->is_layaway = true;
        $this->orderData->layaway_pending_amount = null;
        $this->checkOrderDetailsService->orderData = $this->orderData;
        $this->checkOrderDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderDetailsService->checkLayawayAmounts(100);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Layaway pending amount is not specified.');

test(
    'checkLayawayAmounts method throws exception when layaway_pending_amount is not match',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $storeManager->employee = $employee;

        $this->orderData->is_layaway = true;
        $this->orderData->layaway_pending_amount = 100;
        $this->checkOrderDetailsService->orderData = $this->orderData;
        $this->checkOrderDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderDetailsService->checkLayawayAmounts(100);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Specified layaway pending amount does not match with calculated layaway pending amount.\nExpected: 0\nSpecified: 100'
);

test(
    'checkCreditAmounts method return null when is_credit not set',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $storeManager->employee = $employee;

        $this->orderData->is_credit = false;
        $this->checkOrderDetailsService->orderData = $this->orderData;
        $this->checkOrderDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderDetailsService->checkCreditAmounts(100);
        $this->assertNull($response);
    }
);

test(
    'checkCreditAmounts method throws exception when credit_pending_amount is null',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $storeManager->employee = $employee;

        $this->orderData->is_credit = true;
        $this->orderData->credit_pending_amount = null;
        $this->checkOrderDetailsService->orderData = $this->orderData;
        $this->checkOrderDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderDetailsService->checkCreditAmounts(100);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Credit pending amount is not specified.');

test(
    'checkCreditAmounts method throws exception when credit_pending_amount is not match',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '12345',
        ]);

        $storeManager->employee = $employee;

        $this->orderData->is_credit = true;
        $this->orderData->credit_pending_amount = 100;
        $this->checkOrderDetailsService->orderData = $this->orderData;
        $this->checkOrderDetailsService->companyId = $this->companyId;

        $response = $this->checkOrderDetailsService->checkCreditAmounts(100);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Specified credit pending amount does not match with calculated credit pending amount.\nExpected: 0\nSpecified: 100'
);
