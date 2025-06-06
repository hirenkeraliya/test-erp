<?php

declare(strict_types=1);

use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountSaleService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\Location;
use App\Models\Style;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'checkForApplicability method throws an exception if HappyHourDiscount not in over record',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $saleDiscountService = new SaleDiscountService();
        $saleDiscountService->happyHourDiscounts = collect([]);
        $checkSaleDetailsService->saleDiscountService = $saleDiscountService;
        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];
        $happyHourDiscountSaleService->checkForApplicability($checkSaleDetailsService, $cartItem);
    }
)->throws(HttpException::class, 'Specified Happy Hour Discount is not available in our records.');

test(
    'checkForApplicability method calls same class methods as expected',
    function (): void {
        $mock = $this->createPartialMock(
            HappyHourDiscountSaleService::class,
            ['checkStore', 'checkDate', 'checkProduct', 'checkDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('checkStore');

        $mock->expects($this->once())
            ->method('checkDate');

        $mock->expects($this->once())
            ->method('checkProduct');

        $mock->expects($this->once())
            ->method('checkDiscountAmount');

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $saleDiscountService = new SaleDiscountService();

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
        ]);

        $happyHourDiscount->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->make([
            'happy_hour_discount_id' => 1,
            'counter_update_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
        ]);

        $saleDiscountService->happyHourDiscounts = collect([$happyHourDiscount]);
        $checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $mock->checkForApplicability($checkSaleDetailsService, $cartItem);
    }
);

test(
    'checkProduct method return null when all product allow',
    function (): void {
        $checkSaleDetailsService = new CheckSaleDetailsService();

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::ALL->value,
        ]);

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $response = $happyHourDiscountSaleService->checkProduct(
            $checkSaleDetailsService,
            $happyHourDiscount,
            $cartItem
        );

        $this->assertNull($response);
    }
);

test(
    'checkProduct method calls checkProductBrand same class methods as expected',
    function (): void {
        $mock = $this->createPartialMock(HappyHourDiscountSaleService::class, ['checkProductBrand']);

        $mock->expects($this->once())
            ->method('checkProductBrand');

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $product = commonGetProductDetails();
        $checkSaleDetailsService->products = collect([$product]);

        $saleDiscountService = new SaleDiscountService();

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::BRAND->value,
        ]);

        $saleDiscountService->happyHourDiscounts = collect([$happyHourDiscount]);
        $checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $response = $mock->checkProduct($checkSaleDetailsService, $happyHourDiscount, $cartItem);

        $this->assertNull($response);
    }
);

test(
    'checkProduct method calls checkProductStyle same class methods as expected',
    function (): void {
        $mock = $this->createPartialMock(HappyHourDiscountSaleService::class, ['checkProductStyle']);

        $mock->expects($this->once())
            ->method('checkProductStyle');

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $product = commonGetProductDetails();
        $checkSaleDetailsService->products = collect([$product]);

        $saleDiscountService = new SaleDiscountService();

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::STYLE->value,
        ]);

        $saleDiscountService->happyHourDiscounts = collect([$happyHourDiscount]);
        $checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $response = $mock->checkProduct($checkSaleDetailsService, $happyHourDiscount, $cartItem);

        $this->assertNull($response);
    }
);

test(
    'checkProduct method calls checkProductDepartment same class methods as expected',
    function (): void {
        $mock = $this->createPartialMock(HappyHourDiscountSaleService::class, ['checkProductDepartment']);

        $mock->expects($this->once())
            ->method('checkProductDepartment');

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $product = commonGetProductDetails();
        $checkSaleDetailsService->products = collect([$product]);

        $saleDiscountService = new SaleDiscountService();

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);

        $saleDiscountService->happyHourDiscounts = collect([$happyHourDiscount]);
        $checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $response = $mock->checkProduct($checkSaleDetailsService, $happyHourDiscount, $cartItem);

        $this->assertNull($response);
    }
);

test(
    'checkProduct method calls checkProductCategory same class methods as expected',
    function (): void {
        $mock = $this->createPartialMock(HappyHourDiscountSaleService::class, ['checkProductCategory']);

        $mock->expects($this->once())
            ->method('checkProductCategory');

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $product = commonGetProductDetails();
        $checkSaleDetailsService->products = collect([$product]);

        $saleDiscountService = new SaleDiscountService();

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::CATEGORY->value,
        ]);

        $saleDiscountService->happyHourDiscounts = collect([$happyHourDiscount]);
        $checkSaleDetailsService->saleDiscountService = $saleDiscountService;

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $response = $mock->checkProduct($checkSaleDetailsService, $happyHourDiscount, $cartItem);

        $this->assertNull($response);
    }
);

test(
    'checkProductBrand method throws an exception when brand not allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);
        $happyHourDiscount->brands = collect([]);
        $product = commonGetProductDetails();
        $happyHourDiscountSaleService->checkProductBrand($checkSaleDetailsService, $happyHourDiscount, $product);
    }
)->throws(HttpException::class, 'Specified Happy Hour Discount is not available for Specified product brand');

test(
    'checkProductBrand method return null when brand allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);

        $brand = Brand::factory()->make([
            'id' => 1,
        ]);

        $happyHourDiscount->brands = collect([$brand]);

        $product = commonGetProductDetails();
        $product->brand_id = 1;

        $response = $happyHourDiscountSaleService->checkProductBrand(
            $checkSaleDetailsService,
            $happyHourDiscount,
            $product
        );

        $this->assertNull($response);
    }
);

test(
    'checkProductStyle method throws an exception when style not allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);
        $happyHourDiscount->styles = collect([]);
        $product = commonGetProductDetails();
        $happyHourDiscountSaleService->checkProductStyle($checkSaleDetailsService, $happyHourDiscount, $product);
    }
)->throws(HttpException::class, 'Specified Happy Hour Discount is not available for Specified product style');

test(
    'checkProductStyle method return null when style allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);

        $style = Style::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $happyHourDiscount->styles = collect([$style]);

        $product = commonGetProductDetails();
        $product->style_id = 1;

        $response = $happyHourDiscountSaleService->checkProductStyle(
            $checkSaleDetailsService,
            $happyHourDiscount,
            $product
        );

        $this->assertNull($response);
    }
);

test(
    'checkProductCategory method throws an exception when category not allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);
        $happyHourDiscount->categories = collect([]);
        $product = commonGetProductDetails();
        $category = Category::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);
        $product->categories = collect([$category]);
        $happyHourDiscountSaleService->checkProductCategory($checkSaleDetailsService, $happyHourDiscount, $product);
    }
)->throws(HttpException::class, 'Specified Happy Hour Discount is not available for Specified product category');

test(
    'checkProductCategory method return null when category allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);

        $category = Category::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $happyHourDiscount->categories = collect([$category]);

        $product = commonGetProductDetails();
        $product->categories = collect([$category]);

        $response = $happyHourDiscountSaleService->checkProductCategory(
            $checkSaleDetailsService,
            $happyHourDiscount,
            $product
        );

        $this->assertNull($response);
    }
);

test(
    'checkProductDepartment method throws an exception when department not allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);
        $happyHourDiscount->departments = collect([]);
        $product = commonGetProductDetails();
        $happyHourDiscountSaleService->checkProductDepartment($checkSaleDetailsService, $happyHourDiscount, $product);
    }
)->throws(HttpException::class, 'Specified Happy Hour Discount is not available for Specified product department');

test(
    'checkProductDepartment method return null when department allow in Happy Hour Discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
        ]);

        $department = Department::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $happyHourDiscount->departments = collect([$department]);

        $product = commonGetProductDetails();
        $product->department_id = 1;

        $response = $happyHourDiscountSaleService->checkProductDepartment(
            $checkSaleDetailsService,
            $happyHourDiscount,
            $product
        );

        $this->assertNull($response);
    }
);

test(
    'checkDate method throws an exception when start and end date not match',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $checkSaleDetailsService->saleData = new SaleData('1215', '2023-01-03 10:00:00');
        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-01-02 10:00:00',
        ]);
        $happyHourDiscountSaleService->checkDate($checkSaleDetailsService, $happyHourDiscount);
    }
)->throws(
    HttpException::class,
    'Specified Happy Hour Discount is available between 2023-01-01 10:00:00 to 2023-01-02 10:00:00 only. The sale date is 2023-01-03 10:00:00.'
);

test(
    'checkDate method return null when Discount is available between start and end date',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $checkSaleDetailsService->saleData = new SaleData('1215', '2023-01-01 14:00:00');

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-01-02 10:00:00',
        ]);

        $response = $happyHourDiscountSaleService->checkDate($checkSaleDetailsService, $happyHourDiscount);

        $this->assertNull($response);
    }
);

test(
    'checkStore method throws an exception when store not match with discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'test',
            'type_id' => LocationTypes::STORE->value,
        ]);
        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 2,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-01-02 10:00:00',
        ]);
        $happyHourDiscountSaleService->checkStore($checkSaleDetailsService, $happyHourDiscount);
    }
)->throws(HttpException::class, 'Specified Happy Hour Discount is not available for test location');

test(
    'checkStore method return null when store match with discount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);
        $checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 1,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-01-02 10:00:00',
        ]);

        $response = $happyHourDiscountSaleService->checkStore($checkSaleDetailsService, $happyHourDiscount);

        $this->assertNull($response);
    }
);

test(
    'getCalculateItemDiscountAmount method return discount amount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 5.0,
        ];

        $response = $happyHourDiscountSaleService->getCalculateItemDiscountAmount(
            $checkSaleDetailsService,
            $cartItem,
            5.00
        );
        $this->assertEquals($response, 50.00);
    }
);

test(
    'getItemDiscountAmount method return discount amount',
    function (): void {
        $happyHourDiscountSaleService = new HappyHourDiscountSaleService();

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 5.0,
        ];

        $response = $happyHourDiscountSaleService->getItemDiscountAmount($cartItem);
        $this->assertEquals($response, 5.00);
    }
);

test(
    'checkDiscountAmount method throws an exception when Specified discount amount not match with calculate discount amount',
    function (): void {
        $mock = $this->createPartialMock(HappyHourDiscountSaleService::class, ['getCalculateItemDiscountAmount']);

        $mock->expects($this->once())
            ->method('getCalculateItemDiscountAmount')
            ->will($this->returnValue(10.10));

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 2,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-01-02 10:00:00',
        ]);

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 5.0,
        ];

        $mock->checkDiscountAmount($checkSaleDetailsService, $happyHourDiscount, $cartItem);
    }
)->throws(
    HttpException::class,
    'Specified discount amount does not match with our calculations. The actual discount amount is 10.1 and requested discount amount is 5.'
);

test(
    'checkDiscountAmount method return null when Specified discount amount match with calculate discount amount',
    function (): void {
        $mock = $this->createPartialMock(HappyHourDiscountSaleService::class, ['getCalculateItemDiscountAmount']);

        $mock->expects($this->once())
            ->method('getCalculateItemDiscountAmount')
            ->will($this->returnValue(10.10));

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->saleMismatches = collect([]);

        $happyHourDiscount = HappyHourDiscount::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'location_id' => 2,
            'company_id' => 1,
            'authorizer_id' => 1,
            'offline_id' => '12345',
            'product_type_id' => ProductTypes::DEPARTMENTS->value,
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-01-02 10:00:00',
        ]);

        $cartItem = [
            'id' => 1,
            'price' => '10.00',
            'quantity' => '10',
            'happy_hours_offline_id' => '12345',
            'happy_hours_discount_amount' => 10.10,
        ];

        $response = $mock->checkDiscountAmount($checkSaleDetailsService, $happyHourDiscount, $cartItem);
        $this->assertNull($response);
    }
);
