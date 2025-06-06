<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\DataObjects\CompleteLayawaySaleData;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\GenerateLoyaltyPointsService;
use App\Domains\Sale\Services\SaleReturnService;
use App\Models\Brand;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->loyaltyPoints = [
        [
            'loyalty_campaign_id' => 1,
            'minimum_spend_amount' => 10,
            'points' => 10,
            'expired_at' => now()->addDays(2)->format('Y-m-d'),
        ],
        [
            'loyalty_campaign_id' => 2,
            'minimum_spend_amount' => 20,
            'points' => 20,
            'expired_at' => now()->addDays(2)->format('Y-m-d'),
        ],
    ];

    $this->generateLoyaltyPointsService = new GenerateLoyaltyPointsService();
});

test(
    'getTotalApplicableLoyaltyPoints method returns the applicable loyalty point as expected',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $response = $this->generateLoyaltyPointsService->getTotalApplicableLoyaltyPoints(100, $loyaltyCampaign);

        $this->assertEquals(200, $response);
    }
);

test(
    'getFinalAmountExcludeByBrands method returns the applicable loyalty point when excludedBrands is empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $loyaltyCampaign->excludedBrands = collect([]);

        $response = $this->generateLoyaltyPointsService->getFinalAmountExcludeByBrands(
            100,
            $loyaltyCampaign,
            new Sale()
        );

        $this->assertEquals(100, $response);
    }
);

test(
    'getFinalAmountExcludeByBrands method returns the applicable loyalty point when excludedBrands is not empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $brand1 = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $brand2 = Brand::factory()->make([
            'id' => 2,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $loyaltyCampaign->excludedBrands = collect([$brand1]);

        $saleItem1 = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'price_paid_per_unit' => 1,
            'quantity' => 1,
        ]);

        $product = commonGetProductDetails(false);
        $product->brand = $brand1;

        $saleItem1->product = $product;

        $saleItem2 = SaleItem::factory()->make([
            'id' => 2,
            'sale_id' => 1,
            'product_id' => 2,
            'derivative_id' => 1,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
        ]);

        $product2 = commonGetProductDetails(false);
        $product2->brand = $brand2;

        $saleItem2->product = $product2;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 100,
            'layaway_pending_amount' => 100,
        ]);

        $sale->saleItems = collect([$saleItem1, $saleItem2]);

        $response = $this->generateLoyaltyPointsService->getFinalAmountExcludeByBrands(100, $loyaltyCampaign, $sale);
        $this->assertEquals(50, $response);
    }
);

test(
    'getFinalAmountExcludeByBrandsForOffline method returns the applicable loyalty point when excludedBrands is empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $loyaltyCampaign->excludedBrands = collect([]);

        $response = $this->generateLoyaltyPointsService->getFinalAmountExcludeByBrandsForOffline(
            100,
            100,
            [100],
            $loyaltyCampaign,
            new CheckSaleDetailsService()
        );

        $this->assertEquals(100, $response);
    }
);

test(
    'getFinalAmountExcludeByBrandsForOffline method returns the applicable loyalty point when excludedBrands is not empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $brand1 = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $brand2 = Brand::factory()->make([
            'id' => 2,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $loyaltyCampaign->excludedBrands = collect([$brand1]);

        $product = commonGetProductDetails(false);
        $product->brand = $brand1;

        $product2 = Product::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'has_batch' => false,
            'type_id' => 1,
        ]);
        $product2->brand = $brand2;

        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->cartItems = collect([
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '5',
                'promoter_ids' => [1],
            ],
            [
                'id' => 2,
                'price' => '10.00',
                'quantity' => '5',
                'promoter_ids' => [1],
            ],
        ]);

        $checkSaleDetailsService->products = collect([$product, $product2]);

        $response = $this->generateLoyaltyPointsService->getFinalAmountExcludeByBrandsForOffline(
            100,
            100,
            [
                1 => 50,
                2 => 50,
            ],
            $loyaltyCampaign,
            $checkSaleDetailsService
        );
        $this->assertEquals(50, $response);
    }
);

test('setDetails method works as expected', function (): void {
    $this->mock(LoyaltyCampaignQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIds')
            ->once();
    });

    $this->generateLoyaltyPointsService->setDetails($this->loyaltyPoints, 1);

    $this->assertTrue($this->generateLoyaltyPointsService->loyaltyPointsMismatches->toArray() === []);
});

test(
    'checkLoyaltyPoints method method calls same class methods as expected',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
        ]);

        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            [
                'checkUserDetails',
                'checkLoyaltyCampaigns',
                'getLoyaltyCampaign',
                'checkMinimumSpendAmount',
                'checkDateRange',
                'checkExpireDate',
                'getFinalAmountExcludeByBrandsForOffline',
                'getTotalApplicableLoyaltyPoints',
                'checkApplicableLoyaltyPoints',
            ]
        );

        $mock->expects($this->once())
            ->method('checkUserDetails');

        $mock->expects($this->once())
            ->method('checkLoyaltyCampaigns');

        $mock->expects($this->once())
            ->method('getLoyaltyCampaign')
            ->will($this->returnValue($loyaltyCampaign));

        $mock->expects($this->once())
            ->method('checkMinimumSpendAmount');

        $mock->expects($this->once())
            ->method('checkDateRange');

        $mock->expects($this->once())
            ->method('checkExpireDate');

        $mock->expects($this->once())
            ->method('getFinalAmountExcludeByBrandsForOffline');

        $mock->expects($this->once())
            ->method('getTotalApplicableLoyaltyPoints');

        $mock->expects($this->once())
            ->method('checkApplicableLoyaltyPoints');

        $mock->loyaltyCampaigns = collect([$loyaltyCampaign]);
        $mock->loyaltyPoints = collect([
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 10,
                'points' => 10,
                'expired_at' => now()->addDays(2)->format('Y-m-d'),
            ],
        ]);
        $mock->loyaltyPointsMismatches = collect([]);

        $mock->checkLoyaltyPoints(
            [100],
            new CheckSaleDetailsService(),
            100,
            100,
            null,
            now()->format('Y-m-d H:i:s')
        );
    }
);

test(
    'checkUserDetails method throws an exception when null specified in user details ',
    function (): void {
        $this->generateLoyaltyPointsService->checkUserDetails(null);
    }
)->throws(HttpException::class, 'User is compulsory when generate loyalty point');

test(
    'checkUserDetails return null when member id specify',
    function (): void {
        $response = $this->generateLoyaltyPointsService->checkUserDetails(1);
        $this->assertNull($response);
    }
);

test(
    'checkLoyaltyCampaigns method throws an exception when some of the loyalty campaigns are not in our records',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'company_id' => 1,
        ]);

        $this->generateLoyaltyPointsService->loyaltyCampaigns = collect([$loyaltyCampaign]);
        $this->generateLoyaltyPointsService->loyaltyPoints = collect($this->loyaltyPoints);

        $this->generateLoyaltyPointsService->checkLoyaltyCampaigns();
    }
)->throws(HttpException::class, 'Some of the loyalty campaigns are not in our records.');

test(
    'getLoyaltyCampaign method return Loyalty Campaign',
    function (): void {
        $loyaltyCampaigns = [];
        $loyaltyCampaigns[] = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
        ]);

        $loyaltyCampaigns[] = LoyaltyCampaign::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'minimum_spend_amount' => 30,
        ]);

        $this->generateLoyaltyPointsService->loyaltyCampaigns = collect($loyaltyCampaigns);

        $response = $this->generateLoyaltyPointsService->getLoyaltyCampaign(1);

        expect($response->toArray())
            ->toHaveKey('id', $loyaltyCampaigns[0]->id)
            ->toHaveKey('company_id', $loyaltyCampaigns[0]->company_id)
            ->toHaveKey('minimum_spend_amount', $loyaltyCampaigns[0]->minimum_spend_amount);
    }
);

test(
    'checkMinimumSpendAmount method sets Mismatches when minimum spend amount mismatched',
    function (): void {
        $this->generateLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateLoyaltyPointsService->checkMinimumSpendAmount(20, 30);
    }
)->throws(
    HttpException::class,
    'The specified minimum spend amount does not match the loyalty campaign minimum spend amount. The loyalty campaign minimum spend amount is 30. But the specified minimum spend amount is 20.'
);

test(
    'checkDateRange method sets Mismatches when Specified loyalty campaign is not available specified date',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'minimum_spend_amount' => 20,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $this->generateLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateLoyaltyPointsService->checkDateRange(now()->format('Y-m-d H:i:s'), $loyaltyCampaign);
    }
)->throws(
    HttpException::class,
    'Specified loyalty campaign is available between ' . now()->addDay()->format('Y-m-d') . ' and ' . now()->addDays(
        2
    )->format('Y-m-d') . '. only. But the specified sale date is ' . now()->format('Y-m-d') . '.'
);
test(
    'checkExpireDate method sets Mismatches when Specified expire date does not match with our calculations',
    function (): void {
        $this->generateLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateLoyaltyPointsService->checkExpireDate(
            now()->addDays(2)->format('Y-m-d'),
            now()->format('Y-m-d H:i:s'),
            5
        );
    }
)->throws(
    HttpException::class,
    'Specified expire date does not match with our calculations. The actual expire date is ' . now()->addDays(
        5
    )->format('Y-m-d') . ' and requested expire date is ' . now()->addDays(2)->format('Y-m-d') . '.'
);

test(
    'checkApplicableLoyaltyPoints method sets Mismatches when Specified expire date does not match with our calculations',
    function (): void {
        $this->generateLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateLoyaltyPointsService->checkApplicableLoyaltyPoints(10, 20);
    }
)->throws(
    HttpException::class,
    'Specified loyalty points does not match with our calculations. Calculated loyalty points are 10 and, the specified loyalty points is 20.'
);

test(
    'checkExcludedBrands return false when product brand not set',
    function (): void {
        $product = commonGetProductDetails(false);
        $product->brand = null;
        $response = $this->generateLoyaltyPointsService->checkExcludedBrands($product, new LoyaltyCampaign());

        $this->assertFalse($response);
    }
);

test(
    'checkExcludedBrands return false when loyalty campaign excluded brands not set',
    function (): void {
        $brand = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $product = commonGetProductDetails(false);
        $product->brand = $brand;

        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $loyaltyCampaign->excludedBrands = collect([]);
        $response = $this->generateLoyaltyPointsService->checkExcludedBrands($product, $loyaltyCampaign);

        $this->assertFalse($response);
    }
);

test(
    'checkExcludedBrands return true when loyalty campaign excluded brands is set',
    function (): void {
        $brand = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $product = commonGetProductDetails(false);
        $product->brand = $brand;

        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $loyaltyCampaign->excludedBrands = collect([$brand]);
        $response = $this->generateLoyaltyPointsService->checkExcludedBrands($product, $loyaltyCampaign);

        $this->assertTrue($response);
    }
);

test(
    'updateUserLoyaltyPointsForOffline return null when user details not set',
    function (): void {
        $response = $this->generateLoyaltyPointsService->updateUserLoyaltyPointsForOffline(
            null,
            1,
            now()->format('Y-m-d'),
            new Sale()
        );

        $this->assertNull($response);
    }
);

test(
    'updateUserLoyaltyPointsForOffline return null when set null in user details ',
    function (): void {
        $response = $this->generateLoyaltyPointsService->updateUserLoyaltyPointsForOffline(
            null,
            1,
            now()->format('Y-m-d'),
            new Sale()
        );

        $this->assertNull($response);
    }
);

test(
    'updateUserLoyaltyPointsForOffline method calls same class methods as expected',
    function (): void {
        $member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'loyalty_points' => 100,
        ]);
        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByIdWithMembershipAndLoyaltyPoints')
                ->once()
                ->andReturn($member);
            $mock->shouldReceive('increaseLoyaltyPoints')
                ->once();
        });

        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            ['saveLoyaltyPoints', 'getLoyaltyCampaign']
        );

        $mock->expects($this->any())
            ->method('saveLoyaltyPoints');

        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
            'loyalty_point_expiration_days' => 1,
        ]);

        $mock->expects($this->any())
            ->method('getLoyaltyCampaign')
            ->will($this->returnValue($loyaltyCampaign));

        $mock->loyaltyCampaigns = collect([$loyaltyCampaign]);
        $mock->loyaltyPoints = collect($this->loyaltyPoints);

        $mock->updateUserLoyaltyPointsForOffline(1, 1, now()->format('Y-m-d'), new Sale());
    }
);

test(
    'saveLoyaltyPoints method calls addNew methods of LoyaltyPointQueries class',
    function (): void {
        $loyaltyPoint = LoyaltyPoint::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'sale_id' => 1,
            'loyalty_campaign_id' => 1,
            'points' => 100,
            'available_points' => 100,
            'minimum_spend_amount' => 0,
            'expiry_date' => now()->addDays(30)->format('Y-m-d H:i:s'),
        ]);

        $member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'loyalty_points' => 100,
        ]);

        $sale = Sale::factory()->make([
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->mock(LoyaltyPointQueries::class, function ($mock) use ($loyaltyPoint): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($loyaltyPoint);
        });

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->generateLoyaltyPointsService->saveLoyaltyPoints(
            $this->loyaltyPoints[0],
            $member,
            $sale,
            now()->format('Y-m-d'),
            100,
            100
        );
    }
);

test(
    'getLayawaySaleFinalAmountExcludeByBrands method returns the applicable loyalty point when excludedBrands is empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $loyaltyCampaign->excludedBrands = collect([]);

        $response = $this->generateLoyaltyPointsService->getLayawaySaleFinalAmountExcludeByBrands(
            100,
            $loyaltyCampaign,
            new Sale()
        );

        $this->assertEquals(100, $response);
    }
);

test(
    'getLayawaySaleFinalAmountExcludeByBrands method returns the applicable loyalty point when excludedBrands is not empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $brand1 = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $brand2 = Brand::factory()->make([
            'id' => 2,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $loyaltyCampaign->excludedBrands = collect([$brand1]);

        $saleItem1 = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'price_paid_per_unit' => 1,
            'quantity' => 1,
        ]);

        $product = commonGetProductDetails(false);
        $product->brand = $brand1;

        $saleItem1->product = $product;

        $saleItem2 = SaleItem::factory()->make([
            'id' => 2,
            'sale_id' => 1,
            'product_id' => 2,
            'derivative_id' => 1,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
        ]);

        $product2 = commonGetProductDetails(false);
        $product2->brand = $brand2;

        $saleItem2->product = $product2;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 100,
            'layaway_pending_amount' => 100,
        ]);

        $sale->saleItems = collect([$saleItem1, $saleItem2]);

        $response = $this->generateLoyaltyPointsService->getLayawaySaleFinalAmountExcludeByBrands(
            100,
            $loyaltyCampaign,
            $sale
        );
        $this->assertEquals(50, $response);
    }
);

test(
    'checkLayawaySaleLoyaltyPoints method method calls same class methods as expected',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
        ]);

        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            [
                'checkUserDetails',
                'checkLoyaltyCampaigns',
                'getLoyaltyCampaign',
                'checkMinimumSpendAmount',
                'checkDateRange',
                'checkExpireDate',
                'getLayawaySaleFinalAmountExcludeByBrands',
                'getTotalApplicableLoyaltyPoints',
                'checkApplicableLoyaltyPoints',
            ]
        );

        $mock->expects($this->once())
            ->method('checkUserDetails');

        $mock->expects($this->once())
            ->method('checkLoyaltyCampaigns');

        $mock->expects($this->once())
            ->method('getLoyaltyCampaign')
            ->will($this->returnValue($loyaltyCampaign));

        $mock->expects($this->once())
            ->method('checkMinimumSpendAmount');

        $mock->expects($this->once())
            ->method('checkDateRange');

        $mock->expects($this->once())
            ->method('checkExpireDate');

        $mock->expects($this->once())
            ->method('getLayawaySaleFinalAmountExcludeByBrands');

        $mock->expects($this->once())
            ->method('getTotalApplicableLoyaltyPoints');

        $mock->expects($this->once())
            ->method('checkApplicableLoyaltyPoints');

        $mock->loyaltyCampaigns = collect([$loyaltyCampaign]);
        $mock->loyaltyPoints = collect([
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 10,
                'points' => 10,
                'expired_at' => now()->addDays(2)->format('Y-m-d'),
            ],
        ]);
        $mock->loyaltyPointsMismatches = collect([]);

        $sale = new Sale();

        $mock->checkLayawaySaleLoyaltyPoints(10.10, 1, $sale, now()->format('Y-m-d H:i:s'));
    }
);

test(
    'hasGenerateLoyaltyPointsForLayawaySale method returns boolean as expected',
    function (): void {
        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                ],
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $response = $this->generateLoyaltyPointsService->hasGenerateLoyaltyPointsForLayawaySale(
            $completeLayawaySaleData
        );
        $this->assertFalse($response);

        $data['loyalty_points'] = [
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 10,
                'points' => 10,
                'expired_at' => now()->format('Y-m-d'),
            ],
        ];

        $completeLayawaySaleData = new CompleteLayawaySaleData(...$data);

        $response = $this->generateLoyaltyPointsService->hasGenerateLoyaltyPointsForLayawaySale(
            $completeLayawaySaleData
        );
        $this->assertTrue($response);
    }
);

test(
    'saveGenerateLoyaltyPoints method method calls same class methods when offline loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            ['updateUserLoyaltyPointsForOffline']
        );

        $mock->expects($this->once())
            ->method('updateUserLoyaltyPointsForOffline');

        $sale = new Sale();

        $checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData('1111', now()->format('Y-m-d'));
            $mock->companyId = 1;
            $mock->location = new Location([
                'loyalty_point_expiration_days' => 1,
            ]);
            $mock->shouldReceive('hasGenerateLoyaltyPoints')
                ->once()
                ->andReturn(true);
        });

        $mock->saveGenerateLoyaltyPoints($checkSaleDetailsService, $sale, null);
    }
);

test(
    'saveGenerateLoyaltyPoints method method calls same class methods when online loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(GenerateLoyaltyPointsService::class, ['generateLoyaltyPoints']);

        $mock->expects($this->once())
            ->method('generateLoyaltyPoints');

        $sale = new Sale();

        $checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData('1111', now()->format('Y-m-d'));
            $mock->location = new Location([
                'loyalty_point_expiration_days' => 10,
            ]);
            $mock->companyId = 1;
            $mock->shouldReceive('hasGenerateLoyaltyPoints')
                ->once()
                ->andReturn(false);
            $mock->company = Company::factory()->make([
                'default_country_id' => 1,
            ]);
        });

        $checkSaleDetailsService->saleReturnService = $this->mock(
            SaleReturnService::class,
            function ($mock): void {
                $mock->shouldReceive('getExchangeItemsTotal')
                    ->once()
                    ->andReturn(10);
            }
        );

        $mock->saveGenerateLoyaltyPoints($checkSaleDetailsService, $sale, null);
    }
);

test(
    'generateLoyaltyPointsForLayawaySale method method calls same class methods when offline loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            ['hasGenerateLoyaltyPointsForLayawaySale', 'updateUserLoyaltyPointsForOffline']
        );

        $mock->expects($this->once())
            ->method('hasGenerateLoyaltyPointsForLayawaySale')
            ->will($this->returnValue(true));

        $mock->expects($this->once())
            ->method('updateUserLoyaltyPointsForOffline');

        $sale = new Sale();

        $companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
            'credit_sale_earn_loyalty_points' => true,
        ]);

        $completeLayawaySaleData = new CompleteLayawaySaleData(now()->format('Y-m-d'));
        $mock->generateLoyaltyPointsForLayawaySale($completeLayawaySaleData, $sale, $companySetting, 1, 10.10, 1);
    }
);

test(
    'generateLoyaltyPointsForLayawaySale method method calls same class methods when online loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            ['hasGenerateLoyaltyPointsForLayawaySale', 'generateLoyaltyPoints']
        );

        $mock->expects($this->once())
            ->method('hasGenerateLoyaltyPointsForLayawaySale')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('generateLoyaltyPoints');

        $sale = new Sale();

        $completeLayawaySaleData = new CompleteLayawaySaleData(now()->format('Y-m-d'));

        $companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
            'layaway_sale_earn_loyalty_points' => true,
        ]);

        $mock->generateLoyaltyPointsForLayawaySale($completeLayawaySaleData, $sale, $companySetting, 1, 10.10, 1);
    }
);

test(
    'hasGenerateLoyaltyPointsForCreditSale method returns boolean as expected',
    function (): void {
        $data = [
            'happened_at' => '2022-01-04 04:20:50',
            'payments' => [
                [
                    'type_id' => 1,
                    'amount' => 10,
                ],
            ],
        ];

        $completeCreditSaleData = new CompleteCreditSaleData(...$data);

        $response = $this->generateLoyaltyPointsService->hasGenerateLoyaltyPointsForCreditSale(
            $completeCreditSaleData
        );
        $this->assertFalse($response);

        $data['loyalty_points'] = [
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 10,
                'points' => 10,
                'expired_at' => now()->format('Y-m-d'),
            ],
        ];

        $completeCreditSaleData = new CompleteCreditSaleData(...$data);

        $response = $this->generateLoyaltyPointsService->hasGenerateLoyaltyPointsForCreditSale(
            $completeCreditSaleData
        );
        $this->assertTrue($response);
    }
);

test(
    'getCreditSaleFinalAmountExcludeByBrands method returns the applicable loyalty point when excludedBrands is empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $loyaltyCampaign->excludedBrands = collect([]);

        $response = $this->generateLoyaltyPointsService->getCreditSaleFinalAmountExcludeByBrands(
            100,
            $loyaltyCampaign,
            new Sale()
        );

        $this->assertEquals(100, $response);
    }
);

test(
    'getCreditSaleFinalAmountExcludeByBrands method returns the applicable loyalty point when excludedBrands is not empty',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
            'loyalty_points' => 20,
            'status' => true,
        ]);

        $brand1 = Brand::factory()->make([
            'id' => 1,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $brand2 = Brand::factory()->make([
            'id' => 2,
            'name' => 'Test Brand',
            'code' => '12465',
        ]);

        $loyaltyCampaign->excludedBrands = collect([$brand1]);

        $saleItem1 = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'price_paid_per_unit' => 1,
            'quantity' => 1,
        ]);

        $product = commonGetProductDetails(false);
        $product->brand = $brand1;

        $saleItem1->product = $product;

        $saleItem2 = SaleItem::factory()->make([
            'id' => 2,
            'sale_id' => 1,
            'product_id' => 2,
            'derivative_id' => 1,
            'price_paid_per_unit' => 10,
            'quantity' => 10,
        ]);

        $product2 = commonGetProductDetails(false);
        $product2->brand = $brand2;

        $saleItem2->product = $product2;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 100,
            'credit_pending_amount' => 100,
        ]);

        $sale->saleItems = collect([$saleItem1, $saleItem2]);

        $response = $this->generateLoyaltyPointsService->getCreditSaleFinalAmountExcludeByBrands(
            100,
            $loyaltyCampaign,
            $sale
        );
        $this->assertEquals(50, $response);
    }
);

test(
    'checkCreditSaleLoyaltyPoints method method calls same class methods as expected',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'minimum_spend_amount' => 10,
        ]);

        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            [
                'checkUserDetails',
                'checkLoyaltyCampaigns',
                'getLoyaltyCampaign',
                'checkMinimumSpendAmount',
                'checkDateRange',
                'checkExpireDate',
                'getCreditSaleFinalAmountExcludeByBrands',
                'getTotalApplicableLoyaltyPoints',
                'checkApplicableLoyaltyPoints',
            ]
        );

        $mock->expects($this->once())
            ->method('checkUserDetails');

        $mock->expects($this->once())
            ->method('checkLoyaltyCampaigns');

        $mock->expects($this->once())
            ->method('getLoyaltyCampaign')
            ->will($this->returnValue($loyaltyCampaign));

        $mock->expects($this->once())
            ->method('checkMinimumSpendAmount');

        $mock->expects($this->once())
            ->method('checkDateRange');

        $mock->expects($this->once())
            ->method('checkExpireDate');

        $mock->expects($this->once())
            ->method('getCreditSaleFinalAmountExcludeByBrands');

        $mock->expects($this->once())
            ->method('getTotalApplicableLoyaltyPoints');

        $mock->expects($this->once())
            ->method('checkApplicableLoyaltyPoints');

        $mock->loyaltyCampaigns = collect([$loyaltyCampaign]);
        $mock->loyaltyPoints = collect([
            [
                'loyalty_campaign_id' => 1,
                'minimum_spend_amount' => 10,
                'points' => 10,
                'expired_at' => now()->addDays(2)->format('Y-m-d'),
            ],
        ]);
        $mock->loyaltyPointsMismatches = collect([]);

        $sale = new Sale();

        $mock->checkCreditSaleLoyaltyPoints(10.10, 1, $sale, now()->format('Y-m-d H:i:s'));
    }
);

test(
    'generateLoyaltyPointsForCreditSale method method calls same class methods when offline loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            ['hasGenerateLoyaltyPointsForCreditSale', 'updateUserLoyaltyPointsForOffline']
        );

        $mock->expects($this->once())
            ->method('hasGenerateLoyaltyPointsForCreditSale')
            ->will($this->returnValue(true));

        $mock->expects($this->once())
            ->method('updateUserLoyaltyPointsForOffline');

        $sale = new Sale();

        $completeCreditSaleData = new CompleteCreditSaleData(now()->format('Y-m-d'));

        $companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
            'credit_sale_earn_loyalty_points' => true,
        ]);

        $mock->generateLoyaltyPointsForCreditSale($completeCreditSaleData, $sale, $companySetting, 1, 10.10, 1);
    }
);

test(
    'generateLoyaltyPointsForCreditSale method method calls same class methods when online loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateLoyaltyPointsService::class,
            ['hasGenerateLoyaltyPointsForCreditSale', 'generateLoyaltyPoints']
        );

        $mock->expects($this->once())
            ->method('hasGenerateLoyaltyPointsForCreditSale')
            ->will($this->returnValue(false));

        $mock->expects($this->once())
            ->method('generateLoyaltyPoints');

        $sale = new Sale();

        $companySetting = CompanySetting::factory()->make([
            'company_id' => 1,
            'credit_sale_earn_loyalty_points' => true,
        ]);

        $completeCreditSaleData = new CompleteCreditSaleData(now()->format('Y-m-d'));

        $mock->generateLoyaltyPointsForCreditSale($completeCreditSaleData, $sale, $companySetting, 1, 10.10, 1);
    }
);

test('generateLoyaltyPoints method calls member queries class', function (): void {
    $companyId = 1;
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 0,
    ]);

    $loyaltyPoint = LoyaltyPoint::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'sale_id' => 1,
        'loyalty_campaign_id' => 1,
        'points' => 100,
        'available_points' => 100,
        'minimum_spend_amount' => 0,
        'expiry_date' => now()->addDays(30)->format('Y-m-d H:i:s'),
    ]);

    $loyaltyCampaign = LoyaltyCampaign::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'minimum_spend_amount' => 1,
        'loyalty_points' => 50,
        'status' => true,
    ]);

    $loyaltyCampaign->excludedBrands = collect([]);

    Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
        'loyalty_point_expiration_days' => 10,
    ]);

    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $product = commonGetProductDetails(false);
    $product->brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'Test Brand',
        'code' => '12465',
    ]);

    $saleItem->product = $product;

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
    ]);

    $sale->saleItems = collect([$saleItem]);

    $this->mock(LoyaltyCampaignQueries::class, function ($mock) use ($loyaltyCampaign): void {
        $mock->shouldReceive('getActiveLoyaltyCampaignsByCompanyId')
            ->once()
            ->andReturn(collect([$loyaltyCampaign]));
    });

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('increaseLoyaltyPoints')
            ->once();
        $mock->shouldReceive('getByIdWithMembershipAndLoyaltyPoints')
            ->once()
            ->andReturn($member);
    });

    $this->mock(LoyaltyPointQueries::class, function ($mock) use ($loyaltyPoint): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($loyaltyPoint);
    });

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('loadSaleItemsProductAndBrand')
            ->once()
            ->andReturn($sale);
    });

    $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $happenedAt = '2022-01-01 10:10:10';

    $this->generateLoyaltyPointsService->generateLoyaltyPoints(
        $sale->total_amount_paid,
        $member->id,
        $companyId,
        $sale,
        $happenedAt,
    );
});
