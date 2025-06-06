<?php

declare(strict_types=1);

use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Order\Services\GenerateEcommerceLoyaltyPointsService;
use App\Models\Brand;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
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

    $this->generateEcommerceLoyaltyPointsService = new GenerateEcommerceLoyaltyPointsService();
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

        $response = $this->generateEcommerceLoyaltyPointsService->getTotalApplicableLoyaltyPoints(
            100,
            $loyaltyCampaign
        );

        $this->assertEquals(200, $response);
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

        $response = $this->generateEcommerceLoyaltyPointsService->getFinalAmountExcludeByBrandsForOffline(
            100,
            100,
            [100],
            $loyaltyCampaign,
            new CheckOrderEcommerceDetailsService()
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

        $checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();
        $checkOrderEcommerceDetailsService->orderItems = collect([
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

        $checkOrderEcommerceDetailsService->products = collect([$product, $product2]);

        $response = $this->generateEcommerceLoyaltyPointsService->getFinalAmountExcludeByBrandsForOffline(
            100,
            100,
            [
                1 => 50,
                2 => 50,
            ],
            $loyaltyCampaign,
            $checkOrderEcommerceDetailsService
        );
        $this->assertEquals(50, $response);
    }
);

test('setDetails method works as expected', function (): void {
    $this->mock(LoyaltyCampaignQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIds')
            ->once();
    });

    $this->generateEcommerceLoyaltyPointsService->setDetails($this->loyaltyPoints, 1);

    $this->assertTrue($this->generateEcommerceLoyaltyPointsService->loyaltyPointsMismatches->toArray() === []);
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
            GenerateEcommerceLoyaltyPointsService::class,
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
            new CheckOrderEcommerceDetailsService(),
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
        $this->generateEcommerceLoyaltyPointsService->checkUserDetails(null);
    }
)->throws(HttpException::class, 'User is compulsory when generate loyalty point');

test(
    'checkUserDetails return null when member id specify',
    function (): void {
        $response = $this->generateEcommerceLoyaltyPointsService->checkUserDetails(1);
        $this->assertNull($response);
    }
);

test(
    'checkLoyaltyCampaigns method throws an exception when some of the loyalty campaigns are not in our records',
    function (): void {
        $loyaltyCampaign = LoyaltyCampaign::factory()->make([
            'company_id' => 1,
        ]);

        $this->generateEcommerceLoyaltyPointsService->loyaltyCampaigns = collect([$loyaltyCampaign]);
        $this->generateEcommerceLoyaltyPointsService->loyaltyPoints = collect($this->loyaltyPoints);

        $this->generateEcommerceLoyaltyPointsService->checkLoyaltyCampaigns();
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

        $this->generateEcommerceLoyaltyPointsService->loyaltyCampaigns = collect($loyaltyCampaigns);

        $response = $this->generateEcommerceLoyaltyPointsService->getLoyaltyCampaign(1);

        expect($response->toArray())
            ->toHaveKey('id', $loyaltyCampaigns[0]->id)
            ->toHaveKey('company_id', $loyaltyCampaigns[0]->company_id)
            ->toHaveKey('minimum_spend_amount', $loyaltyCampaigns[0]->minimum_spend_amount);
    }
);

test(
    'getLoyaltyCampaigns method call getByIds method of LoyaltyCampaignQueries class',
    function (): void {
        $return = collect([]);
        $this->mock(LoyaltyCampaignQueries::class, function ($mock) use ($return): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($return);
        });

        $response = $this->generateEcommerceLoyaltyPointsService->getLoyaltyCampaigns([], 1);
        $this->assertEquals($return, $response);
    }
);

test(
    'checkMinimumSpendAmount method sets Mismatches when minimum spend amount mismatched',
    function (): void {
        $this->generateEcommerceLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateEcommerceLoyaltyPointsService->checkMinimumSpendAmount(20, 30);
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

        $this->generateEcommerceLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateEcommerceLoyaltyPointsService->checkDateRange(now()->format('Y-m-d H:i:s'), $loyaltyCampaign);
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
        $this->generateEcommerceLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateEcommerceLoyaltyPointsService->checkExpireDate(
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
        $this->generateEcommerceLoyaltyPointsService->loyaltyPointsMismatches = collect([]);

        $this->generateEcommerceLoyaltyPointsService->checkApplicableLoyaltyPoints(10, 20);
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
        $response = $this->generateEcommerceLoyaltyPointsService->checkExcludedBrands($product, new LoyaltyCampaign());

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
        $response = $this->generateEcommerceLoyaltyPointsService->checkExcludedBrands($product, $loyaltyCampaign);

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
        $response = $this->generateEcommerceLoyaltyPointsService->checkExcludedBrands($product, $loyaltyCampaign);

        $this->assertTrue($response);
    }
);

test(
    'updateUserLoyaltyPointsForOffline return null when user details not set',
    function (): void {
        $response = $this->generateEcommerceLoyaltyPointsService->updateUserLoyaltyPointsForOffline(
            null,
            1,
            now()->format('Y-m-d'),
            new Order()
        );

        $this->assertNull($response);
    }
);

test(
    'updateUserLoyaltyPointsForOffline return null when set null in user details ',
    function (): void {
        $response = $this->generateEcommerceLoyaltyPointsService->updateUserLoyaltyPointsForOffline(
            null,
            1,
            now()->format('Y-m-d'),
            new Order()
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
            GenerateEcommerceLoyaltyPointsService::class,
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

        $mock->updateUserLoyaltyPointsForOffline(1, 1, now()->format('Y-m-d'), new Order());
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

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
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

        $this->generateEcommerceLoyaltyPointsService->saveLoyaltyPoints(
            $this->loyaltyPoints[0],
            $member,
            $order,
            now()->format('Y-m-d'),
            100,
            100
        );
    }
);

test(
    'saveGenerateLoyaltyPoints method method calls same class methods when offline loyalty points generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateEcommerceLoyaltyPointsService::class,
            ['updateUserLoyaltyPointsForOffline']
        );

        $mock->expects($this->once())
            ->method('updateUserLoyaltyPointsForOffline');

        $order = new Order();

        $checkOrderEcommerceDetailsService = $this->mock(
            CheckOrderEcommerceDetailsService::class,
            function ($mock): void {
                $mock->companyId = 1;

                $mock->shouldReceive('hasGenerateLoyaltyPoints')
                    ->once()
                    ->andReturn(true);

                $mock->shouldReceive('getHappenedAtFormat')
                    ->once()
                    ->andReturn(now());
            }
        );

        $mock->saveGenerateLoyaltyPoints($checkOrderEcommerceDetailsService, $order, 1);
    }
);

test(
    'saveGenerateLoyaltyPoints method return null when offline loyalty points not generate',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateEcommerceLoyaltyPointsService::class,
            ['updateUserLoyaltyPointsForOffline']
        );

        $mock->expects($this->never())
            ->method('updateUserLoyaltyPointsForOffline');

        $order = new Order();

        $checkOrderEcommerceDetailsService = $this->mock(
            CheckOrderEcommerceDetailsService::class,
            function ($mock): void {
                $mock->companyId = 1;

                $mock->shouldReceive('hasGenerateLoyaltyPoints')
                    ->once()
                    ->andReturn(false);

                $mock->shouldReceive('getHappenedAtFormat')
                    ->never()
                    ->andReturn(now());
            }
        );

        $response = $mock->saveGenerateLoyaltyPoints($checkOrderEcommerceDetailsService, $order, 1);
        $this->assertNull($response);
    }
);

test(
    'saveGenerateLoyaltyPoints method return null when member id is null',
    function (): void {
        $mock = $this->createPartialMock(
            GenerateEcommerceLoyaltyPointsService::class,
            ['updateUserLoyaltyPointsForOffline']
        );

        $mock->expects($this->never())
            ->method('updateUserLoyaltyPointsForOffline');

        $order = new Order();

        $checkOrderEcommerceDetailsService = $this->mock(
            CheckOrderEcommerceDetailsService::class,
            function ($mock): void {
                $mock->companyId = 1;

                $mock->shouldReceive('hasGenerateLoyaltyPoints')
                    ->once()
                    ->andReturn(true);

                $mock->shouldReceive('getHappenedAtFormat')
                    ->never()
                    ->andReturn(now());
            }
        );

        $response = $mock->saveGenerateLoyaltyPoints($checkOrderEcommerceDetailsService, $order, null);
        $this->assertNull($response);
    }
);
