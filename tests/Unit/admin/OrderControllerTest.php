<?php

declare(strict_types=1);

use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\OrderQueries;
use App\Domains\Order\Resources\MarketPlaceOrderListResource;
use App\Domains\Order\Resources\OrderListResource;
use App\Domains\Order\Resources\OrderReceiptResource;
use App\Domains\Order\Services\OrderService;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderItem\Resources\OrderItemsReportResource;
use App\Http\Controllers\Admin\OrderController;
use App\Models\Attribute;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('fetchB2bOrders method call and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'member_id' => null,
        'type_id' => null,
        'e_invoice_submitted' => null,
    ];
    $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getPaginatedCompleteOrderWithRelations')
            ->once()
            ->with($requestParameter, 1, 1, OrderChannels::B2B_ORDERS->value, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
        $mock->shouldReceive('getFilteredTotalsForReport')
            ->once()
            ->with($requestParameter, 1, 1, OrderChannels::B2B_ORDERS->value, $companyId)
            ->andReturn(collect());
        $mock->shouldReceive('getCompleteOrderWithRelationsForExport')
            ->andReturn(collect());
    });
    $orderController = new OrderController($orderQueries);

    $requestParameter['location_id'] = 1;
    $requestParameter['store_manager_id'] = 1;

    $response = $orderController->fetchB2bOrders(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(OrderListResource::collection(collect([])), $response['data']);
});

test('fetchMarketplacesOrders method call and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'member_id' => null,
        'type_id' => null,
        'location_id' => null,
        'company_id' => null,
        'store_manager_id' => null,
        'e_invoice_submitted' => null,
    ];

    $orderQueries = $this->mock(OrderQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedCompleteOrderWithRelations')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 20, 15));
        $mock->shouldReceive('getFilteredTotalsForReport')
            ->once()
            ->andReturn(collect());
    });

    $orderController = new OrderController($orderQueries);

    $requestParameter['location_id'] = 1;
    $requestParameter['store_manager_id'] = 1;

    $response = $orderController->fetchMarketplacesOrders(new Request($requestParameter));
    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(MarketPlaceOrderListResource::collection(collect([])), $response['data']);
});

test('fetchOrderItemsByOrderId method call and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'order_id' => '1',
        'store_manager_id' => 1,
        'location_id' => 1,
    ];

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getOrderItemsBy')
            ->once()
            ->with(
                $requestParameter['order_id'],
                $requestParameter['store_manager_id'],
                $requestParameter['location_id'],
                $companyId
            )
            ->andReturn(new Order());
    });
    $orderController = new OrderController($productQueries);

    $response = $orderController->fetchOrderItemsByOrderId(new Request($requestParameter));
    expect($response)->toBeArray();
    $this->assertArrayHasKey('order_details', $response);
    $this->assertInstanceOf(OrderItemsReportResource::class, $response['order_details']);
});

test('printOrderReceipt method call and returns proper response', function (): void {
    $companyId = 1;
    $orderId = 1;

    setCompanyIdInSession($companyId);

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($orderId): void {
        $mock->shouldReceive('getOrderDetailsForReceipt')
            ->once()
            ->with($orderId)
            ->andReturn(new Order());
    });
    $orderController = new OrderController($productQueries);

    $response = $orderController->printOrderReceipt($orderId);
    expect($response)->toBeArray();
    $this->assertArrayHasKey('order_details', $response);
    $this->assertInstanceOf(OrderReceiptResource::class, $response['order_details']);
});

test('printOrderTaxInvoice method call and returns proper response when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $order->location = $location;

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
    ]);

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'exchange_item_id' => 1,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->product = $product;

    $order->orderItems = collect([$orderItem]);
    $order->member = null;

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getOrderDetailsForReceipt')
            ->once()
            ->with($order->id)
            ->andReturn($order);
    });
    $orderController = new OrderController($productQueries);

    $response = $orderController->printOrderTaxInvoice($order->id);
    expect($response)->toBeString();
});

test('printOrderTaxInvoice method call and returns proper response when product variant is true', function (): void {
    $companyId = 1;

    Config::set('app.product_variant', true);

    setCompanyIdInSession($companyId);

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $order->location = $location;

    $masterProduct = MasterProduct::factory()->make([
        'unit_of_measure_id' => null,
        'department_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'variant_template_id' => 1,
        'vendor_id' => null,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'master_product_id' => $masterProduct->id,
    ]);

    $attribute = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'company_id' => $companyId,
    ]);

    $productVariantValue = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'attribute_id' => $attribute->id,
    ]);

    $productVariantValue->attribute = $attribute;

    $product->productVariantValues = collect([$productVariantValue]);

    $masterProduct->productVariants = collect([$product]);

    $product->masterProduct = $masterProduct;

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'exchange_item_id' => 1,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->product = $product;

    $order->orderItems = collect([$orderItem]);
    $order->member = null;

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getOrderDetailsForReceipt')
            ->once()
            ->with($order->id)
            ->andReturn($order);
    });
    $orderController = new OrderController($productQueries);

    $response = $orderController->printOrderTaxInvoice($order->id);
    expect($response)->toBeString();
});

test('printPurchaseOrder method call and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $order->location = $location;

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
    ]);

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'exchange_item_id' => 1,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->product = $product;

    $order->orderItems = collect([$orderItem]);
    $order->member = null;

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getOrderDetailsForReceipt')
            ->once()
            ->with($order->id)
            ->andReturn($order);
    });

    $orderController = new OrderController($productQueries);

    $response = $orderController->printPurchaseOrder($order->id);

    expect($response)->toBeString();
});

test(
    'printLayawayOrderReport method call and returns proper response when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $companyId = 1;

        setCompanyIdInSession($companyId);

        $company = Company::factory()->make([
            'id' => $companyId,
            'name' => 'ABCD',
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $company;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'group_id' => 1,
        ]);

        $promoter->employee = $employee;

        $paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
        ]);

        $orderPayment = OrderPayment::factory()->make([
            'id' => 1,
            'order_id' => $order->id,
            'store_manager_id' => 1,
            'location_id' => $location->id,
            'payment_type_id' => $paymentType->id,
        ]);

        $orderPayment->paymentType = $paymentType;

        $order->location = $location;
        $order->payments = collect([$orderPayment]);

        $product = Product::factory()->make([
            'id' => 1,
            'unit_of_measure_id' => null,
            'season_id' => null,
            'department_id' => null,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
            'brand_id' => 1,
            'company_id' => 1,
        ]);

        $orderItem = OrderItem::factory()->make([
            'id' => 1,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'exchange_item_id' => 1,
            'complimentary_item_reason_id' => 1,
        ]);

        $orderItem->product = $product;
        $orderItem->promoters = collect([$promoter]);

        $order->orderItems = collect([$orderItem]);
        $order->member = null;

        $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getLayawayOrderItemsByForPrint')
                ->once()
                ->with($order->id)
                ->andReturn($order);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getNameAndCodeWithCompanyById')
                ->once()
                ->with($order->location_id)
                ->andReturn($order->location);
        });

        $orderController = new OrderController($productQueries);

        $response = $orderController->printLayawayOrderReport($order->id);

        expect($response)->toBeString();
    }
);

test('printLayawayOrderReport method call and returns proper response when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $promoter->employee = $employee;

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $orderPayment = OrderPayment::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'store_manager_id' => 1,
        'location_id' => $location->id,
        'payment_type_id' => $paymentType->id,
    ]);

    $orderPayment->paymentType = $paymentType;

    $order->location = $location;
    $order->payments = collect([$orderPayment]);

    $masterProduct = MasterProduct::factory()->make([
        'unit_of_measure_id' => null,
        'department_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'variant_template_id' => 1,
        'vendor_id' => null,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'master_product_id' => $masterProduct->id,
    ]);

    $attribute = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'company_id' => $companyId,
    ]);

    $productVariantValue = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'attribute_id' => $attribute->id,
    ]);

    $productVariantValue->attribute = $attribute;

    $product->productVariantValues = collect([$productVariantValue]);

    $masterProduct->productVariants = collect([$product]);

    $product->masterProduct = $masterProduct;

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'exchange_item_id' => 1,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->product = $product;
    $orderItem->promoters = collect([$promoter]);

    $order->orderItems = collect([$orderItem]);
    $order->member = null;

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getLayawayOrderItemsByForPrint')
            ->once()
            ->with($order->id)
            ->andReturn($order);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getNameAndCodeWithCompanyById')
            ->once()
            ->with($order->location_id)
            ->andReturn($order->location);
    });

    $orderController = new OrderController($productQueries);

    $response = $orderController->printLayawayOrderReport($order->id);

    expect($response)->toBeString();
});

test('printCreditOrderReport method call and returns proper response when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $promoter->employee = $employee;

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $orderPayment = OrderPayment::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'store_manager_id' => 1,
        'location_id' => $location->id,
        'payment_type_id' => $paymentType->id,
    ]);

    $orderPayment->paymentType = $paymentType;

    $order->location = $location;
    $order->payments = collect([$orderPayment]);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
    ]);

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'exchange_item_id' => 1,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->product = $product;
    $orderItem->promoters = collect([$promoter]);

    $order->orderItems = collect([$orderItem]);
    $order->member = null;

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getLayawayOrderItemsByForPrint')
            ->once()
            ->with($order->id)
            ->andReturn($order);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getNameAndCodeWithCompanyById')
            ->once()
            ->with($order->location_id)
            ->andReturn($order->location);
    });

    $orderController = new OrderController($productQueries);

    $response = $orderController->printCreditOrderReport($order->id);

    expect($response)->toBeString();
});

test('printCreditOrderReport method call and returns proper response when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => 1,
    ]);

    $promoter->employee = $employee;

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $orderPayment = OrderPayment::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'store_manager_id' => 1,
        'location_id' => $location->id,
        'payment_type_id' => $paymentType->id,
    ]);

    $orderPayment->paymentType = $paymentType;

    $order->location = $location;
    $order->payments = collect([$orderPayment]);

    $masterProduct = MasterProduct::factory()->make([
        'unit_of_measure_id' => null,
        'department_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'variant_template_id' => 1,
        'vendor_id' => null,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'style_id' => null,
        'brand_id' => 1,
        'company_id' => 1,
        'master_product_id' => $masterProduct->id,
    ]);

    $attribute = Attribute::factory()->make([
        'id' => 1,
        'template_id' => 1,
        'company_id' => $companyId,
    ]);

    $productVariantValue = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'attribute_id' => $attribute->id,
    ]);

    $productVariantValue->attribute = $attribute;

    $product->productVariantValues = collect([$productVariantValue]);

    $masterProduct->productVariants = collect([$product]);

    $product->masterProduct = $masterProduct;

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'exchange_item_id' => 1,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->product = $product;
    $orderItem->promoters = collect([$promoter]);

    $order->orderItems = collect([$orderItem]);
    $order->member = null;

    $productQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getLayawayOrderItemsByForPrint')
            ->once()
            ->with($order->id)
            ->andReturn($order);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('getNameAndCodeWithCompanyById')
            ->once()
            ->with($order->location_id)
            ->andReturn($order->location);
    });

    $orderController = new OrderController($productQueries);

    $response = $orderController->printCreditOrderReport($order->id);

    expect($response)->toBeString();
});

test('fetchOrderItemsEcommerceByOrderId method call and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'order_id' => '1',
        'store_manager_id' => 1,
        'location_id' => 1,
    ];

    $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getOrderItemsForEcommerce')
            ->once()
            ->with($requestParameter['order_id'], $requestParameter['location_id'], $companyId)
            ->andReturn(new Order());
    });
    $orderController = new OrderController($orderQueries);

    $response = $orderController->fetchOrderItemsEcommerceByOrderId(new Request($requestParameter));
    expect($response)->toBeArray();
    $this->assertArrayHasKey('order_details', $response);
    $this->assertInstanceOf(OrderItemsReportResource::class, $response['order_details']);
});

test('exportMarketplaceOrders method call and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $filterData = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 10,
        'date_range' => 'null',
        'member_id' => 'null',
        'type_id' => 1,
        'location_id' => 'null',
        'store_manager_id' => null,
        'e_invoice_submitted' => 0,
        'export_columns' => null,
    ];
    $orderQueries = $this->mock(OrderQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedCompleteOrderWithRelations')
            ->andReturn(new LengthAwarePaginator([], 20, 15));

        $mock->shouldReceive('getFilteredTotalsForReport')
            ->andReturn(collect());

        $mock->shouldReceive('getCompleteOrderWithRelationsForExport')
            ->andReturn(collect());
    });

    $orderController = new OrderController($orderQueries);

    $response = $orderController->exportMarketplaceOrders('filename.csv', new Request($filterData));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('printMarketplaceOrders method call and returns proper response', function (): void {
    setCompanyIdInSession();
    $filterData = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 10,
        'date_range' => 'null',
        'member_id' => 'null',
        'type_id' => 1,
        'location_id' => 'null',
        'store_manager_id' => null,
        'e_invoice_submitted' => 0,
        'export_columns' => null,
    ];
    $orderQueries = $this->mock(OrderQueries::class, function ($mock): void {
    });
    $this->mock(OrderService::class, function ($mock): void {
        $mock->shouldReceive('prepareDataForPrintMarketplaceOrder')
            ->once()
            ->andReturn(collect([]));

        $mock->shouldReceive('orderDataPrint')
            ->once()
            ->andReturn(collect());
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(new Company());
    });

    $this->mock(PrintPdfHeaderFilterService::class, function ($mock): void {
        $mock->shouldReceive('buildFilterData')
            ->once()
            ->andReturn([]);
    });

    $orderController = new OrderController($orderQueries);

    $response = $orderController->printMarketplaceOrders(new Request($filterData));

    expect($response)->toBeString();
});

test('fetchOrderAddress can return shipping address', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $request = new Request([
        'order_id' => '1',
        'type' => OrderAddressesType::SHIPPING_ADDRESS->value,
    ]);

    $orders = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $orderAddress = OrderAddress::factory()->make([
        'order_id' => $orders->id,
        'type_id' => OrderAddressesType::SHIPPING_ADDRESS->value,
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '1234567890',
        'area_code' => '12345',
    ]);

    $this->mock(OrderAddressQueries::class, function ($mock) use ($orderAddress): void {
        $mock->shouldReceive('getOrderAddress')
            ->once()
            ->andReturn($orderAddress);
    });

    $orderController = resolve(OrderController::class);

    $response = $orderController->fetchOrderAddress($request);
    expect($response)->toBeArray();
});

test('updateAddress can update the address', function (): void {
    Queue::fake();
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $request = new Request([
        'order_id' => '1',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '1234567890',
        'area_code' => '12345',
        'city_name' => 'Mountain View Gardens',
        'type_id' => 1,
    ]);

    $orderAddressId = 1;

    $orderQueries = $this->mock(OrderQueries::class);

    $this->mock(OrderAddressQueries::class, function ($mock) use ($orderAddressId): void {
        $mock->shouldReceive('updateOrderAddressECommerce')
            ->once()
            ->withArgs(fn ($data, $id): bool => $id === $orderAddressId &&
                'John' === $data->first_name &&
                'Doe' === $data->last_name &&
                '1234567890' === $data->phone &&
                '12345' === $data->area_code &&
                'Mountain View Gardens' === $data->city_name &&
                '123 Main St' === $data->address_line_1 &&
                'Apt 4B' === $data->address_line_2);
    });

    $orderController = new OrderController($orderQueries);

    $orderController->updateAddress($request, $orderAddressId);
});
