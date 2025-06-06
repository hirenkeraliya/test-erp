<?php

declare(strict_types=1);

use App\Domains\City\CityQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\OrderInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Order\Services\GenerateEcommerceLoyaltyPointsService;
use App\Domains\Order\Services\OrderEcommerceDiscountService;
use App\Domains\Order\Services\SaveOrderEcommerceDetailsService;
use App\Domains\Order\Services\UseEcommerceLoyaltyPointsService;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\State\StateQueries;
use App\Domains\Voucher\Services\GenerateVoucherECommerceService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SaleChannel;
use App\Models\Sequence;
use App\Models\StoreManager;
use App\Models\Voucher;

beforeEach(function (): void {
    $this->checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();
    $this->saveOrderEcommerceDetailsService = new SaveOrderEcommerceDetailsService();
    $this->companyId = 1;
    $this->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $this->orderDetails = [
        'member_id' => null,
        'notes' => 'Notes goes here',
        'order_items' => [
            [
                'id' => 1,
                'upc' => 'abd123',
                'price' => '10.00',
                'total_amount' => '10.00',
                'quantity' => '1',
                'promoter_ids' => [1],
            ],
        ],
        'payment_type_id' => 1,
        'payment_amount' => 100,
        'shipping_address' => [
            'first_name' => 'test',
            'last_name' => 'test',
            'phone' => 'test',
            'address_line_1' => 'test',
            'address_line_2' => 'test',
            'country_code' => 'test',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_code' => 'test',
        ],
        'billing_address' => [
            'first_name' => 'test',
            'last_name' => 'test',
            'phone' => 'test',
            'address_line_1' => 'test',
            'address_line_2' => 'test',
            'country_code' => 'test',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_code' => 'test',
        ],
        'order_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'member_details' => [],
    ];

    $this->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'open_price' => 10.00,
        'has_batch' => false,
        'is_non_inventory' => false,
        'status' => 1,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->orderItems = collect($this->orderECommerceData->order_items);
    $this->checkOrderEcommerceDetailsService->orderItems = $this->orderItems;

    $this->checkOrderEcommerceDetailsService->products = collect([$this->product]);
    $this->checkOrderEcommerceDetailsService->location = $this->location;
    $this->checkOrderEcommerceDetailsService->saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'default_location_id' => 1,
        'inventory_deduct_order_status' => OrderStatus::PLACED,
    ]);
});

test(
    'checkRequestDetails method will the request details',
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

        $order = Order::factory()->make([
            'store_manager_id' => null,
            'location_id' => 1,
            'member_id' => null,
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'id' => 1,
        ]);

        $orderItem = OrderItem::factory()->make([
            'store_manager_id' => null,
            'order_id' => 1,
            'product_id' => 1,
            'exchange_item_id' => null,
            'complimentary_item_reason_id' => null,
            'id' => 1,
        ]);

        $sequence = Sequence::factory()->make([
            'location_id' => 1,
            'id' => 1,
        ]);

        $inventory = Inventory::factory()->make([
            'product_id' => 1,
            'location_id' => 1,
            'id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($sequence);
        });

        $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('addNewForEcommerce')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->twice()
                ->andReturn($order);
        });

        $this->mock(OrderAddressQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewAddress')
                ->twice();
        });

        $this->mock(OrderItemQueries::class, function ($mock) use ($orderItem): void {
            $mock->shouldReceive('addNewForEcommerce')
                ->once()
                ->andReturn($orderItem);
        });

        $this->mock(OrderPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewForEcommerce')
                ->once();
        });

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('decreaseStock')
                ->once();
        });

        $this->mock(OrderInventoryService::class, function ($mock): void {
            $mock->shouldReceive('updateInventoryUnits')
                ->once();
        });

        $this->mock(CountryQueries::class, function ($mock): void {
            $mock->shouldReceive('existsById')
                ->twice();
            $mock->shouldReceive('checkCodeExists')
                ->twice();
        });

        $this->mock(StateQueries::class, function ($mock): void {
            $mock->shouldReceive('existsById')
                ->twice();
        });

        $this->mock(CityQueries::class, function ($mock): void {
            $mock->shouldReceive('existsById')
                ->twice();
        });

        $this->mock(UseEcommerceLoyaltyPointsService::class, function ($mock): void {
            $mock->shouldReceive('saveCartWideLoyaltyPointsDiscount')
                ->once();
        });

        $this->checkOrderEcommerceDetailsService->generateEcommerceLoyaltyPointsService = $this->mock(
            GenerateEcommerceLoyaltyPointsService::class,
            function ($mock): void {
                $mock->shouldReceive('saveGenerateLoyaltyPoints')
                    ->once();
            }
        );

        $storeManager->employee = $employee;

        $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;
        $this->checkOrderEcommerceDetailsService->companyId = $this->companyId;

        $this->checkOrderEcommerceDetailsService->generateVoucherECommerceService = $this->mock(
            GenerateVoucherECommerceService::class,
            function ($mock): void {
                $mock->shouldReceive('saveVouchers')
                    ->once();
            }
        );

        $this->checkOrderEcommerceDetailsService->orderEcommerceDiscountService = $this->mock(
            OrderEcommerceDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('getOrderDiscountAmountFor')
                    ->once()
                    ->andReturn([
                        'voucher_discount' => 0,
                        'cart_wide_loyalty_point_discount' => 0,
                        'total_discount' => 0,
                    ]);
                $mock->shouldReceive('getItemOrderDiscountAmount')
                    ->once()
                    ->andReturn(0.00);
            }
        );

        $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

        $response = $this->saveOrderEcommerceDetailsService->saveDetails(
            $this->checkOrderEcommerceDetailsService,
            $this->location->getKey(),
            null,
        );

        expect($response)->toBeInstanceOf(Order::class);
        expect($response->toArray())->toHaveKeys([
            'store_manager_id',
            'location_id',
            'member_id',
            'receipt_number',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
        ]);
    }
);

test(
    'getSequenceNumber method call and return the sequence number',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'country_id' => 1,
        ]);
        $sequence = Sequence::factory()->make([
            'number' => '000001',
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $response = $this->saveOrderEcommerceDetailsService->getSequenceNumber($location);
        expect($response)->toBeString();
    }
);

test('it calls addNew method of PosMismatchQueries class', function (): void {
    $this->checkOrderEcommerceDetailsService->orderMismatches = collect(['Test', 'test 1']);
    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->twice();
    });

    $this->saveOrderEcommerceDetailsService->saveOrderMismatches(new Order(), $this->checkOrderEcommerceDetailsService);
});

test('saveVoucherDiscount method can call addNew method of SaleDiscountQueries', function (): void {
    $this->orderDetails['voucher_number'] = 'ABC123';
    $this->orderDetails['voucher_discount_amount'] = 10.20;
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->companyId = $this->companyId;

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
        'number' => $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $orderEcommerceDiscountService = new OrderEcommerceDiscountService();
    $orderEcommerceDiscountService->voucher = $voucher;
    $this->checkOrderEcommerceDetailsService->orderEcommerceDiscountService = $orderEcommerceDiscountService;
    $this->checkOrderEcommerceDetailsService->location = $location;

    $this->mock(OrderDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsUsed')
            ->once();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->saveOrderEcommerceDetailsService->saveVoucherDiscount($this->checkOrderEcommerceDetailsService, 10);
});

test('saveVoucherDiscount method return null when voucher number not set', function (): void {
    $this->orderDetails['voucher_number'] = null;
    $this->orderDetails['voucher_discount_amount'] = 10.20;
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->companyId = $this->companyId;

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
        'number' => $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $orderEcommerceDiscountService = new OrderEcommerceDiscountService();
    $orderEcommerceDiscountService->voucher = $voucher;
    $this->checkOrderEcommerceDetailsService->orderEcommerceDiscountService = $orderEcommerceDiscountService;
    $this->checkOrderEcommerceDetailsService->location = $location;

    $this->mock(OrderDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->never();
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsUsed')
            ->never();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->never();
    });

    $response = $this->saveOrderEcommerceDetailsService->saveVoucherDiscount(
        $this->checkOrderEcommerceDetailsService,
        10
    );
    $this->assertNull($response);
});

test('saveVoucherDiscount method return null when voucher_discount_amount not set', function (): void {
    $this->orderDetails['voucher_number'] = '1353';
    $this->orderDetails['voucher_discount_amount'] = 0.00;
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->companyId = $this->companyId;

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => 1,
        'generated_by_sale_id' => 1,
        'number' => $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $orderEcommerceDiscountService = new OrderEcommerceDiscountService();
    $orderEcommerceDiscountService->voucher = $voucher;
    $this->checkOrderEcommerceDetailsService->orderEcommerceDiscountService = $orderEcommerceDiscountService;
    $this->checkOrderEcommerceDetailsService->location = $location;

    $this->mock(OrderDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->never();
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsUsed')
            ->never();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->never();
    });

    $response = $this->saveOrderEcommerceDetailsService->saveVoucherDiscount(
        $this->checkOrderEcommerceDetailsService,
        10
    );
    $this->assertNull($response);
});
