<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\DreamPrice\Services\DreamPriceService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\MemberGroupMember;
use App\Models\Product;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
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
        'sale_round_off_amount' => null,
    ];

    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->dreamPriceService = new DreamPriceService();
    $this->saleDetails['items'][0]['dream_price_id'] = 1;
    $this->saleDetails['items'][0]['dream_price_amount'] = 5;
    $this->cartItem = $this->saleDetails['items'][0];

    $this->dreamPrice = DreamPrice::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'start_date' => '2022-07-15',
        'end_date' => '2022-07-16',
        'allow_registered_member' => true,
        'allow_employee' => true,
    ]);
});

test('checkForApplicability method calls all the respective methods as expected.', function (): void {
    $dreamPrice = DreamPrice::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'start_date' => '2022-07-15',
        'end_date' => '2022-07-16',
    ]);

    $dreamPriceProduct = DreamPriceProduct::factory()->make([
        'dream_price_id' => $dreamPrice->id,
        'product_id' => 1,
        'price' => 10,
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'name' => 'ABC',
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $dreamPrice->dreamPriceProducts = collect([
        '0' => $dreamPriceProduct,
    ]);

    $this->checkSaleDetailsService->products = collect([
        '0' => $product,
    ]);

    $mock = $this->createPartialMock(
        DreamPriceService::class,
        [
            'checkNonRegularProduct',
            'checkProductBox',
            'checkWalkInMember',
            'checkDreamPriceDateRange',
            'checkDreamPriceAmount',
            'checkDreamPriceStores',
            'checkMember',
            'checkEmployee',
            'checkDreamPriceIsActive',
        ]
    );

    $mock->expects($this->once())
        ->method('checkNonRegularProduct');

    $mock->expects($this->once())
        ->method('checkProductBox');

    $mock->expects($this->once())
        ->method('checkWalkInMember');

    $mock->expects($this->once())
        ->method('checkDreamPriceDateRange');

    $mock->expects($this->once())
        ->method('checkDreamPriceAmount');

    $mock->expects($this->once())
        ->method('checkDreamPriceStores');

    $mock->expects($this->once())
        ->method('checkMember');

    $mock->expects($this->once())
        ->method('checkEmployee');

    $mock->expects($this->once())
        ->method('checkDreamPriceIsActive');

    $mock->checkForApplicability($this->checkSaleDetailsService, $dreamPrice, $this->saleDetails['items'][0]);
});

test(
    'getDiscountFor method returns item discount amount.',
    function (): void {
        $response = $this->dreamPriceService->getDiscountFor($this->cartItem);

        $this->assertTrue(
            CommonFunctions::numberFormat(
                (float) (($this->cartItem['price'] - $this->cartItem['dream_price_amount']) * $this->cartItem['quantity'])
            ) === $response
        );
    }
);

test(
    'checkDreamPriceDateRange method checks the given dream price is valid or not and set message in sale mismatches accordingly',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'start_date' => '2022-07-15',
            'end_date' => '2022-07-16',
        ]);

        $this->saleDetails['items'][0]['dream_price_id'] = $dreamPrice->id;
        $this->saleDetails['items'][0]['dream_price_amount'] = 5;

        $this->saleDetails['happened_at'] = '2022-07-14 10:05:20';
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = new Collection([]);
        $this->dreamPriceService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->dreamPriceService->checkDreamPriceDateRange(
            $this->dreamPriceService->checkSaleDetailsService,
            $dreamPrice
        );
    }
)->throws(
    HttpException::class,
    'Specified dream price is available between 2022-07-15 and 2022-07-16. only. But the specified sale date is 2022-07-14.'
);

test(
    'checkDreamPriceAmount method checks the given dream price amount and set message in sale mismatches accordingly',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'start_date' => '2022-07-15',
            'end_date' => '2022-07-16',
        ]);

        $dreamPriceProduct = DreamPriceProduct::factory()->make([
            'dream_price_id' => $dreamPrice->id,
            'product_id' => 1,
            'price' => 10,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'name' => 'ABC',
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        $dreamPrice->dreamPriceProducts = collect([
            '0' => $dreamPriceProduct,
        ]);

        $this->saleDetails['items'][0]['id'] = $product->id;
        $this->saleDetails['items'][0]['dream_price_id'] = $dreamPrice->id;
        $this->saleDetails['items'][0]['dream_price_amount'] = 5;
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->checkSaleDetailsService->products = collect([
            '0' => $product,
        ]);

        $this->checkSaleDetailsService->saleMismatches = new Collection([]);
        $this->dreamPriceService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->dreamPriceService->checkDreamPriceAmount(
            $this->dreamPriceService->checkSaleDetailsService,
            $dreamPrice,
            $this->saleDetails['items'][0],
            $product
        );
    }
)->throws(HttpException::class, 'The dream price of the product ABC is 10 but the specified price is 5.');

test(
    'checkNonRegularProduct method call and thrown an exception if dream price apply on non-regular product',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
            'name' => 'ABC',
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'type_id' => ProductTypes::SPECIAL_ORDER->value,
        ]);

        $this->checkSaleDetailsService->saleMismatches = new Collection([]);

        $this->dreamPriceService->checkNonRegularProduct($this->checkSaleDetailsService, $product);
    }
)->throws(
    HttpException::class,
    'Dream Price is applicable on regular products only. The type of the product with the name ABC is Special Order.'
);

test(
    'checkDreamPriceStores method return null when location not set in dream price',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);
        $dreamPrice->locations = collect([]);

        $response = $this->dreamPriceService->checkDreamPriceStores($this->checkSaleDetailsService, $dreamPrice);
        $this->assertNull($response);
    }
);

test(
    'checkDreamPriceStores method return null when location available in dream price',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'name' => 'test',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $dreamPrice->locations = collect([$location]);
        $this->checkSaleDetailsService->location = $location;
        $response = $this->dreamPriceService->checkDreamPriceStores($this->checkSaleDetailsService, $dreamPrice);
        $this->assertNull($response);
    }
);

test(
    'checkDreamPriceStores method set mismatches when location not available in dream price',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'name' => 'test',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->checkSaleDetailsService->saleMismatches = new Collection([]);
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 2,
            'name' => 'test',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $dreamPrice->locations = collect([$location]);
        $this->dreamPriceService->checkDreamPriceStores($this->checkSaleDetailsService, $dreamPrice);
    }
)->throws(HttpException::class, 'The dream price is not available for the location ');

test('checkMember method sets saleMismatches when Member is required for selected dreamPrice', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->allow_registered_member = false;

    $this->dreamPriceService->checkMember($this->checkSaleDetailsService, $this->dreamPrice);
})->throws(HttpException::class, 'Specified dream price is not allowed for the registered members.');

test('checkEmployee method sets saleMismatches when employee is required for selected dreamPrice', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['employee_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->allow_employee = false;

    $this->dreamPriceService->checkEmployee($this->checkSaleDetailsService, $this->dreamPrice);
})->throws(HttpException::class, 'Specified dream price is not allowed for the employees.');

test('checkMember method return null when member not required in dreamPrice', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->allow_registered_member = false;

    $response = $this->dreamPriceService->checkMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkMember method return null when dreamPrice member group not selected', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->memberGroups = collect([]);
    $this->dreamPrice->allow_registered_member = true;

    $response = $this->dreamPriceService->checkMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkMember method return null when member group is in dreamPrice member group', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->member = Member::factory()->make([
        'id' => 1,
        'company_id' => '1',
        'type_id' => '1',
        'title_id' => '1',
        'race_id' => '1',
        'gender_id' => '1',
        'created_location_id' => '1',
        'group_id' => '1',
    ]);

    $this->checkSaleDetailsService->member->memberGroupMembers = collect([
        MemberGroupMember::factory()->make([
            'member_id' => 1,
            'member_group_id' => 1,
        ]),
    ]);

    $this->dreamPrice->memberGroups = collect([
        MemberGroup::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'code' => '123456',
        ]),
    ]);
    $this->dreamPrice->allow_registered_member = true;

    $response = $this->dreamPriceService->checkMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkMember method sets saleMismatches when member group is not in dreamPrice member group', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->member = Member::factory()->make([
        'company_id' => '1',
        'type_id' => '1',
        'title_id' => '1',
        'race_id' => '1',
        'gender_id' => '1',
        'created_location_id' => '1',
        'group_id' => '2',
    ]);

    $this->dreamPrice->memberGroups = collect([
        MemberGroup::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'code' => '123456',
        ]),
    ]);

    $this->dreamPrice->allow_registered_member = true;

    $response = $this->dreamPriceService->checkMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
})->throws(HttpException::class, 'Member is not valid for the specified dream price.');

test('isMemberAttached method returns true when member id is specified', function (): void {
    $checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('isMemberAttached')
                ->once()
                ->andReturn(true);
        }
    );

    $response = $this->dreamPriceService->isMemberAttached($checkSaleDetailsService);
    $this->assertTrue($response);
});

test('isMemberAttached method returns true when member details is specified', function (): void {
    $checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('isMemberAttached')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('hasMemberDetails')
                ->once()
                ->andReturn(true);
        }
    );

    $response = $this->dreamPriceService->isMemberAttached($checkSaleDetailsService);
    $this->assertTrue($response);
});

test('isMemberAttached method returns false when member details not specified', function (): void {
    $checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('isMemberAttached')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('hasMemberDetails')
                ->once()
                ->andReturn(false);
        }
    );

    $response = $this->dreamPriceService->isMemberAttached($checkSaleDetailsService);
    $this->assertFalse($response);
});

test('checkMember method return null when dreamPrice Allow Registered Member and member not pass', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->memberGroups = collect([]);
    $this->dreamPrice->allow_registered_member = true;

    $response = $this->dreamPriceService->checkMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkEmployee method return null when dreamPrice Allow Employee and employee not pass', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['employee_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->memberGroups = collect([]);
    $this->dreamPrice->allow_employee = true;

    $response = $this->dreamPriceService->checkEmployee($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkWalkInMember method return null when Allow Walk In Member', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->allow_walk_in_member = true;

    $response = $this->dreamPriceService->checkWalkInMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
});

test('checkWalkInMember method return null when not Allow Walk In Member and member id pass', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->allow_walk_in_member = false;

    $response = $this->dreamPriceService->checkWalkInMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
});

test('checkWalkInMember method return null when not Allow Walk In Member and employee id pass', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['employee_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->dreamPrice->allow_walk_in_member = false;

    $response = $this->dreamPriceService->checkWalkInMember($this->checkSaleDetailsService, $this->dreamPrice);
    $this->assertNull($response);
});

test(
    'checkWalkInMember method set mismatches when not Allow Walk In Member and employee aor member not Specified',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $saleDetails['employee_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->dreamPrice->allow_walk_in_member = false;

        $this->dreamPriceService->checkWalkInMember($this->checkSaleDetailsService, $this->dreamPrice);
    }
)->throws(HttpException::class, 'Specified dream price is not allowed for the walk in member.');

test(
    'checkNonRegularProduct method return null when product type is regular product.',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
            'name' => 'ABC',
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->dreamPriceService->checkNonRegularProduct($this->checkSaleDetailsService, $product);

        $this->assertNull($response);
    }
);

test(
    'checkNonRegularProduct method return null when product type is bundle product.',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
            'name' => 'ABC',
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->dreamPriceService->checkNonRegularProduct($this->checkSaleDetailsService, $product);

        $this->assertNull($response);
    }
);

test(
    'checkNonRegularProduct method return null when product type is assembly product.',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
            'name' => 'ABC',
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'type_id' => ProductTypes::ASSEMBLY_PRODUCT->value,
        ]);

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->dreamPriceService->checkNonRegularProduct($this->checkSaleDetailsService, $product);

        $this->assertNull($response);
    }
);

test(
    'checkProductBox method thrown an exception when dream price apply in bundle product',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = new Collection([]);
        $this->saleDetails['items'][0]['box_product_id'] = 1;
        $this->dreamPriceService->checkProductBox($this->checkSaleDetailsService, $this->saleDetails['items'][0]);
    }
)->throws(HttpException::class, 'Dream Price is not applicable on product bundle.');

test(
    'checkProductBox method return null when dream price apply in none bundle product',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = new Collection([]);
        $response = $this->dreamPriceService->checkProductBox(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
        $this->assertNull($response);
    }
);
