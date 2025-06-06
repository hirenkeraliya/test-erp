<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\BookingPaymentUseTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\Services\CheckCompanySettingService;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Domains\Sale\Services\SaleReturnService;
use App\Domains\Sale\Services\SaleTaxService;
use App\Domains\Sale\Services\SaleUserService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Domains\Voucher\Services\GenerateVoucherService;
use App\Models\Batch;
use App\Models\BookingPayment;
use App\Models\BoxProduct;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Country;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\GiftCard;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Location;
use App\Models\Member;
use App\Models\Membership;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use App\Models\UnitOfMeasureDerivative;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->companyId = 1;
    $this->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);
    $country = Country::factory()->make([
        'id' => 1,
    ]);
    $currency = Currency::factory()->make([
        'id' => 1,
        'country_id' => $country->id,
        'name' => 'Malaysian Ringgit',
        'code' => 'MYR',
    ]);
    $currencyRate = CurrencyRate::factory()->make([
        'id' => 1,
        'currency_id' => $currency->id,
        'rate' => 1,
    ]);

    $this->checkSaleDetailsService->company = $this->company;
    $this->checkSaleDetailsService->company->countries = collect([$country]);
    foreach ($this->checkSaleDetailsService->company->countries as $country) {
        $country->currency = $currency;
        $country->currency->currencyRate = $currencyRate;
    }

    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'items' => [
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
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

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
        'is_sold_as_single_item' => false,
        'sell_item_via_derivative' => true,
    ]);

    $this->batchA = Batch::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'product_id' => $this->product->id,
        'number' => '123',
    ]);

    $this->batchB = Batch::factory()->make([
        'id' => 2,
        'company_id' => $this->companyId,
        'product_id' => $this->product->id,
        'number' => '2345',
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
        'batch_id' => $this->batchA->id,
        'quantity' => 30.00,
    ]);

    $this->inventoryUnitB = InventoryUnit::factory()->make([
        'id' => 2,
        'inventory_id' => $this->inventory->id,
        'purchase_amount_id' => 2,
        'batch_id' => $this->batchB->id,
        'quantity' => 10.00,
    ]);

    $this->inventory->inventoryUnits = collect([$this->inventoryUnitA]);

    $this->cartItems = collect($this->saleData->items);

    $this->batches = collect([$this->batchA, $this->batchB]);

    $this->cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $this->saleUserService = new SaleUserService();
    $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);
});

test('setDetails method works as expected', function (): void {
    $this->mock(SaleDiscountService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
    });

    $this->mock(SaleReturnService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
    });

    $this->mock(GenerateLoyaltyPointsService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('memberExistsById')
            ->once();
    });

    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIds')
            ->once();
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoriesByProductIds')
            ->once();
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once();
    });
    $cartItems = $this->cartItems->toArray();
    $cartItems[0]['derivative_id'] = 1;

    $this->checkSaleDetailsService->setDetails(
        $this->saleData,
        collect($this->product),
        collect($cartItems),
        collect($this->batches),
        $this->location,
        $this->cashier,
        1
    );

    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('getCurrentLocation method throws exception if counter_update_id is not present in cashier', function (): void {
    $this->checkSaleDetailsService->getCurrentLocation($this->cashier);
})->throws(HttpException::class, 'The counter has not been opened yet.');

test('it calls the getLocationByCountersCounterUpdateId method of LocationQueries class', function (): void {
    $this->cashier->counter_update_id = 1;
    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with(1)
            ->andReturn(new Location());
    });

    $response = $this->checkSaleDetailsService->getCurrentLocation($this->cashier);

    expect($response)->toBeInstanceOf(Location::class);
});

test('hasCartItems method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasCartItems();
    $this->assertTrue(true);

    $this->checkSaleDetailsService->cartItems = collect([]);
    $this->checkSaleDetailsService->hasCartItems();
    $this->assertFalse(false);
});

test('hasCartPromotion method returns boolean as expected', function (): void {
    $cartData = $this->saleDetails;
    $cartData['cart_promotion_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$cartData);
    $response = $this->checkSaleDetailsService->hasCartPromotion();
    $this->assertTrue($response);

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $response = $this->checkSaleDetailsService->hasCartPromotion();
    $this->assertFalse($response);
});

test('checkRequestDetails method returns void when cart items is empty', function (): void {
    $this->checkSaleDetailsService->saleReturnService = $this->mock(
        SaleReturnService::class,
        function ($mock): void {
            $mock->shouldReceive('hasReturnItems')
                ->times(2)
                ->andReturn(false);
        }
    );

    $this->checkSaleDetailsService->company = $this->company;
    $this->checkSaleDetailsService->cartItems = collect([]);
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkSaleDetailsService->checkRequestDetails(true);
    $this->assertNull($response);
});

test('checkRequestDetails method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        [
            'hasCartItems',
            'checkRecordsExists',
            'checkCartItem',
            'checkPaymentDetails',
            'getSaleRoundOffAmount',
            'checkOfflineSaleId',
            'checkBillReferenceNumberDetails',
            'checkMemberExists',
            'hasVoucher',
            'getCartSubtotalAfterDiscount',
            'getTotalTaxAmount',
            'checkProductPriceWithType',
            'hasGenerateLoyaltyPoints',
            'hasPriceOverrideForCart',
            'checkEmployeePurchaseLimit',
        ]
    );
    $cartItems = $this->saleData->items;

    $cartItems[0]['total_price_paid'] = 110.1;
    $mock->cartItems = collect($cartItems);
    $mock->products = collect([$this->product]);

    $this->saleData->voucher_number;
    $mock->saleData = $this->saleData;
    $mock->saleMismatches = collect([]);
    $mock->location = $this->location;
    $mock->companyId = 1;
    $mock->company = $this->company;
    $totalCartDiscountAmount = 10.10;
    $total = $this->saleDetails['payments'][0]['amount'];
    $afterDiscountEffectTotal = $total - $totalCartDiscountAmount;
    $saleReturnService = new SaleReturnService();

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'total_tax_amount' => 2.50,
        'price_paid_per_unit' => 10.50,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $saleReturnService->returnedSaleItems = collect([$saleItem]);

    $mock->generateVoucherService = $this->mock(GenerateVoucherService::class, function ($mock): void {
        $mock->shouldReceive('checkVouchers')
            ->once();
    });

    $mock->expects($this->once())
        ->method('hasCartItems')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('hasVoucher')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('hasPriceOverrideForCart')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('checkRecordsExists');

    $mock->expects($this->once())
        ->method('checkCartItem');

    $mock->expects($this->once())
        ->method('getSaleRoundOffAmount')
        ->will($this->returnValue(0))
        ->with(100.0);

    $mock->expects($this->once())
        ->method('checkPaymentDetails')
        ->with(89.50);

    $mock->expects($this->once())
        ->method('checkOfflineSaleId');

    $mock->expects($this->once())
        ->method('checkOfflineSaleId');

    $mock->expects($this->once())
        ->method('checkProductPriceWithType');

    $mock->expects($this->once())
        ->method('checkEmployeePurchaseLimit');

    $mock->expects($this->once())
        ->method('hasGenerateLoyaltyPoints')
        ->will($this->returnValue(true));

    $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock) use (
        $totalCartDiscountAmount
    ): void {
        $mock->shouldReceive('checkCartWidePromotionDetails')
            ->once();
        $mock->shouldReceive('getCartDiscountAmountFor')
            ->once()
            ->andReturn([
                'total_discount' => $totalCartDiscountAmount,
                'voucher_discount' => 0,
                'price_override_discount' => 0,
                'cart_wide_discount' => 0,
                'cart_wide_loyalty_point_discount' => 0,
            ]);
        $mock->shouldReceive('getItemDiscountAmountFor')
            ->once()
            ->andReturn([
                'total_discount' => 0,
            ]);
        $mock->shouldReceive('getItemCartDiscountAmount')
            ->once();
        $mock->shouldReceive('checkVoucherDetails')
            ->once();
        $mock->shouldReceive('checkPriceOverrideForCartDetails')
            ->once();
        $mock->shouldReceive('getTotalItemDiscountAmount')
            ->once();
    });

    $mock->saleTaxService = $this->mock(SaleTaxService::class, function ($mock) use (
        $afterDiscountEffectTotal
    ): void {
        $mock->shouldReceive('getTotalTaxAmountFor')
            ->once()
            ->with($afterDiscountEffectTotal)
            ->andReturn(10.10);
        $mock->shouldReceive('getItemTaxAmountFor')
            ->once()
            ->andReturn(10.10);
        $mock->shouldReceive('checkTaxDetails')
            ->once()
            ->with(10.10);
    });

    $mock->saleReturnService = $this->mock(SaleReturnService::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('hasReturnItems')
            ->times(2)
            ->andReturn(true);
        $mock->shouldReceive('checkReturnItems')
            ->once();
        $mock->shouldReceive('getReturnItemsSubtotal')
            ->once()
            ->andReturn($saleItem->price_paid_per_unit);
    });

    $mock->generateLoyaltyPointsService = $this->mock(
        GenerateLoyaltyPointsService::class,
        function ($mock): void {
            $mock->shouldReceive('checkLoyaltyPoints')
                ->times(1)
                ->andReturn(collect([]));
        }
    );

    $mock->saleDiscountService = $mockSaleDiscountService;

    $mock->checkRequestDetails(true);
});

test(
    'checkRequestDetails method throws an exception if price does not specified for regular product type',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        unset($cartItem['price']);

        $this->checkSaleDetailsService->checkProductPriceWithType($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Price is not provided for the product with the name ABC');

test(
    'checkRequestDetails method throws an exception if price does not specified for bundle product type',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        unset($cartItem['price']);

        $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;

        $this->checkSaleDetailsService->checkProductPriceWithType($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Price is not provided for the product with the name ABC');

test(
    'checkRequestDetails method throws an exception if price does not specified for assembly product type',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        unset($cartItem['price']);

        $this->product->type_id = ProductTypes::ASSEMBLY_PRODUCT->value;

        $this->checkSaleDetailsService->checkProductPriceWithType($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Price is not provided for the product with the name ABC');

test(
    'checkRequestDetails method return null when product purchase by loyalty points',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 10;

        $response = $this->checkSaleDetailsService->checkProductPriceWithType($this->product, $cartItem);
        $this->assertNull($response);
    }
);

test(
    'checkRequestDetails method throws an exception if the open price does not specify for non-regular type of products',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['price'] = null;
        $this->product->type_id = ProductTypes::POSTAGE_COST->value;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->checkProductPriceWithType($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Open Price is not provided for the product with the name ABC');

test('checkCartItem method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        [
            'checkDerivativeDetails',
            'checkPriceMismatch',
            'checkBatchNumber',
            'checkPromotersDetails',
            'checkNegativeInventory',
            'checkProductSoldAsSingleItem',
            'checkProductLoyaltyPoints',
            'checkAllowDecimalQty',
        ]
    );

    $mock->expects($this->once())
            ->method('checkDerivativeDetails');

    $mock->expects($this->once())
            ->method('checkPriceMismatch');

    $mock->expects($this->once())
            ->method('checkBatchNumber');

    $mock->expects($this->once())
            ->method('checkPromotersDetails');

    $mock->expects($this->once())
            ->method('checkNegativeInventory');

    $mock->expects($this->once())
            ->method('checkProductSoldAsSingleItem');

    $mock->expects($this->once())
            ->method('checkProductLoyaltyPoints');

    $mock->expects($this->once())
            ->method('checkAllowDecimalQty');

    $mock->saleDiscountService = $this->mock(SaleDiscountService::class, function ($mock): void {
        $mock->shouldReceive('checkItemWisePromotionDetails')
                ->once();
    });

    $mock->checkCartItem($this->product, $this->cartItems[0]);
});

test('checkProducts method sets the saleMismatches when one of the products is archived', function (): void {
    $this->product->status = false;
    $this->checkSaleDetailsService->products = collect([$this->product]);
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->checkProducts();
})->throws(HttpException::class, 'Some of the products are archived.');

test('checkProducts method throws an exception when product is not available in our records', function (): void {
    $this->checkSaleDetailsService->products = collect([]);
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->checkProducts();
})->throws(HttpException::class, 'Some of the products are not in our records.');

test(
    'checkPaymentTypes method sets saleMismatches when Some of the payment types are inactive',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $paymentTypes = new Collection([
            PaymentType::factory()->make([
                'id' => 1,
                'company_id' => $this->companyId,
                'name' => 'Payment 1',
                'is_member_required' => false,
                'status' => false,
            ]),
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->checkSaleDetailsService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Some of the payment types are inactive.');

test(
    'checkPaymentTypes method sets saleMismatches when Member is required for one of the selected payment types',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $paymentTypes = new Collection([
            PaymentType::factory()->make([
                'id' => 1,
                'company_id' => $this->companyId,
                'name' => 'Payment 1',
                'is_member_required' => true,
                'status' => true,
            ]),
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->checkSaleDetailsService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Member is required for one of the selected payment types.');

test(
    'checkPaymentTypes method throws an exception when payment type is not available in our records',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->mock(PaymentTypeQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn(new Collection([]));
        });

        $this->checkSaleDetailsService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Some of the payment types are not available in our records.');

test('checkPaymentTypes method returns the response as expected', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $paymentTypes = new Collection([
        PaymentType::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'name' => 'Payment 1',
            'is_member_required' => true,
            'status' => true,
        ]),
    ]);

    $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn($paymentTypes);
    });

    $response = $this->checkSaleDetailsService->checkPaymentTypes();

    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkPriceMismatch method sets saleMismatches when product retail price is not available', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = 1;

    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->product->retail_price = null;
    $this->checkSaleDetailsService->checkPriceMismatch($this->product, $this->saleData->items[0]);
    // TODO: Temporary skip due to pos is not able create sale
})->throws(HttpException::class, 'Price is not available for the product with the name ABC')->skip();

test(
    'checkPriceMismatch method sets saleMismatches when product retail price is not match with our records',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->product->retail_price = 5;

        $this->checkSaleDetailsService->checkPriceMismatch($this->product, $this->saleData->items[0]);
    }
)->throws(
    HttpException::class,
    'Product retail price mismatched. Actual Product retail price is 5 And Given product retail price is 10'
);

test(
    'checkPriceMismatch method sets saleMismatches when product open price is less than minimum price',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->product->retail_price = null;
        $this->product->type_id = ProductTypes::SPECIAL_ORDER->value;
        $cartItem = $this->saleData->items[0];
        unset($cartItem['price']);
        $cartItem['open_price'] = $this->product->minimum_price - 1;

        $this->checkSaleDetailsService->checkPriceMismatch($this->product, $cartItem);
    }
)->throws(HttpException::class);

test(
    'checkPriceMismatch method sets saleMismatches when product open price is less than minimum price with is_exchange',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->product->retail_price = null;
        $this->product->type_id = ProductTypes::SPECIAL_ORDER->value;
        $cartItem = $this->saleData->items[0];
        unset($cartItem['price']);
        $cartItem['open_price'] = $this->product->minimum_price - 1;
        $cartItem['is_exchange'] = true;

        $this->checkSaleDetailsService->checkPriceMismatch($this->product, $cartItem);

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test('checkDerivativeDetails method returns nothing when no derivative details specified', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->checkDerivativeDetails($this->product, $this->saleData->items[0]);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->count() === 0);
});

test(
    'checkDerivativeDetails method sets the sale mismatches when the product does not have unit of measure',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->product->unit_of_measure_id = null;
        $cartItem = $this->saleData->items[0];
        $cartItem['derivative_id'] = 1;

        $this->checkSaleDetailsService->checkDerivativeDetails($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Specified product (Named: ABC) does not have unit of measure.');

test(
    'checkDerivativeDetails method sets the sale mismatches when the product cannot be sold via derivative',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->product->sell_item_via_derivative = false;
        $cartItem = $this->saleData->items[0];
        $cartItem['derivative_id'] = 1;

        $this->checkSaleDetailsService->checkDerivativeDetails($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Specified product (Named: ABC) cannot be sold via derivative.');

test(
    'checkDerivativeDetails method sets the sale mismatches when specified derivative id does not exists in our records.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->derivatives = collect([
            UnitOfMeasureDerivative::factory()->make([
                'id' => 1,
                'unit_of_measure_id' => 1,
            ]),
        ]);
        $this->product->unit_of_measure_id = 1;
        $cartItem = $this->saleData->items[0];
        $cartItem['derivative_id'] = 2;

        $this->checkSaleDetailsService->checkDerivativeDetails($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Specified derivative id is not available in our records.');

test(
    'checkDerivativeDetails method sets the sale mismatches when specified derivative id does not match with product unit of measure.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->derivatives = collect([
            UnitOfMeasureDerivative::factory()->make([
                'id' => 1,
                'name' => 'XYZ',
                'unit_of_measure_id' => 1,
            ]),
        ]);
        $this->product->unit_of_measure_id = 2;
        $cartItem = $this->saleData->items[0];
        $cartItem['derivative_id'] = 1;

        $this->checkSaleDetailsService->checkDerivativeDetails($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Specified derivative XYZ does not match with products (Named: ABC) unit of measure.');

test('getCartSubtotal method returns the cart subtotal as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);

    $response = $this->checkSaleDetailsService->getCartSubtotal();

    $this->assertTrue(100.00 === $response);
});

test(
    'getSaleRoundOffAmount method returns the sale round off as expected',
    function (float $subtotal): void {
        $this->saleDetails['sale_round_off_amount'] = -0.02;
        $this->saleDetails['items'][0]['total_price_paid'] = $subtotal;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $response = $this->checkSaleDetailsService->getSaleRoundOffAmount($subtotal);
        $this->assertTrue(-0.02 === $response);
    }
)->with([[555.22], [222.22]]);

test(
    'checkPaymentDetails method sets saleMismatches when subtotal is more than 0 and payments not specified',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [];
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->checkPaymentDetails(100);
    }
)->throws(HttpException::class, 'Payment is required. Because of subtotal is 100');

test(
    'checkPaymentDetails method sets saleMismatches when subtotal is more than specified payments',
    function (float $paymentAmount): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkPaymentCurrency']);

        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => 1,
                'amount' => $paymentAmount,
                'currency_id' => 1,
                'current_currency_rate' => 1,
                'currency_amount' => 10,
            ],
        ];
        $mock->saleData = new SaleData(...$saleDetails);
        $mock->saleMismatches = collect([]);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'Specified payment amount of ' . $paymentAmount . ' is short of the expected payment amount of 100'
        );

        $mock->checkPaymentDetails(100);
    }
)->with([[0], [-10], [10], [50], [99.99]]);

test(
    'checkPaymentDetails method sets saleMismatches when subtotal is less than specified payments',
    function (float $paymentAmount): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkPaymentCurrency']);
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => 1,
                'amount' => $paymentAmount,
                'currency_id' => 1,
                'current_currency_rate' => 1,
                'currency_amount' => 10,
            ],
        ];
        $mock->saleData = new SaleData(...$saleDetails);
        $mock->saleMismatches = collect([]);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'Specified payment amount of ' . $paymentAmount . ' is more than the expected payment amount of 100'
        );

        $mock->checkPaymentDetails(100);
    }
)->with([[150], [210], [550.55], [999.99]]);

test(
    'checkPaymentDetails method does not set saleMismatches when sale is layaway',
    function (float $paymentAmount): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkPaymentCurrency']);
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => 1,
                'amount' => $paymentAmount,
                'currency_id' => 1,
                'current_currency_rate' => 1,
                'currency_amount' => 10,
            ],
        ];
        $saleDetails['is_layaway'] = true;
        $saleDetails['layaway_pending_amount'] = 100 - $paymentAmount;
        $mock->saleData = new SaleData(...$saleDetails);
        $mock->saleMismatches = collect([]);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $mock->checkPaymentDetails(100);
        $this->assertTrue($mock->saleMismatches->isEmpty());
    }
)->with([[50], [90], [50.55]]);

test(
    'checkCreditAndLayawaySaleMismatch method does not set saleMismatches when sale is layaway or credit',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => 1,
                'amount' => 10,
            ],
        ];
        $saleDetails['is_layaway'] = true;
        $saleDetails['layaway_pending_amount'] = 50;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $response = $this->checkSaleDetailsService->checkCreditAndLayawaySaleMismatch(
            200,
            $this->checkSaleDetailsService->saleData->layaway_pending_amount,
            150
        );
        expect($response)->toBe(null);
    }
);

test(
    'checkCreditAndLayawaySaleMismatch method set saleMismatches when sale is layaway or credit',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => 1,
                'amount' => 10,
            ],
        ];
        $saleDetails['is_layaway'] = true;
        $saleDetails['layaway_pending_amount'] = 50;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkCreditAndLayawaySaleMismatch(
            200,
            $this->checkSaleDetailsService->saleData->layaway_pending_amount,
            100
        );
        expect($response)->toBe(null);
    }
)->throws(HttpException::class, 'Specified payment amount of 100 is more than the expected payment amount of 150');

test('isMemberAttached method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->isMemberAttached();
    $this->assertTrue(true);
});

test('hasMemberDetails method returns boolean as expected', function (): void {
    $this->saleData->member_id = null;
    $this->saleData->member['mobile_number'] = '123456789';
    $this->saleData->member['first_name'] = 'ABC';
    $this->saleData->member['last_name'] = 'XYZ';
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->hasMemberDetails();
    $this->assertTrue(true);
});

test('isLayawaySale method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->isLayawaySale();
    $this->assertTrue(true);
});

test('isRoundOffValueProvided method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->isRoundOffValueProvided();
    $this->assertTrue(true);
});

test('hasItemPromotion method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasItemPromotion($this->checkSaleDetailsService->cartItems->first());
    $this->assertFalse(false);

    $this->cartItems->first()['promotion_id'] = 1;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasItemPromotion($this->checkSaleDetailsService->cartItems->first());
});

test('hasDreamPrice method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasDreamPrice($this->checkSaleDetailsService->cartItems->first());
    $this->assertFalse(false);

    $this->cartItems->first()['dream_price_id'] = 1;
    $this->cartItems->first()['dream_price_amount'] = 5;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasDreamPrice($this->checkSaleDetailsService->cartItems->first());
    $this->assertTrue(true);
});

test('hasStoreManagerPriceOverride method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasStoreManagerPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertFalse(false);

    $this->cartItems->first()['store_manager_id'] = 1;
    $this->cartItems->first()['store_manager_passcode'] = 1234;
    $this->cartItems->first()['price_override_amount'] = 100;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasStoreManagerPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertTrue(true);
});

test('hasDirectorPriceOverride method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasDirectorPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertFalse(false);

    $this->cartItems->first()['director_id'] = 1;
    $this->cartItems->first()['director_passcode'] = 1234;
    $this->cartItems->first()['price_override_amount'] = 100;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasDirectorPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertTrue(true);
});

test('hasCashierPriceOverride method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasCashierPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertFalse(false);

    $this->cartItems->first()['cashier_id'] = 1;
    $this->cartItems->first()['price_override_amount'] = 100;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasCashierPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertTrue(true);
});

test('hasPriceOverride method returns boolean as expected', function (): void {
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertFalse(false);

    $this->cartItems->first()['cashier_id'] = 1;
    $this->cartItems->first()['price_override_amount'] = 100;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->hasPriceOverride($this->checkSaleDetailsService->cartItems->first());
    $this->assertTrue(true);
});

test('hasCashback method returns boolean as expected', function (): void {
    $this->saleData->cashback_id = 1;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->hasCashback();
    $this->assertFalse(false);

    $this->saleData->cashback_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->hasCashback();
    $this->assertTrue(true);
});

test('checkLayawayDetails method check layaway details and return true when member id is specified', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->checkLayawayDetails();
    $this->assertTrue(true);
});

test(
    'checkLayawayDetails method check layaway details and return true when employee id is specified',
    function (): void {
        $this->saleData->member_id = null;
        $this->saleData->employee_id = 1;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->checkLayawayDetails();
        $this->assertTrue(true);
    }
);

test(
    'checkLayawayDetails method throw exception if sale is layaway and member id is not specified in sale',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleData->is_layaway = true;
        $this->checkSaleDetailsService->saleData->layaway_pending_amount = 1;
        $this->checkSaleDetailsService->saleData->member_id = null;
        $this->checkSaleDetailsService->saleData->employee_id = null;
        $this->checkSaleDetailsService->checkLayawayDetails();
    }
)->throws(HttpException::class, 'Please provide member or employee when a layaway sale.');

test(
    'checkLayawayDetails method throw exception if sale is layaway_pending_amount is 0 or less than 0 in sale',
    function ($layaWayPendingAmount): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleData->is_layaway = true;
        $this->checkSaleDetailsService->saleData->layaway_pending_amount = $layaWayPendingAmount;
        $this->checkSaleDetailsService->saleData->member_id = null;
        $this->checkSaleDetailsService->saleData->employee_id = null;
        $this->checkSaleDetailsService->checkLayawayDetails();
    }
)->throws(HttpException::class, 'Layaway pending amount is not allow 0 or less than 0')->with([0, null]);

test(
    'checkPromoters method throws an exception when promoter is not available in our records',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
        $this->checkSaleDetailsService->companyId = 1;

        $this->mock(PromoterQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllPromotersExist')
                ->once()
                ->andReturn(false);
        });

        $this->checkSaleDetailsService->checkPromoters();
    }
)->throws(HttpException::class, 'Some of the promoters are not available in our records.');

test('checkPromoters method returns the response as expected', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkSaleDetailsService->companyId = 1;

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('doAllPromotersExist')
            ->once()
            ->andReturn(true);
    });

    $response = $this->checkSaleDetailsService->checkPromoters();

    $this->assertNull($response);
});

test(
    'checkBatchNumber method sets the saleMismatches when one of the products has batch off and pass batch number',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $cartItem = $this->cartItems[0];
        $cartItem['batch_details'] = '123456';

        $this->checkSaleDetailsService->checkBatchNumber($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Batch number is not required for the product with name ABC.');

test(
    'checkBatchNumber method throws an exception when one of the products has batch enabled and batch number is not specified',
    function (): void {
        $this->checkSaleDetailsService->batches = $this->batches;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $product = $this->product;
        $product->has_batch = true;

        $cartItem = $this->cartItems[0];

        $this->checkSaleDetailsService->checkBatchNumber($product, $cartItem);
    }
)->throws(HttpException::class, 'Batch Number is required for the product with name ABC.');

test(
    'checkBatchNumber method throws an exception when one of the products has batch enabled and Batch Expiry Date is not specified',
    function (): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['getBoxProductUnits']);

        $mock->expects($this->once())
            ->method('getBoxProductUnits')
            ->will($this->returnValue(1.0));

        $mock->batches = $this->batches;
        $mock->saleMismatches = collect([]);

        $product = $this->product;
        $product->has_batch = true;

        $cartItem = $this->cartItems[0];
        $cartItem['batch_details'][0]['batch_number'] = 'xyz';

        $mock->checkBatchNumber($product, $cartItem);
    }
)->throws(HttpException::class, 'Batch Expiry Date is required for the product with name ABC.');

test(
    'checkBatchNumber method cell addNew method of BatchQueries class',
    function (): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['getBoxProductUnits']);

        $mock->expects($this->once())
            ->method('getBoxProductUnits')
            ->will($this->returnValue(1.0));

        $mock->batches = collect([]);
        $mock->companyId = 1;
        $mock->saleMismatches = collect([]);

        $product = $this->product;
        $product->has_batch = true;

        $cartItem = $this->cartItems[0];
        $cartItem['batch_details'][0]['batch_number'] = 'xyz';
        $cartItem['batch_details'][0]['quantity'] = 10;
        $cartItem['batch_details'][0]['batch_expiry_date'] = now()->format('Y-m-d');
        $batch = Batch::factory()->make([
            'company_id' => 1,
            'product_id' => 1,
            'number' => 'abc123',
            'expiry_date' => '2022-01-01',
        ]);

        $this->mock(BatchQueries::class, function ($mock) use ($batch): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($batch);
        });

        $mock->checkBatchNumber($product, $cartItem);

        expect($mock->batches->first()->toArray())
            ->toHaveKey('company_id', 1)
            ->toHaveKey('product_id', 1)
            ->toHaveKey('number', 'abc123')
            ->toHaveKey('expiry_date', '2022-01-01');
    }
);

test('checkBatchNumber method returns the response as expected', function (): void {
    $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['getBoxProductUnits']);

    $mock->expects($this->once())
        ->method('getBoxProductUnits')
        ->will($this->returnValue(1.0));

    $mock->batches = $this->batches;
    $mock->saleMismatches = collect([]);

    $product = $this->product;
    $product->has_batch = true;

    $cartItem = $this->cartItems[0];
    $cartItem['batch_details'][0]['batch_number'] = '123';

    $response = $mock->checkBatchNumber($product, $cartItem);
    $this->assertNull($response);
});

test('getItemSubtotal method returns the subtotal of the item as expected', function (): void {
    $response = $this->checkSaleDetailsService->getItemSubtotal($this->saleDetails['items'][0]);
    $this->assertTrue(100.00 === $response);
});

test(
    'getItemSubtotal method returns the subtotal of the item as expected when hasProductLoyaltyPoints is true',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 10;
        $response = $this->checkSaleDetailsService->getItemSubtotal($cartItem);
        $this->assertTrue(100.00 === $response);
    }
);

test('hasComplimentaryItem method returns boolean as expected', function (): void {
    $cartItem = $this->cartItems[0];

    $response = $this->checkSaleDetailsService->hasComplimentaryItem($cartItem);
    $this->assertFalse($response);

    $cartItem['complimentary_item_reason_id'] = null;
    $response = $this->checkSaleDetailsService->hasComplimentaryItem($cartItem);
    $this->assertFalse($response);

    $cartItem['complimentary_item_reason_id'] = 0;
    $response = $this->checkSaleDetailsService->hasComplimentaryItem($cartItem);
    $this->assertFalse($response);

    $cartItem['complimentary_item_reason_id'] = 1;
    $response = $this->checkSaleDetailsService->hasComplimentaryItem($cartItem);
    $this->assertTrue($response);
});

test('hasLoyaltyPoints method returns boolean as expected', function (): void {
    $payment = [];
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints([]);
    $this->assertFalse($response);

    $payment['loyalty_points'] = null;
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints($payment);
    $this->assertFalse($response);

    $payment['loyalty_points'] = 0;
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints($payment);
    $this->assertFalse($response);

    $payment['loyalty_points'] = 100;
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints($payment);
    $this->assertTrue($response);
});

test('checkPaymentTypes method returns null if payments is null', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['payments'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);

    $response = $this->checkSaleDetailsService->checkPaymentTypes();

    $this->assertNull($response);
});

test('checkLoyaltyPoints method returns null when there are no payments by loyalty points', function (): void {
    $saleDetails = $this->saleDetails;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $response = $this->checkSaleDetailsService->checkLoyaltyPoints();
    $this->assertNull($response);
});

test(
    'checkUserLoyaltyPoints method throws an exception when member id or employee id not specified',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => '100',
            ],
        ];

        $this->mock(SaleUserService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('getMember')
                ->once()
                ->andReturn(null);
        });

        $this->checkSaleDetailsService->cashier = new Cashier();
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->checkUserLoyaltyPoints();
    }
)->throws(HttpException::class, 'User is compulsory when payment type is loyalty point');

test('checkUserLoyaltyPoints method throws an exception when user does not have an membership.', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['payments'] = [
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => '100',
        ],
    ];

    $this->mock(SaleUserService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
        $mock->shouldReceive('getMember')
            ->once()
            ->andReturn(new Member());
    });

    $this->checkSaleDetailsService->cashier = new Cashier();
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->checkUserLoyaltyPoints();
})->throws(HttpException::class, 'Loyalty points can only be used when membership is assigned to the user.');

test(
    'checkLoyaltyPoints method throws an exception when payment type is loyalty point and Loyalty Points not specified',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => '100',
            ],
        ];

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $mock->cashier = new Cashier();

        $mock->products = collect([$this->product]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue(new Member()));

        $mock->saleData = new SaleData(...$saleDetails);
        $mock->checkLoyaltyPoints();
    }
)->throws(HttpException::class, 'Loyalty Points must be provided when payment type is loyalty point');

test(
    'validateBookingPayment method throws an exception when the specified payment type is not booking payment',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'][0]['type_id'] = StaticPaymentTypes::CREDIT_NOTE->value;
        $saleDetails['payments'][0]['booking_payment_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateBookingPayment($payment);
    }
)->throws(HttpException::class, 'Booking Payment id must be provided when payment type is booking payment.');

test(
    'validateBookingPayment method sets the sale mismatches when booking payment status is not active',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'available_amount' => 100,
            'status' => BookingPaymentStatuses::USED->value,
        ]);

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->times(0)
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->validateBookingPayment($payment);
    }
)->throws(HttpException::class, 'Booking Payment is not active.');

test(
    'validateBookingPayment method throws an exception when other member tries to use booking payment on sale.',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 2;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateBookingPayment($payment);
    }
)->throws(HttpException::class, 'Selected member is not same as the booking payment member');

test(
    'validateBookingPayment method sets the sale mismatch when requested amount to use on sale is more than booking payment available amount.',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'available_amount' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;
        $this->checkSaleDetailsService->companyId = 1;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->validateBookingPayment($payment);
    }
)->throws(HttpException::class, 'Specified payment amount 10 is more than available amount of the booking payment 1');

test(
    'validateBookingPayment method throws an exception when someone tries to use booking payment of another company.',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'available_amount' => 10,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->companyId = 2;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->validateBookingPayment($payment);
    }
)->throws(HttpException::class, 'You cannot use different companies booking payments.');

test(
    'validateCreditNotes method throws exception when specified payment type id is not credit note',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'][0]['type_id'] = StaticPaymentTypes::BOOKING_PAYMENT->value;
        $saleDetails['payments'][0]['credit_note_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;
        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class, 'Credit note id must be provided when payment type is credit note.');

test('validateCreditNotes method sets the sale mismatches when credit note status is not active', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::USED->value,
    ]);

    $payment = [
        [
            'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'credit_note_id' => $creditNote->id,
            'amount' => 10,
        ],
    ];

    $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->times(0)
            ->andReturn(1);
    });

    $this->checkSaleDetailsService->validateCreditNotes($payment);
})->throws(HttpException::class);

test(
    'validateCreditNotes method sets the sale mismatches when credit note is expired but status is active.',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'expiry_date' => now()->subDay()->format('Y-m-d'),
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->times(0)
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class);

test(
    'validateCreditNotes method throws an exception when other member tries to use credit note on sale.',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'available_amount' => 10,
            'status' => CreditNoteStatuses::USED->value,
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 2;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class);

test(
    'checkLoyaltyPoints method sets the saleMismatches when the user does not have loyalty points.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 100,
                'loyalty_points' => 100,
            ],
        ];

        $member = new Member([
            'membership_id' => 1,
            'loyalty_points' => 50,
        ]);

        $member->membership = new Membership([
            'loyalty_points_per_currency_unit' => 4,
        ]);

        $this->mock(SaleUserService::class, function ($mock) use ($member): void {
            $mock->shouldReceive('setDetails')
                ->once();
            $mock->shouldReceive('getMember')
                ->once()
                ->andReturn($member);
        });

        $this->checkSaleDetailsService->cashier = new Cashier();

        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->cartItems = $this->cartItems;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->checkUserLoyaltyPoints();
    }
)->throws(
    HttpException::class,
    'Specified loyalty points are more than the current loyalty points balance of the user.'
);

test(
    'validateCreditNotes method throws an exception when the specified payment type is not credit note',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'status' => CreditNoteStatuses::USED->value,
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CASH->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class);

test(
    'validateCreditNotes method throws an exception when requested amount to use on sale is more than credit note available amount.',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'available_amount' => 1,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'expiry_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->times(0)
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(
    HttpException::class,
    'Specified payment amount exceeds the credit note available amount 1 Requested Payment Amount is 10'
);

test(
    'checkLoyaltyPoints method sets the saleMismatches when the Specified amount does not match with the given loyalty points',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'] = [
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 100,
                'loyalty_points' => 100,
            ],
        ];

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue(new Member()));
        $mock->products = collect([$this->product]);
        $mock->cashier = new Cashier();
        $mock->saleData = new SaleData(...$saleDetails);
        $mock->saleMismatches = collect([]);

        $mock->checkLoyaltyPoints();
    }
)->throws(
    HttpException::class,
    'The specified amount (100) is more than the calculated amount from the loyalty points as per the membership of the user (0)'
);

test('checkLoyaltyPoints method returns the response as expected', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['payments'] = [
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => 100,
            'loyalty_points' => 400,
        ],
    ];

    $member = new Member([
        'membership_id' => 1,
        'loyalty_points' => 500,
    ]);

    $member->membership = new Membership([
        'loyalty_points_per_currency_unit' => 4,
    ]);

    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        ['checkUserLoyaltyPoints', 'checkLoyaltyPointsIsValidOrNot']
    );

    $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

    $mock->expects($this->once())
            ->method('checkLoyaltyPointsIsValidOrNot')
            ->will($this->returnValue(true));

    $mock->products = collect([$this->product]);
    $mock->cashier = new Cashier();
    $mock->saleData = new SaleData(...$saleDetails);
    $mock->saleMismatches = collect([]);

    $response = $mock->checkLoyaltyPoints();
    $this->assertNull($response);

    $this->assertTrue($mock->saleMismatches->toArray() === []);
});

test(
    'validateCreditNotes method throws an exception when someone tries to use credit note of another company.',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'available_amount' => 10,
            'member_id' => 1,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'expiry_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;

        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->times(1)
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->companyId = 2;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class, 'You cannot use different companies credit notes.');

test(
    'validateCreditNotes method throws an exception when member tries to use employee credit note.',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'available_amount' => 10,
            'member_id' => 1,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class);

test(
    'validateCreditNotes method throws an exception when employee tries to use member credit note.',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'available_amount' => 10,
            'member_id' => 1,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->creditNotes = collect([$creditNote]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $saleDetails['employee_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $payment = [
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'credit_note_id' => $creditNote->id,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateCreditNotes($payment);
    }
)->throws(HttpException::class);

test(
    'checkOfflineSaleIdSaleReturn calls the doesOfflineSaleReturnIdExist method of SaleReturnQueries class',
    function (): void {
        $this->checkSaleDetailsService->saleData->offline_sale_id = '1';
        $this->checkSaleDetailsService->companyId = 1;

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleReturnIdExist')
                ->once()
                ->with('1', 1)
                ->andReturn(false);
        });

        $this->checkSaleDetailsService->checkOfflineSaleIdSaleReturn();
    }
);

test(
    'checkOfflineSaleIdSaleReturn method throws an exception when offline_sale_id exist in our records.',
    function (): void {
        $this->checkSaleDetailsService->saleData->offline_sale_id = '1';
        $this->checkSaleDetailsService->companyId = 1;

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleReturnIdExist')
                ->once()
                ->with('1', 1)
                ->andReturn(true);
        });

        $this->checkSaleDetailsService->checkOfflineSaleIdSaleReturn();
    }
)->throws(HttpException::class, 'The offline sale id has already been taken.');

test('checkOfflineSaleIdSale calls the checkOfflineSaleId method of SaleQueries class', function (): void {
    $this->checkSaleDetailsService->saleData->offline_sale_id = '1';
    $this->checkSaleDetailsService->companyId = 1;

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('doesOfflineSaleIdExist')
            ->once()
            ->with('1', 1)
            ->andReturn(false);
    });

    $this->checkSaleDetailsService->checkOfflineSaleIdSale();
});

test(
    'checkOfflineSaleIdSale method throws an exception when offline_sale_id exist in our records.',
    function (): void {
        $this->checkSaleDetailsService->saleData->offline_sale_id = '1';
        $this->checkSaleDetailsService->companyId = 1;

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleIdExist')
                ->once()
                ->with('1', 1)
                ->andReturn(true);
        });

        $this->checkSaleDetailsService->checkOfflineSaleIdSale();
    }
)->throws(HttpException::class, 'The offline sale id has already been taken.');

test('checkOfflineSaleId method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        ['checkOfflineSaleIdSaleReturn', 'hasCartItems', 'checkOfflineSaleIdSale']
    );

    $mock->expects($this->once())
            ->method('checkOfflineSaleIdSaleReturn');

    $mock->expects($this->once())
            ->method('hasCartItems')
            ->will($this->returnValue(true));

    $mock->expects($this->once())
            ->method('checkOfflineSaleIdSale');

    $mock->saleReturnService = $this->mock(SaleReturnService::class, function ($mock): void {
        $mock->shouldReceive('hasReturnItems')
            ->once()
            ->andReturn(true);
    });

    $mock->checkOfflineSaleId();
});

test('checkOfflineSaleId method returns null when Cart is empty', function (): void {
    $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['hasCartItems']);

    $mock->expects($this->once())
            ->method('hasCartItems')
            ->will($this->returnValue(false));

    $mock->saleReturnService = $this->mock(SaleReturnService::class, function ($mock): void {
        $mock->shouldReceive('hasReturnItems')
            ->once()
            ->andReturn(false);
    });

    $response = $mock->checkOfflineSaleId();
    $this->assertNull($response);
});

test(
    'checkItemTotalPricePaid method sets saleMismatches when cartItem does not contain total_price_paid',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->checkItemTotalPricePaid($this->product, $this->saleData->items[0], 0.0);
    }
)->throws(HttpException::class, 'Item total price paid is not specified for ABC product.');

test(
    'checkItemTotalPricePaid method sets saleMismatches when cartItem total_price_paid does not match.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData->items[0]['total_price_paid'] = 1;

        $this->checkSaleDetailsService->checkItemTotalPricePaid($this->product, $this->saleData->items[0], 100);
    }
)->throws(
    HttpException::class,
    'Specified total price paid amount of 1 for ABC product does not match with calculated amount of 100'
);

test(
    'checkItemTotalPricePaid method should not set saleMismatches when cartItem total_price_paid contains valid amount.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData->items[0]['total_price_paid'] = 100;

        $this->checkSaleDetailsService->checkItemTotalPricePaid($this->product, $this->saleData->items[0], 100);

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test(
    'checkItemTotalPricePaid method should not set saleMismatches when cartItem with is_exchange and total_price_paid mismatch.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData->items[0]['total_price_paid'] = 100;
        $this->saleData->items[0]['is_exchange'] = true;

        $this->checkSaleDetailsService->checkItemTotalPricePaid($this->product, $this->saleData->items[0], 100);

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test('checkMemberExists return null when member id not pass', function (): void {
    $this->saleData->member_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkSaleDetailsService->checkMemberExists();
    $this->assertNull($response);
});

test('checkMemberExists return null when member id is available in our records', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->member = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
    ]);

    $response = $this->checkSaleDetailsService->checkMemberExists();
    $this->assertNull($response);
});

test('checkMemberExists method throws an exception when member id not available in our records', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew');
    });

    $this->checkSaleDetailsService->checkMemberExists();
})->throws(HttpException::class, 'The selected member id is invalid.');

test(
    'checkPromotersDetails method should not set saleMismatches when company set min_promoters_per_item zero.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->company->min_promoters_per_item = 0;
        $this->checkSaleDetailsService->company = $this->company;

        $this->checkSaleDetailsService->checkPromotersDetails($this->product, $this->saleData->items[0]);

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test(
    'checkPromotersDetails method sets saleMismatches when company set min_promoters_per_item more than zero but no promoters attached.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->company = $this->company;

        $cartItem = $this->saleData->items[0];
        unset($cartItem['promoter_ids']);

        $this->checkSaleDetailsService->checkPromotersDetails($this->product, $cartItem);
    }
)->throws(HttpException::class, 'Specified product (Named: ABC) does not have any promoter(s) attached.');

test(
    'checkPromotersDetails method sets saleMismatches when company set min_promoters_per_item more than zero.',
    function (): void {
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->company = $this->company;
        $this->company->min_promoters_per_item = 2;

        $cartItem = $this->saleData->items[0];
        $cartItem['promoter_ids'][0] = 1;

        $this->checkSaleDetailsService->checkPromotersDetails($this->product, $cartItem);
    }
)->throws(
    HttpException::class,
    'Specified product (Named: ABC) requires a minimum of 2 promoter(s) but only 1 promoter(s) are attached.'
);

test(
    'validateGiftCards method throws exception when specified payment type id is not gift Card',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['payments'][0]['type_id'] = StaticPaymentTypes::BOOKING_PAYMENT->value;
        $saleDetails['payments'][0]['gift_card_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;
        $payment = [
            [
                'type_id' => StaticPaymentTypes::GIFT_CARD->value,
                'amount' => 10,
            ],
        ];

        $this->checkSaleDetailsService->validateGiftCards($payment);
    }
)->throws(HttpException::class, 'Gift Card id must be provided when payment type is gift card.');

test('validateGiftCards method sets the sale mismatches when gift card status is not active', function (): void {
    $giftCard = GiftCard::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'status' => GiftCardStatuses::USED->value,
    ]);

    $payment = [
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'gift_card_id' => $giftCard->id,
            'amount' => 10,
        ],
    ];

    $this->checkSaleDetailsService->giftCards = collect([$giftCard]);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

    $this->checkSaleDetailsService->validateGiftCards($payment);
})->throws(HttpException::class);

test(
    'validateGiftCards method sets the sale mismatches when gift card is expired but status is active.',
    function (): void {
        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'expiry_date' => now()->subDay()->format('Y-m-d'),
            'status' => GiftCardStatuses::ACTIVE->value,
        ]);

        $payment = [
            [
                'type_id' => StaticPaymentTypes::GIFT_CARD->value,
                'gift_card_id' => $giftCard->id,
                'amount' => 10,
            ],
        ];

        $this->saleData->happened_at = now()->addDay()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->giftCards = collect([$giftCard]);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->checkSaleDetailsService->validateGiftCards($payment);
    }
)->throws(HttpException::class);

test(
    'checkBillReferenceNumberDetails sets saleMismatches when company set is_bill_reference_number_mandatory but bill_reference_number does not specified',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->company->is_bill_reference_number_mandatory = true;
        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->checkBillReferenceNumberDetails();
    }
)->throws(HttpException::class, 'Bill reference number is required while new sale.');

test('hasStoreManagerPriceOverrideForCart method returns boolean as expected', function (): void {
    $response = $this->checkSaleDetailsService->hasStoreManagerPriceOverrideForCart();
    $this->assertFalse($response);

    $this->saleDetails['store_manager_id'] = 1;
    $this->saleDetails['store_manager_passcode'] = '111';
    $this->saleDetails['cart_price_override_amount'] = 1;

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $response = $this->checkSaleDetailsService->hasStoreManagerPriceOverrideForCart();
    $this->assertTrue($response);
});

test('hasDirectorPriceOverrideForCart method returns boolean as expected', function (): void {
    $response = $this->checkSaleDetailsService->hasDirectorPriceOverrideForCart();
    $this->assertFalse($response);

    $this->saleDetails['director_id'] = 1;
    $this->saleDetails['director_passcode'] = '1';
    $this->saleDetails['cart_price_override_amount'] = 1;

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $response = $this->checkSaleDetailsService->hasDirectorPriceOverrideForCart();
    $this->assertTrue($response);
});

test('hasCashierPriceOverrideForCart method returns boolean as expected', function (): void {
    $response = $this->checkSaleDetailsService->hasCashierPriceOverrideForCart();
    $this->assertFalse($response);

    $this->saleDetails['cashier_id'] = 1;
    $this->saleDetails['cart_price_override_amount'] = 1;

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $response = $this->checkSaleDetailsService->hasCashierPriceOverrideForCart();
    $this->assertTrue($response);
});

test('hasPriceOverrideForCart method returns false when Price Override not set', function (): void {
    $response = $this->checkSaleDetailsService->hasPriceOverrideForCart();
    $this->assertFalse($response);
});

test(
    'hasPriceOverrideForCart method call hasStoreManagerPriceOverrideForCart and returns boolean as expected',
    function (): void {
        $mock = $this->createPartialMock(
            CheckSaleDetailsService::class,
            ['hasStoreManagerPriceOverrideForCart', 'hasDirectorPriceOverrideForCart', 'hasCashierPriceOverrideForCart']
        );

        $this->saleDetails['cart_price_override_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $mock->saleData = $this->saleData;

        $mock->expects($this->once())
            ->method('hasStoreManagerPriceOverrideForCart')
            ->will($this->returnValue(true));

        $mock->expects($this->exactly(0))
            ->method('hasDirectorPriceOverrideForCart')
            ->will($this->returnValue(true));

        $mock->expects($this->exactly(0))
            ->method('hasCashierPriceOverrideForCart')
            ->will($this->returnValue(true));

        $response = $mock->hasPriceOverrideForCart();
        $this->assertTrue($response);
    }
);

test(
    'hasPriceOverrideForCart method call hasDirectorPriceOverrideForCart and returns boolean as expected',
    function (): void {
        $mock = $this->createPartialMock(
            CheckSaleDetailsService::class,
            ['hasStoreManagerPriceOverrideForCart', 'hasDirectorPriceOverrideForCart', 'hasCashierPriceOverrideForCart']
        );

        $this->saleDetails['cart_price_override_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $mock->saleData = $this->saleData;

        $mock->expects($this->once())
            ->method('hasStoreManagerPriceOverrideForCart')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('hasDirectorPriceOverrideForCart')
            ->will($this->returnValue(true));

        $mock->expects($this->exactly(0))
            ->method('hasCashierPriceOverrideForCart')
            ->will($this->returnValue(true));

        $response = $mock->hasPriceOverrideForCart();
        $this->assertTrue($response);
    }
);

test(
    'hasPriceOverrideForCart method call hasCashierPriceOverrideForCart and returns boolean as expected',
    function (): void {
        $mock = $this->createPartialMock(
            CheckSaleDetailsService::class,
            ['hasStoreManagerPriceOverrideForCart', 'hasDirectorPriceOverrideForCart', 'hasCashierPriceOverrideForCart']
        );

        $this->saleDetails['cart_price_override_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $mock->saleData = $this->saleData;

        $mock->expects($this->once())
            ->method('hasStoreManagerPriceOverrideForCart')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('hasDirectorPriceOverrideForCart')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('hasCashierPriceOverrideForCart')
            ->will($this->returnValue(true));

        $response = $mock->hasPriceOverrideForCart();
        $this->assertTrue($response);
    }
);

test(
    'hasPriceOverrideForCart method call return false when cart price override amount is 0',
    function (): void {
        $this->saleDetails['cart_price_override_amount'] = 0;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $response = $this->checkSaleDetailsService->hasPriceOverrideForCart();
        $this->assertFalse($response);
    }
);

test('checkEmployeeExists return null when employee id not pass', function (): void {
    $this->saleData->employee_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkSaleDetailsService->checkEmployeeExists();
    $this->assertNull($response);
});

test('checkEmployeeExists return null when employee id is available in our records', function (): void {
    $this->saleData->employee_id = 1;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $this->checkSaleDetailsService->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $response = $this->checkSaleDetailsService->checkEmployeeExists();
    $this->assertNull($response);
});

test('checkEmployeeExists method throws an exception when employee id not available in our records', function (): void {
    $this->saleData->employee_id = 1;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->employee = null;

    $this->checkSaleDetailsService->checkEmployeeExists();
})->throws(HttpException::class, 'The selected employee id is invalid.');

test(
    'checkRequestDetails method calls GenerateLoyaltyPointsService class checkLoyaltyPoints method and return mismatch collection',
    function (): void {
        $mock = $this->createPartialMock(
            CheckSaleDetailsService::class,
            [
                'hasCartItems',
                'checkRecordsExists',
                'checkCartItem',
                'checkPaymentDetails',
                'getSaleRoundOffAmount',
                'checkOfflineSaleId',
                'checkBillReferenceNumberDetails',
                'checkMemberExists',
                'hasVoucher',
                'getCartSubtotalAfterDiscount',
                'getTotalTaxAmount',
                'checkProductPriceWithType',
                'hasGenerateLoyaltyPoints',
                'checkItemTotalPricePaid',
            ]
        );

        $mock->cartItems = collect($this->cartItems);
        $mock->products = collect([$this->product]);

        $this->saleData->voucher_number;
        $mock->saleData = $this->saleData;
        $mock->saleMismatches = collect([]);
        $mock->location = $this->location;
        $mock->companyId = 1;
        $mock->company = $this->company;
        $totalCartDiscountAmount = 10.10;
        $total = $this->saleDetails['payments'][0]['amount'];
        $afterDiscountEffectTotal = $total - $totalCartDiscountAmount;
        $saleReturnService = new SaleReturnService();

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'total_tax_amount' => 2.50,
            'price_paid_per_unit' => 10.50,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $saleReturnService->returnedSaleItems = collect([$saleItem]);

        $mock->generateVoucherService = $this->mock(GenerateVoucherService::class, function ($mock): void {
            $mock->shouldReceive('checkVouchers')
                ->once();
        });

        $mock->expects($this->once())
            ->method('hasCartItems')
            ->will($this->returnValue(true));

        $mock->expects($this->once())
            ->method('hasVoucher')
            ->will($this->returnValue(true));

        $mock->expects($this->once())
            ->method('checkRecordsExists');

        $mock->expects($this->once())
            ->method('checkCartItem');

        $mock->expects($this->once())
            ->method('getSaleRoundOffAmount')
            ->will($this->returnValue(0))
            ->with(100.0);

        $mock->expects($this->once())
            ->method('checkPaymentDetails')
            ->with(89.50);

        $mock->expects($this->once())
            ->method('checkItemTotalPricePaid');

        $mock->expects($this->once())
            ->method('checkOfflineSaleId');

        $mock->expects($this->once())
            ->method('checkOfflineSaleId');

        $mock->expects($this->once())
            ->method('checkProductPriceWithType');

        $mock->expects($this->once())
            ->method('hasGenerateLoyaltyPoints')
            ->will($this->returnValue(true));

        $mockSaleDiscountService = $this->mock(SaleDiscountService::class, function ($mock) use (
            $totalCartDiscountAmount
        ): void {
            $mock->shouldReceive('checkCartWidePromotionDetails')
                ->once();
            $mock->shouldReceive('getCartDiscountAmountFor')
                ->once()
                ->andReturn([
                    'total_discount' => $totalCartDiscountAmount,
                    'voucher_discount' => 0,
                    'cart_wide_discount' => 0,
                ]);
            $mock->shouldReceive('getItemDiscountAmountFor')
                ->once()
                ->andReturn([
                    'total_discount' => 0,
                ]);
            $mock->shouldReceive('getTotalItemDiscountAmount')
                ->once()
                ->andReturn(0.00);
            $mock->shouldReceive('getItemCartDiscountAmount')
                ->once();
            $mock->shouldReceive('checkVoucherDetails')
                ->once();
        });

        $mock->saleTaxService = $this->mock(SaleTaxService::class, function ($mock) use (
            $afterDiscountEffectTotal
        ): void {
            $mock->shouldReceive('getTotalTaxAmountFor')
                ->once()
                ->with($afterDiscountEffectTotal)
                ->andReturn(10.10);
            $mock->shouldReceive('getItemTaxAmountFor')
                ->once()
                ->andReturn(10.10);
            $mock->shouldReceive('checkTaxDetails')
                ->once()
                ->with(10.10);
        });

        $mock->saleReturnService = $this->mock(SaleReturnService::class, function ($mock) use ($saleItem): void {
            $mock->shouldReceive('hasReturnItems')
                ->times(2)
                ->andReturn(true);
            $mock->shouldReceive('checkReturnItems')
                ->once();
            $mock->shouldReceive('getReturnItemsSubtotal')
                ->once()
                ->andReturn($saleItem->price_paid_per_unit);
        });

        $mock->generateLoyaltyPointsService = $this->mock(
            GenerateLoyaltyPointsService::class,
            function ($mock): void {
                $mock->shouldReceive('checkLoyaltyPoints')
                    ->times(1)
                    ->andReturn(collect(['Generate Loyalty Points Service mismatch.']));
            }
        );

        $mock->saleDiscountService = $mockSaleDiscountService;

        $this->assertTrue($mock->saleMismatches->isEmpty());
        $mock->checkRequestDetails(true);
        $this->assertTrue($mock->saleMismatches->contains('Generate Loyalty Points Service mismatch.'));
    }
);

test('hasGenerateLoyaltyPoints method returns boolean as expected', function (): void {
    $cartData = $this->saleDetails;
    $cartData['loyalty_points'] = [
        'loyalty_campaign_id' => 1,
        'minimum_spend_amount' => 10,
        'points' => 10,
        'expired_at' => '2022-01-01',
    ];

    $this->checkSaleDetailsService->saleData = new SaleData(...$cartData);
    $response = $this->checkSaleDetailsService->hasGenerateLoyaltyPoints();
    $this->assertTrue($response);

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $response = $this->checkSaleDetailsService->hasGenerateLoyaltyPoints();
    $this->assertFalse($response);
});

test('getPaymentAmount method returns as expected', function (): void {
    $response = $this->checkSaleDetailsService->getPaymentAmount();
    $this->assertEquals($response, 100);
});

test(
    'checkNegativeInventory return null when allow_negative_inventory not set',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'allow_negative_inventory' => true,
            'default_country_id' => 1,
        ]);

        $response = $this->checkSaleDetailsService->checkNegativeInventory($this->product, $this->saleData->items[0]);

        $this->assertNull($response);

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test(
    'checkNegativeInventory return null when stock not Negative',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'allow_negative_inventory' => false,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->inventories = collect([
            Inventory::factory()->make([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 10,
            ]),
        ]);

        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);

        $response = $this->checkSaleDetailsService->checkNegativeInventory($this->product, $this->saleData->items[0]);

        $this->assertNull($response);

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test(
    'checkNegativeInventory set  sale Mismatch when stock is Negative',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'allow_negative_inventory' => false,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->inventories = collect([
            Inventory::factory()->make([
                'product_id' => 1,
                'location_id' => 1,
                'stock' => 5,
            ]),
        ]);

        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);

        $response = $this->checkSaleDetailsService->checkNegativeInventory($this->product, $this->saleData->items[0]);

        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Specified product (Named: ABC) does not have sufficient quantity available at the moment.'
);

test('getMember return null when member id not pass', function (): void {
    $this->saleData->member_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;

    $this->checkSaleDetailsService->saleUserService = $this->mock(
        SaleUserService::class,
        function ($mock): void {
            $mock->shouldReceive('getMemberId')
                ->once()
                ->andReturn(null);
        }
    );

    $response = $this->checkSaleDetailsService->getMember();
    $this->assertNull($response);
});

test('getMember return null when member id not available in our records', function (): void {
    $this->saleData->member_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;

    $this->checkSaleDetailsService->saleUserService = $this->mock(
        SaleUserService::class,
        function ($mock): void {
            $mock->shouldReceive('getMemberId')
                ->once()
                ->andReturn(1);
        }
    );

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('memberExistsById')
            ->once()
            ->andReturn(null);
    });

    $response = $this->checkSaleDetailsService->getMember();
    $this->assertNull($response);
});

test('getMember return member when member id available in our records', function (): void {
    $this->saleData->member_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;

    $this->checkSaleDetailsService->saleUserService = $this->mock(
        SaleUserService::class,
        function ($mock): void {
            $mock->shouldReceive('getMemberId')
                ->once()
                ->andReturn(1);
        }
    );

    $member = Member::factory()->make([
        'company_id' => $this->company->id,
        'created_location_id' => $this->location->id,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('memberExistsById')
            ->once()
            ->andReturn($member);
    });

    $response = $this->checkSaleDetailsService->getMember();

    expect($response->toArray())
        ->toHaveKey('company_id', $this->company->id)
        ->toHaveKey('created_location_id', $this->location->id);
});

test(
    'checkEmployeePurchaseLimit method return null if employee have employee group purchase limit set zero.',
    function (): void {
        $employeeGroup = EmployeeGroup::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'item_purchase_limit' => 0,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'designation_id' => 1,
            'group_id' => $employeeGroup->id,
        ]);

        $this->saleData->employee_id = $employee->id;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $response = $this->checkSaleDetailsService->checkEmployeePurchaseLimit();
        expect($response)->toBeNull();
    }
);

test(
    'checkEmployeePurchaseLimit sets the saleMismatches when purchase limit by items with various reset limit mismatch',
    function ($limitResetType, $limitReset): void {
        $employeeGroup = EmployeeGroup::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'purchase_limit_type_id' => PurchaseLimitTypes::BY_ITEMS->value,
            'item_purchase_limit' => 5,
            'limit_reset_type_id' => $limitResetType,
            'limit_reset' => $limitReset,
        ]);

        $employeePurchaseLimit = $employeeGroup->item_purchase_limit;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'designation_id' => 1,
            'group_id' => $employeeGroup->id,
        ]);

        $employee->employeeGroup = $employeeGroup;
        $saleQuantity = 2;
        $saleReturnQuantity = 1;

        $this->mock(SaleItemQueries::class, function ($mock) use ($saleQuantity): void {
            $mock->shouldReceive('getTotalQuantitiesBy')
                ->once()
                ->andReturn($saleQuantity);
        });

        $this->mock(SaleReturnItemQueries::class, function ($mock) use ($saleReturnQuantity): void {
            $mock->shouldReceive('getTotalQuantitiesBy')
                ->once()
                ->andReturn($saleReturnQuantity);
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData->employee_id = $employee->id;
        $this->checkSaleDetailsService->employee = $employee;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
        $this->checkSaleDetailsService->checkEmployeePurchaseLimit();

        $totalBuyingQuantities = ($saleQuantity - $saleReturnQuantity) + $this->checkSaleDetailsService->cartItems->sum(
            'quantity'
        );
    }
)->with([
    [LimitResetTypes::BY_WEEK->value, LimitResetDays::WEDNESDAY->value],
    [LimitResetTypes::BY_MONTH->value, 3],
    [LimitResetTypes::BY_DAYS->value, 3],
])->throws(HttpException::class);

test(
    'checkEmployeePurchaseLimit sets the saleMismatches when purchase limit by amounts with various reset limit mismatch',
    function ($limitResetType, $limitReset): void {
        $employeeGroup = EmployeeGroup::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'purchase_limit_type_id' => PurchaseLimitTypes::BY_AMOUNT->value,
            'item_purchase_limit' => 5,
            'limit_reset_type_id' => $limitResetType,
            'limit_reset' => $limitReset,
        ]);

        $employeePurchaseLimit = $employeeGroup->item_purchase_limit;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'designation_id' => 1,
            'group_id' => $employeeGroup->id,
        ]);

        $employee->employeeGroup = $employeeGroup;
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => $employee->id,
            'counter_update_id' => 1,
            'total_amount_paid' => 100,
            'happened_at' => Carbon::now(),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'member_id' => $employee->id,
            'counter_update_id' => 1,
            'total_price_paid' => 5,
            'happened_at' => Carbon::now(),
        ]);

        $sales = collect([$sale]);
        $saleReturns = collect([$saleReturn]);

        $this->mock(SaleQueries::class, function ($mock) use ($sales): void {
            $mock->shouldReceive('getSalesByEmployeeWithDateRange')
                ->once()
                ->andReturn($sales);
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturns): void {
            $mock->shouldReceive('getSaleReturnsByEmployeeWithDateRange')
                ->once()
                ->andReturn($saleReturns);
        });

        $totalPayments = $this->saleDetails['payments'][0]['amount'];
        $finalPurchaseAmounts = ($totalPayments + $sales->sum('total_amount_paid')) - $saleReturns->sum(
            'total_price_paid'
        );
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData->employee_id = $employee->id;
        $this->checkSaleDetailsService->employee = $employee;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
        $this->checkSaleDetailsService->checkEmployeePurchaseLimit();
    }
)->with([
    [LimitResetTypes::BY_WEEK->value, LimitResetDays::WEDNESDAY->value],
    [LimitResetTypes::BY_MONTH->value, 3],
    [LimitResetTypes::BY_DAYS->value, 3],
])->throws(HttpException::class);

test(
    'checkEmployeePurchaseLimit sets the saleMismatches when purchase limit by sale with various reset limit mismatch',
    function ($limitResetType, $limitReset): void {
        $employeeGroup = EmployeeGroup::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'purchase_limit_type_id' => PurchaseLimitTypes::BY_SALE->value,
            'item_purchase_limit' => 1,
            'limit_reset_type_id' => $limitResetType,
            'limit_reset' => $limitReset,
        ]);

        $employeePurchaseLimit = $employeeGroup->item_purchase_limit;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'designation_id' => 1,
            'group_id' => $employeeGroup->id,
        ]);

        $employee->employeeGroup = $employeeGroup;
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => $employee->id,
            'counter_update_id' => 1,
            'total_amount_paid' => 100,
            'happened_at' => Carbon::now(),
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $sales = collect([$sale]);

        $this->mock(SaleQueries::class, function ($mock) use ($sales): void {
            $mock->shouldReceive('getSalesByEmployeeWithDateRange')
                ->once()
                ->andReturn($sales);
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('getSaleReturnsByEmployeeWithDateRange')
                ->once()
                ->andReturn(collect([]));
        });

        $finalSalesCount = $sales->count() + 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData->employee_id = $employee->id;
        $this->checkSaleDetailsService->employee = $employee;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->cartItems = collect($this->cartItems);
        $this->checkSaleDetailsService->checkEmployeePurchaseLimit();
    }
)->with([
    [LimitResetTypes::BY_WEEK->value, LimitResetDays::WEDNESDAY->value],
    [LimitResetTypes::BY_MONTH->value, 3],
    [LimitResetTypes::BY_DAYS->value, 3],
])->throws(HttpException::class);

test(
    'checkLayawayAuthorizer method set mismatches when store manager id & passcode specified blank',
    function (): void {
        $this->saleData->is_layaway = true;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->company->companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();

            $mock->shouldReceive('checkLayawaySaleSettings')
                ->once();
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->checkLayawayAuthorizer();
    }
)->throws(HttpException::class, 'Store Manager id & passcode is required to authorized layaway sale');

test(
    'checkLayawayAuthorizer method set mismatches when store manager not found in database.',
    function (): void {
        $this->saleData->is_layaway = true;
        $this->saleData->layaway_store_manager_id = 12;
        $this->saleData->layaway_store_manager_passcode = '1234';
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->company->companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();

            $mock->shouldReceive('checkLayawaySaleSettings')
                ->once();
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn(null);
        });

        $this->checkSaleDetailsService->checkLayawayAuthorizer();
    }
)->throws(HttpException::class, 'Specified Store Manager does not correspond with our records.');

it('checkLayawayAuthorizer method set mismatches when employee as store manager is inactive', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'first_name' => 'abc',
        'last_name' => 'def',
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => false,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkLayawayStoreManagerAuthorizationCode']);

    $mock->expects($this->once())
        ->method('checkLayawayStoreManagerAuthorizationCode');

    $this->saleData->is_layaway = true;
    $this->saleData->layaway_store_manager_id = $storeManager->id;
    $this->saleData->layaway_store_manager_passcode = $storeManager->passcode;
    $mock->saleData = $this->saleData;
    $mock->companyId = $this->companyId;
    $mock->saleMismatches = collect([]);
    $mock->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $mock->company->companySetting = CompanySetting::factory()->make([
        'company_id' => 1,
    ]);

    $this->mock(CheckCompanySettingService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkLayawaySaleSettings')
            ->once();
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $mock->checkLayawayAuthorizer();
})->throws(HttpException::class, 'Specified Store Manager : abc def account is inactive. Please contact admin.');

it('checkLayawayAuthorizer method set mismatches when store manager passcode mismatch', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => false,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $this->saleData->is_layaway = true;
    $this->saleData->layaway_store_manager_id = $storeManager->id;
    $this->saleData->layaway_store_manager_passcode = '12345';
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $this->checkSaleDetailsService->company->companySetting = CompanySetting::factory()->make([
        'company_id' => 1,
    ]);

    $this->mock(CheckCompanySettingService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkLayawaySaleSettings')
            ->once();
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $this->checkSaleDetailsService->checkLayawayAuthorizer();
})->throws(HttpException::class);

it(
    'getCartSubtotalByDiscountApplicableType method return total when Discount Applicable Types is Additional Discount On Already Discounted Prices',
    function (): void {
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $response = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType(100.20);

        $this->assertTrue(100.20 === $response);
    }
);

it(
    'getCartSubtotalByDiscountApplicableType method return total when Discount Applicable Types is Discount Applied To The Original Price',
    function (): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['getCartSubtotal']);

        $mock->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE->value,
            'default_country_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('getCartSubtotal')
            ->will($this->returnValue(50));

        $response = $mock->getCartSubtotalByDiscountApplicableType(100.20);

        $this->assertTrue(50.00 === $response);
    }
);

it(
    'getItemSubtotalByDiscountApplicableType method return total when Discount Applicable Types is Additional Discount On Already Discounted Prices',
    function (): void {
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $response = $this->checkSaleDetailsService->getItemSubtotalByDiscountApplicableType(100.20, []);

        $this->assertTrue(100.20 === $response);
    }
);

it(
    'getItemSubtotalByDiscountApplicableType method return total when Discount Applicable Types is Discount Applied To The Original Price',
    function (): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['getItemSubtotal']);

        $mock->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE->value,
            'default_country_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('getItemSubtotal')
            ->will($this->returnValue(50));

        $response = $mock->getItemSubtotalByDiscountApplicableType(100.20, []);

        $this->assertTrue(50.00 === $response);
    }
);

test(
    'validateBookingPayment method return null when booking payment use types is partially.',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'available_amount' => 10,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'booking_payment_use_type' => BookingPaymentUseTypes::PARTIALLY->value,
            'default_country_id' => 1,
        ]);

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->validateBookingPayment($payment);
        $this->assertNull($response);
    }
);

test(
    'validateBookingPayment method return null when booking payment use types is full and payment is also full.',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'available_amount' => 10,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'booking_payment_use_type' => BookingPaymentUseTypes::FULLY->value,
            'default_country_id' => 1,
        ]);

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->validateBookingPayment($payment);
        $this->assertNull($response);
    }
);

test(
    'validateBookingPayment method throws an exception when booking payment use types is full and payment is not full.',
    function (): void {
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'available_amount' => 20,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->checkSaleDetailsService->bookingPayments = collect([$bookingPayment]);
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleUserService = $this->saleUserService;

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'booking_payment_use_type' => BookingPaymentUseTypes::FULLY->value,
            'default_country_id' => 1,
        ]);

        $payment = [
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'booking_payment_id' => $bookingPayment->id,
                'amount' => 10,
            ],
        ];

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->validateBookingPayment($payment);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'You cannot use booking payment partially. kindly use full booking payment. Specified payment amount is 10 and available booking payment amount is 20'
);

test('checkProductLoyaltyPoints method returns null when hasProductLoyaltyPoints is true', function (): void {
    $cartItem = $this->saleDetails['items'][0];
    $cartItem['loyalty_points'] = 0;
    $response = $this->checkSaleDetailsService->checkProductLoyaltyPoints($this->product, $cartItem);
    $this->assertNull($response);
});

test(
    'checkProductLoyaltyPoints method throws an exception when product tiers null',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);
        $this->product->tiers = collect([]);
        $response = $mock->checkProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The specified product cannot be purchased using loyalty points.');

test(
    'checkProductLoyaltyPoints method throws an exception when product membership tiers not in product tiers',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));
        $mock->saleMismatches = collect([]);
        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make([
            'product_id' => 1,
            'membership_id' => 2,
            'points' => 100,
        ]);
        $this->product->tiers = collect([$productLoyaltyPoint]);
        $response = $mock->checkProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The specified product cannot be purchased using loyalty points.');

test(
    'checkProductLoyaltyPoints method throws an exception when product loyalty points and attached product price is not same',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);
        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make([
            'product_id' => 1,
            'membership_id' => 1,
            'points' => 10,
        ]);
        $this->product->tiers = collect([$productLoyaltyPoint]);

        $response = $mock->checkProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Product loyalty points mismatched. Actual Product loyalty points is 100 And Given product loyalty points is 11'
);

test(
    'checkProductLoyaltyPoints method returns null when product loyalty points and attached product price is same',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 100;
        $cartItem['price'] = null;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);

        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make([
            'product_id' => 1,
            'membership_id' => 1,
            'points' => 10,
        ]);
        $this->product->tiers = collect([$productLoyaltyPoint]);

        $response = $mock->checkProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
);

test('checkProductLoyaltyPoints method returns null when product type is bundle', function (): void {
    $cartItem = $this->saleDetails['items'][0];
    $cartItem['loyalty_points'] = 0;
    $cartItem['box_product_id'] = 1;

    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        [
            'hasProductLoyaltyPoints',
            'isBoxProductWithBoxProductIdAttached',
            'checkBoxProductLoyaltyPoints',
            'checkRegularProductLoyaltyPoints',
        ]
    );

    $mock->expects($this->once())
            ->method('hasProductLoyaltyPoints')
            ->will($this->returnValue(true));

    $mock->expects($this->once())
            ->method('isBoxProductWithBoxProductIdAttached')
            ->will($this->returnValue(true));

    $mock->expects($this->once())
            ->method('checkBoxProductLoyaltyPoints');

    $mock->expects($this->never())
            ->method('checkRegularProductLoyaltyPoints');

    $response = $mock->checkProductLoyaltyPoints($this->product, $cartItem);
    $this->assertNull($response);
});

test('checkProductLoyaltyPoints method returns null when product type is not bundle', function (): void {
    $cartItem = $this->saleDetails['items'][0];
    $cartItem['loyalty_points'] = 0;
    $cartItem['box_product_id'] = 1;

    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        [
            'hasProductLoyaltyPoints',
            'isBoxProductWithBoxProductIdAttached',
            'checkBoxProductLoyaltyPoints',
            'checkRegularProductLoyaltyPoints',
        ]
    );

    $mock->expects($this->once())
            ->method('hasProductLoyaltyPoints')
            ->will($this->returnValue(true));

    $mock->expects($this->once())
            ->method('isBoxProductWithBoxProductIdAttached')
            ->will($this->returnValue(false));

    $mock->expects($this->never())
            ->method('checkBoxProductLoyaltyPoints');

    $mock->expects($this->once())
            ->method('checkRegularProductLoyaltyPoints');

    $response = $mock->checkProductLoyaltyPoints($this->product, $cartItem);
    $this->assertNull($response);
});

test('hasProductLoyaltyPoints method returns boolean as expected', function (): void {
    $cartItem = [];
    $response = $this->checkSaleDetailsService->hasProductLoyaltyPoints($cartItem);
    $this->assertFalse($response);

    $cartItem['loyalty_points'] = null;
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints($cartItem);
    $this->assertFalse($response);

    $cartItem['loyalty_points'] = 0;
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints($cartItem);
    $this->assertFalse($response);

    $cartItem['loyalty_points'] = 100;
    $response = $this->checkSaleDetailsService->hasLoyaltyPoints($cartItem);
    $this->assertTrue($response);
});

test(
    'checkCreditAuthorizer method return null when is_credit_sale is false',
    function (): void {
        $this->saleData->is_credit_sale = false;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $response = $this->checkSaleDetailsService->checkCreditAuthorizer();
        $this->assertNull($response);
    }
);

test(
    'checkCreditAuthorizer method set mismatches when store manager id & passcode specified blank',
    function (): void {
        $this->saleData->is_credit_sale = true;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->company->companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();

            $mock->shouldReceive('checkCreditSaleSettings')
                ->once();
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->checkCreditAuthorizer();
    }
)->throws(HttpException::class, 'Store Manager id & passcode is required to authorized credit sale');

test(
    'checkCreditAuthorizer method set mismatches when store manager not found in database.',
    function (): void {
        $this->saleData->is_credit_sale = true;
        $this->saleData->credit_store_manager_id = 12;
        $this->saleData->credit_store_manager_passcode = '1234';
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->company->companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();

            $mock->shouldReceive('checkCreditSaleSettings')
                ->once();
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn(null);
        });

        $this->checkSaleDetailsService->checkCreditAuthorizer();
    }
)->throws(HttpException::class, 'Specified Store Manager does not correspond with our records.');

it('checkCreditAuthorizer method set mismatches when employee as store manager is inactive', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => false,
        'first_name' => 'test',
        'last_name' => 'test',
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkCreditStoreManagerAuthorizationCode']);

    $mock->expects($this->once())
        ->method('checkCreditStoreManagerAuthorizationCode');

    $this->saleData->is_credit_sale = true;
    $this->saleData->credit_store_manager_id = $storeManager->id;
    $this->saleData->credit_store_manager_passcode = $storeManager->passcode;
    $mock->saleData = $this->saleData;
    $mock->companyId = $this->companyId;
    $mock->saleMismatches = collect([]);

    $mock->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $mock->company->companySetting = CompanySetting::factory()->make([
        'company_id' => 1,
    ]);

    $this->mock(CheckCompanySettingService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkCreditSaleSettings')
            ->once();
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $mock->checkCreditAuthorizer();
})->throws(HttpException::class, 'Specified Store Manager : test test account is inactive. Please contact admin.');

it('checkCreditAuthorizer method set mismatches when store manager passcode mismatch', function (): void {
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
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkCreditStoreManagerAuthorizationCode']);

    $mock->expects($this->once())
        ->method('checkCreditStoreManagerAuthorizationCode');

    $this->saleData->is_credit_sale = true;
    $this->saleData->credit_store_manager_id = $storeManager->id;
    $this->saleData->credit_store_manager_passcode = '12345';
    $mock->saleData = $this->saleData;
    $mock->companyId = $this->companyId;
    $mock->saleMismatches = collect([]);

    $mock->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $mock->company->companySetting = CompanySetting::factory()->make([
        'company_id' => 1,
    ]);

    $this->mock(CheckCompanySettingService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();

        $mock->shouldReceive('checkCreditSaleSettings')
            ->once();
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $mock->checkCreditAuthorizer();
})->throws(
    HttpException::class,
    'The Store Manager provided passcode for authorization does not correspond with our records.'
);

test(
    'checkCreditAuthorizer method return null when store manager passcode not set',
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
            'passcode' => null,
        ]);

        $storeManager->employee = $employee;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkCreditStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkCreditStoreManagerAuthorizationCode');

        $this->saleData->is_credit_sale = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $mock->saleData = $this->saleData;
        $mock->companyId = $this->companyId;
        $mock->saleMismatches = collect([]);

        $mock->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $mock->company->companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();

            $mock->shouldReceive('checkCreditSaleSettings')
                ->once();
        });

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($storeManager);
        });

        $response = $mock->checkCreditAuthorizer();
        $this->assertNull($response);
    }
);

test(
    'checkCreditAuthorizer method return null when store manager passcode and credit_store_manager_passcode is same',
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

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkCreditStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkCreditStoreManagerAuthorizationCode');

        $this->saleData->is_credit_sale = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $mock->saleData = $this->saleData;
        $mock->companyId = $this->companyId;
        $mock->saleMismatches = collect([]);

        $mock->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $mock->company->companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
        ]);

        $this->mock(CheckCompanySettingService::class, function ($mock): void {
            $mock->shouldReceive('setDetails')
                ->once();

            $mock->shouldReceive('checkCreditSaleSettings')
                ->once();
        });

        $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($storeManager);
        });

        $response = $mock->checkCreditAuthorizer();
        $this->assertNull($response);
    }
);

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

        $this->saleData->is_layaway = false;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkLayawayAmounts(100);
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

        $this->saleData->is_layaway = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->saleData->layaway_pending_amount = null;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkLayawayAmounts(100);
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

        $this->saleData->is_layaway = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->saleData->layaway_pending_amount = 100;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkLayawayAmounts(100);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Specified layaway pending amount does not match with calculated layaway pending amount.\nExpected: 0\nSpecified: 100'
);

test(
    'checkCreditAmounts method return null when is_layaway not set',
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

        $this->saleData->is_credit_sale = false;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkCreditAmounts(100);
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

        $this->company->allow_credit_sale = true;
        $this->company->allow_employee_credit_sale = true;

        $storeManager->employee = $employee;

        $this->saleData->is_credit_sale = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->saleData->credit_pending_amount = null;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkCreditAmounts(100);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Credit pending amount is not specified.');

test(
    'checkCreditAmounts method throws exception when company not allow credit',
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

        $this->company->allow_credit_sale = false;
        $this->company->allow_employee_credit_sale = false;

        $storeManager->employee = $employee;

        $this->saleData->is_credit_sale = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->saleData->credit_pending_amount = null;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkCreditAmounts(100);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Please note that credit sales are not permitted with our company.');

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
        $this->company->allow_credit_sale = true;
        $this->company->allow_employee_credit_sale = true;

        $this->saleData->is_credit_sale = true;
        $this->saleData->credit_store_manager_id = $storeManager->id;
        $this->saleData->credit_store_manager_passcode = '12345';
        $this->saleData->credit_pending_amount = 100;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = $this->companyId;
        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->checkSaleDetailsService->checkCreditAmounts(100);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Specified credit pending amount does not match with calculated credit pending amount.\nExpected: 0\nSpecified: 100'
);

test('isCreditSale method returns boolean as expected', function (): void {
    $this->saleData->is_credit_sale = false;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $response = $this->checkSaleDetailsService->isCreditSale();
    $this->assertFalse($response);

    $this->saleData->is_credit_sale = true;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $response = $this->checkSaleDetailsService->isCreditSale();
    $this->assertTrue($response);
});

test('checkCreditDetails method return null when is_credit_sale is false', function (): void {
    $this->saleData->is_credit_sale = false;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $response = $this->checkSaleDetailsService->checkCreditDetails();
    $this->assertNull($response);
});

test('checkCreditDetails method check credit details and return true when member id is specified', function (): void {
    $this->saleData->is_credit_sale = false;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $response = $this->checkSaleDetailsService->checkCreditDetails();
    $this->assertNull($response);
});

test(
    'checkCreditDetails method check credit details and return true when employee id is specified',
    function (): void {
        $this->saleData->member_id = null;
        $this->saleData->is_credit_sale = true;
        $this->saleData->employee_id = 1;
        $this->saleData->credit_pending_amount = 1;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->company->allow_credit_sale = true;
        $this->company->allow_employee_credit_sale = true;

        $this->checkSaleDetailsService->company = $this->company;
        $response = $this->checkSaleDetailsService->checkCreditDetails();
        $this->assertNull($response);
    }
);

test(
    'checkCreditDetails method throw exception if sale is credit and member id is not specified in sale',
    function (): void {
        $this->company->allow_credit_sale = true;
        $this->company->allow_employee_credit_sale = true;

        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleData->is_credit_sale = true;
        $this->checkSaleDetailsService->saleData->credit_pending_amount = 1;
        $this->checkSaleDetailsService->saleData->member_id = null;
        $this->checkSaleDetailsService->saleData->employee_id = null;
        $this->checkSaleDetailsService->checkCreditDetails();
    }
)->throws(HttpException::class, 'Please provide member or employee when a credit sale.');

test(
    'checkCreditDetails method throw exception if sale is credit pending amount is 0 or less than 0 specified in sale',
    function ($creditPendingAmount): void {
        $this->company->allow_credit_sale = true;
        $this->company->allow_employee_credit_sale = true;

        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleData->credit_pending_amount = $creditPendingAmount;
        $this->checkSaleDetailsService->saleData->is_credit_sale = true;
        $this->checkSaleDetailsService->saleData->member_id = null;
        $this->checkSaleDetailsService->saleData->employee_id = null;
        $this->checkSaleDetailsService->checkCreditDetails();
    }
)->throws(HttpException::class, 'Credit pending amount is not allow 0 or less than 0.')->with([0, null]);

test(
    'checkCreditDetails method throw exception if allow_employee_credit_sale false but pass employee id',
    function (): void {
        $this->company->allow_credit_sale = true;
        $this->company->allow_employee_credit_sale = false;

        $this->checkSaleDetailsService->company = $this->company;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->saleData->is_credit_sale = true;
        $this->checkSaleDetailsService->saleData->credit_pending_amount = 1;
        $this->checkSaleDetailsService->saleData->member_id = null;
        $this->checkSaleDetailsService->saleData->employee_id = 1;
        $this->checkSaleDetailsService->member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
            'employee_id' => 1,
        ]);
        $this->checkSaleDetailsService->checkCreditDetails();
    }
)->throws(HttpException::class, 'The employee is not authorized to make purchases through credit sale.');

test('hasLoyaltyPointsForCart method returns boolean as expected', function (): void {
    $response = $this->checkSaleDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkSaleDetailsService->saleData->cart_loyalty_point_amount = 00.0;
    $response = $this->checkSaleDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkSaleDetailsService->saleData->cart_loyalty_point_amount = 10.20;
    $response = $this->checkSaleDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkSaleDetailsService->saleData->cart_loyalty_point_amount = 10.20;
    $this->checkSaleDetailsService->saleData->cart_loyalty_points = 0;
    $response = $this->checkSaleDetailsService->hasLoyaltyPointsForCart();
    $this->assertFalse($response);

    $this->checkSaleDetailsService->saleData->cart_loyalty_point_amount = 10.20;
    $this->checkSaleDetailsService->saleData->cart_loyalty_points = 10;
    $response = $this->checkSaleDetailsService->hasLoyaltyPointsForCart();
    $this->assertTrue($response);
});

test(
    'checkLoyaltyPointsCartDiscount method returns null when there are no payments by loyalty points',
    function (): void {
        $saleDetails = $this->saleDetails;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $response = $this->checkSaleDetailsService->checkLoyaltyPointsCartDiscount();
        $this->assertNull($response);
    }
);

test(
    'checkLoyaltyPointsCartDiscount method sets the saleMismatches when the Specified amount does not match with the given loyalty points',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['cart_loyalty_point_amount'] = 100;
        $saleDetails['cart_loyalty_points'] = 10;

        $mock = $this->createPartialMock(
            CheckSaleDetailsService::class,
            ['hasLoyaltyPointsForCart', 'checkUserLoyaltyPoints']
        );

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue(new Member()));

        $mock->expects($this->once())
            ->method('hasLoyaltyPointsForCart')
            ->will($this->returnValue(true));

        $mock->cashier = new Cashier();
        $mock->saleData = new SaleData(...$saleDetails);
        $mock->saleMismatches = collect([]);

        $mock->checkLoyaltyPointsCartDiscount();
    }
)->throws(
    HttpException::class,
    'The specified amount (100) is more than the calculated amount from the loyalty points as per the membership of the user (0)'
);

test('checkLoyaltyPointsCartDiscount method returns the response as expected', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['cart_loyalty_point_amount'] = 100;
    $saleDetails['cart_loyalty_points'] = 400;

    $member = new Member([
        'membership_id' => 1,
        'loyalty_points' => 500,
    ]);

    $member->membership = new Membership([
        'loyalty_points_per_currency_unit' => 4,
    ]);

    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        ['hasLoyaltyPointsForCart', 'checkUserLoyaltyPoints', 'checkLoyaltyPointsIsValidOrNot']
    );

    $mock->expects($this->once())
        ->method('checkUserLoyaltyPoints')
        ->will($this->returnValue($member));

    $mock->expects($this->once())
        ->method('hasLoyaltyPointsForCart')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('checkLoyaltyPointsIsValidOrNot')
        ->will($this->returnValue(true));

    $mock->cashier = new Cashier();
    $mock->saleData = new SaleData(...$saleDetails);
    $mock->saleMismatches = collect([]);

    $response = $mock->checkLoyaltyPointsCartDiscount();
    $this->assertNull($response);

    $this->assertTrue($mock->saleMismatches->toArray() === []);
});

test('hasHappyHourDiscount method returns boolean as expected', function (): void {
    $cartItem = $this->cartItems[0];

    $response = $this->checkSaleDetailsService->hasHappyHourDiscount($cartItem);
    $this->assertFalse($response);

    $cartItem['happy_hours_offline_id'] = null;
    $response = $this->checkSaleDetailsService->hasHappyHourDiscount($cartItem);
    $this->assertFalse($response);

    $cartItem['happy_hours_offline_id'] = '';
    $response = $this->checkSaleDetailsService->hasHappyHourDiscount($cartItem);
    $this->assertFalse($response);

    $cartItem['happy_hours_offline_id'] = '123';
    $cartItem['happy_hours_discount_amount'] = null;
    $response = $this->checkSaleDetailsService->hasHappyHourDiscount($cartItem);
    $this->assertFalse($response);

    $cartItem['happy_hours_offline_id'] = '123';
    $cartItem['happy_hours_discount_amount'] = '';
    $response = $this->checkSaleDetailsService->hasHappyHourDiscount($cartItem);
    $this->assertFalse($response);

    $cartItem['happy_hours_offline_id'] = '123';
    $cartItem['happy_hours_discount_amount'] = '10.20';
    $response = $this->checkSaleDetailsService->hasHappyHourDiscount($cartItem);
    $this->assertTrue($response);
});

test('checkBoxProductPrice method returns null when product is not bundle', function (): void {
    $cartItem = $this->cartItems[0];
    $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;

    $response = $this->checkSaleDetailsService->checkBoxProductPrice($this->product, $cartItem);

    $this->assertNull($response);
});

test('checkBoxProductPrice method call same class method', function (): void {
    $cartItem = $this->cartItems[0];
    $cartItem['loyalty_points'] = 10;
    unset($cartItem['price']);

    $mock = $this->createPartialMock(
        CheckSaleDetailsService::class,
        ['checkBoxProductBoxRetailPrice', 'hasProductLoyaltyPoints', 'isPriceAttached', 'isBoxProductAttached']
    );

    $mock->expects($this->once())
        ->method('checkBoxProductBoxRetailPrice');

    $mock->expects($this->once())
        ->method('hasProductLoyaltyPoints')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('isBoxProductAttached')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('isPriceAttached')
        ->will($this->returnValue(false));

    $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;

    $mock->checkBoxProductPrice($this->product, $cartItem);
});

test('checkBoxProductBoxRetailPrice method return null when product bundle not attach', function (): void {
    $cartItem = $this->cartItems[0];

    $response = $this->checkSaleDetailsService->checkBoxProductBoxRetailPrice($this->product, $cartItem);
    $this->assertNull($response);
});

test('checkBoxProductBoxRetailPrice method throw exception when product bundle not found', function (): void {
    $cartItem = $this->cartItems[0];
    $cartItem['box_product_id'] = 50;

    $boxProduct = BoxProduct::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'package_type_id' => 1,
        'retail_price' => 0.0,
        'staff_price' => 0.0,
    ]);

    $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
    $this->product->boxes = collect([$boxProduct]);

    $this->checkSaleDetailsService->checkBoxProductBoxRetailPrice($this->product, $cartItem);
})->throws(HttpException::class, 'Product Bundle not in our record');

test(
    'checkBoxProductBoxRetailPrice method throw exception when product bundles retail price not available',
    function (): void {
        $cartItem = $this->cartItems[0];
        $cartItem['box_product_id'] = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 0.0,
            'staff_price' => 0.0,
        ]);

        $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
        $this->product->boxes = collect([$boxProduct]);

        $this->checkSaleDetailsService->checkBoxProductBoxRetailPrice($this->product, $cartItem);
    }
    // TODO: Temporary skip due to pos is not able create sale
)->throws(HttpException::class, 'Product bundle price is not available for the product with the name ABC')->skip();

test('checkBoxProductBoxRetailPrice method return null when price and retail_price match', function (): void {
    $cartItem = $this->cartItems[0];
    $cartItem['box_product_id'] = 1;
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $boxProduct = BoxProduct::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'package_type_id' => 1,
        'retail_price' => 10.0,
        'staff_price' => 0.0,
    ]);

    $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
    $this->product->boxes = collect([$boxProduct]);

    $response = $this->checkSaleDetailsService->checkBoxProductBoxRetailPrice($this->product, $cartItem);
    $this->assertNull($response);
});

test('checkBoxProductBoxRetailPrice method return null when is_exchange true', function (): void {
    $cartItem = $this->cartItems[0];
    $cartItem['box_product_id'] = 1;
    $cartItem['is_exchange'] = true;
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $boxProduct = BoxProduct::factory()->make([
        'id' => 1,
        'product_id' => $this->product->id,
        'package_type_id' => 1,
        'retail_price' => 11.0,
        'staff_price' => 0.0,
    ]);

    $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
    $this->product->boxes = collect([$boxProduct]);

    $response = $this->checkSaleDetailsService->checkBoxProductBoxRetailPrice($this->product, $cartItem);
    $this->assertNull($response);
});

test(
    'checkBoxProductBoxRetailPrice method throw exception when product bundles price and retail_price match',
    function (): void {
        $cartItem = $this->cartItems[0];
        $cartItem['box_product_id'] = 1;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 11.0,
            'staff_price' => 0.0,
        ]);

        $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
        $this->product->boxes = collect([$boxProduct]);

        $response = $this->checkSaleDetailsService->checkBoxProductBoxRetailPrice($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Provided price not match with product bundle price with the name ABC');

test('isBoxProductAttached method returns boolean as expected', function (): void {
    $cartItem = [];
    $response = $this->checkSaleDetailsService->isBoxProductAttached($cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = null;
    $response = $this->checkSaleDetailsService->isBoxProductAttached($cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = 0;
    $response = $this->checkSaleDetailsService->isBoxProductAttached($cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = 100;
    $response = $this->checkSaleDetailsService->isBoxProductAttached($cartItem);
    $this->assertTrue($response);
});

test('isBoxProductWithBoxProductIdAttached method returns boolean as expected', function (): void {
    $cartItem = [];
    $this->product->type_id = ProductTypes::ASSEMBLY_PRODUCT->value;
    $response = $this->checkSaleDetailsService->isBoxProductWithBoxProductIdAttached($this->product, $cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = null;
    $response = $this->checkSaleDetailsService->isBoxProductWithBoxProductIdAttached($this->product, $cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = 0;
    $response = $this->checkSaleDetailsService->isBoxProductWithBoxProductIdAttached($this->product, $cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = 100;
    $response = $this->checkSaleDetailsService->isBoxProductWithBoxProductIdAttached($this->product, $cartItem);
    $this->assertFalse($response);

    $cartItem['box_product_id'] = 100;
    $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
    $response = $this->checkSaleDetailsService->isBoxProductWithBoxProductIdAttached($this->product, $cartItem);
    $this->assertTrue($response);
});

test(
    'getBoxProductUnits method return product bundle value when product is bundle product ',
    function (): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['isBoxProductWithBoxProductIdAttached']);

        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => 1,
            'package_type_id' => 1,
            'units' => 10.0,
        ]);

        $this->product->boxes = collect([$boxProduct]);

        $mock->products = collect([$this->product]);

        $mock->expects($this->once())
            ->method('isBoxProductWithBoxProductIdAttached')
            ->will($this->returnValue(true));

        $cartItem = $this->cartItems[0];
        $cartItem['box_product_id'] = 1;

        $response = $mock->getBoxProductUnits($cartItem);
        $this->assertEquals($response, 10.0);
    }
);

test(
    'getBoxProductUnits method return 1 when product is not bundle product ',
    function (): void {
        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['isBoxProductWithBoxProductIdAttached']);

        $mock->products = collect([$this->product]);

        $mock->expects($this->once())
            ->method('isBoxProductWithBoxProductIdAttached')
            ->will($this->returnValue(false));

        $response = $mock->getBoxProductUnits($this->cartItems[0]);
        $this->assertEquals($response, 1.00);
    }
);

test(
    'checkCreditStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $response = $this->checkSaleDetailsService->checkCreditStoreManagerAuthorizationCode();
        $this->assertNull($response);
    }
);

test(
    'checkCreditStoreManagerAuthorizationCode method throw exception when code not match in database',
    function (): void {
        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn(null);
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->credit_store_manager_authorization_code = '1234';

        $this->checkSaleDetailsService->checkCreditStoreManagerAuthorizationCode();
    }
)->throws(HttpException::class, 'Specified Store manager authorization code does not correspond with our records.');

test(
    'checkCreditStoreManagerAuthorizationCode method throw exception when code not match with store manager',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 2,
            'code' => '1234',
            'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->credit_store_manager_authorization_code = '1234';

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->checkCreditStoreManagerAuthorizationCode();
    }
)->throws(HttpException::class, 'Specified Store manager authorization code and store manager not match.');

test('checkCreditStoreManagerAuthorizationCode method throw exception when code not active', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->credit_store_manager_authorization_code = '1234';
    $this->checkSaleDetailsService->saleData->credit_store_manager_id = 1;

    $this->checkSaleDetailsService->checkCreditStoreManagerAuthorizationCode();
})->throws(HttpException::class, 'Specified Store manager authorization code is not active.');

test(
    'checkCreditStoreManagerAuthorizationCode method throw exception when code is expire and happened_at set null',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'code' => '1234',
            'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->credit_store_manager_authorization_code = '1234';
        $this->checkSaleDetailsService->saleData->credit_store_manager_id = 1;
        $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->checkCreditStoreManagerAuthorizationCode();
    }
)->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test('checkCreditStoreManagerAuthorizationCode return null as expected', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->credit_store_manager_authorization_code = '1234';
    $this->checkSaleDetailsService->saleData->credit_store_manager_id = 1;

    $response = $this->checkSaleDetailsService->checkCreditStoreManagerAuthorizationCode();

    $this->assertNull($response);
});

test(
    'checkLayawayStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $response = $this->checkSaleDetailsService->checkLayawayStoreManagerAuthorizationCode();
        $this->assertNull($response);
    }
);

test(
    'checkLayawayStoreManagerAuthorizationCode method throw exception when code not match in database',
    function (): void {
        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn(null);
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->layaway_store_manager_authorization_code = '1234';

        $this->checkSaleDetailsService->checkLayawayStoreManagerAuthorizationCode();
    }
)->throws(HttpException::class, 'Specified Store manager authorization code does not correspond with our records.');

test(
    'checkLayawayStoreManagerAuthorizationCode method throw exception when code not match with store manager',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 2,
            'code' => '1234',
            'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->layaway_store_manager_authorization_code = '1234';

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->checkLayawayStoreManagerAuthorizationCode();
    }
)->throws(HttpException::class, 'Specified Store manager authorization code and store manager not match.');

test('checkLayawayStoreManagerAuthorizationCode method throw exception when code not active', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->layaway_store_manager_authorization_code = '1234';
    $this->checkSaleDetailsService->saleData->layaway_store_manager_id = 1;

    $this->checkSaleDetailsService->checkLayawayStoreManagerAuthorizationCode();
})->throws(HttpException::class, 'Specified Store manager authorization code is not active.');

test(
    'checkLayawayStoreManagerAuthorizationCode method throw exception when code is expire and happened_at set null',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'code' => '1234',
            'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->layaway_store_manager_authorization_code = '1234';
        $this->checkSaleDetailsService->saleData->layaway_store_manager_id = 1;
        $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->checkLayawayStoreManagerAuthorizationCode();
    }
)->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test('checkLayawayStoreManagerAuthorizationCode return null as expected', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->layaway_store_manager_authorization_code = '1234';
    $this->checkSaleDetailsService->saleData->layaway_store_manager_id = 1;

    $response = $this->checkSaleDetailsService->checkLayawayStoreManagerAuthorizationCode();

    $this->assertNull($response);
});

test(
    'checkBoxProductLoyaltyPoints method throws an exception when product bundles null',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);
        $this->product->boxes = collect([]);
        $response = $mock->checkBoxProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The specified product cannot be purchased using loyalty points.');

test(
    'checkBoxProductLoyaltyPoints method throws an exception when product bundles not match',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;
        $cartItem['box_product_id'] = 2;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);
        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 0.0,
            'staff_price' => 0.0,
        ]);

        $this->product->boxes = collect([$boxProduct]);
        $response = $mock->checkBoxProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The specified product cannot be purchased using loyalty points.');

test(
    'checkBoxProductLoyaltyPoints method throws an exception when product bundles Loyalty Points null',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;
        $cartItem['box_product_id'] = 1;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);
        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 0.0,
            'staff_price' => 0.0,
        ]);

        $boxProduct->boxProductLoyaltyPoints = collect([]);

        $this->product->boxes = collect([$boxProduct]);
        $response = $mock->checkBoxProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The specified product cannot be purchased using loyalty points.');

test(
    'checkBoxProductLoyaltyPoints method throws an exception when product membership tiers not in product tiers',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;
        $cartItem['box_product_id'] = 1;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));
        $mock->saleMismatches = collect([]);
        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make([
            'product_id' => 1,
            'membership_id' => 2,
            'points' => 100,
        ]);

        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 0.0,
            'staff_price' => 0.0,
        ]);

        $boxProduct->boxProductLoyaltyPoints = collect([$productLoyaltyPoint]);

        $this->product->boxes = collect([$boxProduct]);
        $response = $mock->checkBoxProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The specified product cannot be purchased using loyalty points.');

test(
    'checkBoxProductLoyaltyPoints method throws an exception when product loyalty points and attached product price is not same',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 11;
        $cartItem['price'] = null;
        $cartItem['box_product_id'] = 1;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);
        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make([
            'product_id' => 1,
            'membership_id' => 1,
            'points' => 10,
        ]);
        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 0.0,
            'staff_price' => 0.0,
        ]);

        $boxProduct->boxProductLoyaltyPoints = collect([$productLoyaltyPoint]);

        $this->product->boxes = collect([$boxProduct]);

        $response = $mock->checkBoxProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'Product loyalty points mismatched. Actual Product loyalty points is 100 And Given product loyalty points is 11'
);

test(
    'checkBoxProductLoyaltyPoints method returns null when product loyalty points and attached product price is same',
    function (): void {
        $cartItem = $this->saleDetails['items'][0];
        $cartItem['loyalty_points'] = 100;
        $cartItem['price'] = null;
        $cartItem['box_product_id'] = 1;

        $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

        $member = Member::factory()->make([
            'company_id' => $this->company->id,
            'created_location_id' => $this->location->id,
            'status' => Status::ACTIVE->value,
            'membership_id' => 1,
        ]);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue($member));

        $mock->saleMismatches = collect([]);

        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make([
            'product_id' => 1,
            'membership_id' => 1,
            'points' => 10,
        ]);

        $boxProduct = BoxProduct::factory()->make([
            'id' => 1,
            'product_id' => $this->product->id,
            'package_type_id' => 1,
            'retail_price' => 0.0,
            'staff_price' => 0.0,
        ]);

        $boxProduct->boxProductLoyaltyPoints = collect([$productLoyaltyPoint]);

        $this->product->boxes = collect([$boxProduct]);

        $response = $mock->checkBoxProductLoyaltyPoints($this->product, $cartItem);
        $this->assertNull($response);
    }
);

test('it calls the getEmployeeMember method of MemberQueries class', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->companyId,
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByEmployeeIdWithEmployee')
            ->once()
            ->andReturn($member);
    });
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkSaleDetailsService->getEmployeeMember($member->employee_id);

    expect($response)->toBeInstanceOf(Member::class);
});

test('it calls the checkLoyaltyPointsIsValidOrNot method check loyalty points is valid', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->companyId,
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $member->membership = new Membership([
        'min_loyalty_points_for_redemption' => 200,
        'max_loyalty_points_for_redemption' => 1000,
    ]);

    $this->checkSaleDetailsService->checkLoyaltyPointsIsValidOrNot($member, 201);
    $this->assertTrue(true);
});

test('it calls the checkLoyaltyPointsIsValidOrNot method check loyalty points is not valid', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->companyId,
        'created_location_id' => $this->location->id,
        'status' => Status::ACTIVE->value,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $mock = $this->createPartialMock(CheckSaleDetailsService::class, ['checkUserLoyaltyPoints']);

    $mock->saleMismatches = collect([]);

    $member->membership = new Membership([
        'min_loyalty_points_for_redemption' => 200,
        'max_loyalty_points_for_redemption' => 1000,
    ]);
    $mock->checkLoyaltyPointsIsValidOrNot($member, 20);
})->throws(
    HttpException::class,
    'The specified loyalty points (20) are not valid. Loyalty points must be between 200 and 1000.'
);

test('it calls the checkPaymentCurrency method currency id is not available in company', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['payments'] = [
        [
            'type_id' => 1,
            'amount' => 10,
            'currency_id' => 2,
            'current_currency_rate' => 1,
            'currency_amount' => 10,
        ],
    ];
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->checkPaymentCurrency(collect($saleDetails['payments']));
})->throws(HttpException::class, 'Payment currency id 2 is not available in this company.');

test('it calls the checkPaymentCurrency method currency rate is not available in company', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['payments'] = [
        [
            'type_id' => 1,
            'amount' => 10,
            'currency_id' => 1,
            'current_currency_rate' => 2,
            'currency_amount' => 10,
        ],
    ];
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->checkPaymentCurrency(collect($saleDetails['payments']));
})->throws(
    HttpException::class,
    'Payment currency rate 2 does not match with the actual currency rate of 1 for the currency id 1'
);

test('it calls the checkPaymentCurrency method currency amount is not matching', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['payments'] = [
        [
            'type_id' => 1,
            'amount' => 10,
            'currency_id' => 1,
            'current_currency_rate' => 1,
            'currency_amount' => 20,
        ],
    ];
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->checkPaymentCurrency(collect($saleDetails['payments']));
})->throws(HttpException::class, 'Payment amount 10 does not match with the actual currency amount of 20.');
