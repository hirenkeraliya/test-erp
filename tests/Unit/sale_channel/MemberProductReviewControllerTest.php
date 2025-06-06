<?php

declare(strict_types=1);

use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\MemberProductReview\DataObjects\EcommerceMemberProductReviewData;
use App\Domains\MemberProductReview\MemberProductReviewQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Http\Controllers\Api\SaleChannel\MemberProductReview\MemberProductReviewController;
use App\Models\Member;
use App\Models\MemberProductReview;
use App\Models\Product;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('customer product review successfully creates review', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
        'mobile_number' => '1234567890',
        'email' => 'test@gmail.com',
    ]);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
    ]);

    $data = new EcommerceMemberProductReviewData(
        product_id: $product->id,
        customer_id: 1,
        review: 'best product',
        rating: 4,
    );

    $this->mock(MemberChannelReferenceQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByMemberId')
            ->once()
            ->andReturn($member->id);
    });

    $this->mock(ProductChannelReferenceQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getByProductId')
            ->once()
            ->andReturn($product->id);
    });

    $requestData = [
        'company_id' => $companyId,
        'member_id' => $member->id,
        'product_id' => $product->id,
        'review' => 'best product',
        'rating' => 4,
    ];

    $memberProductReviewQueries = $this->mock(MemberProductReviewQueries::class);
    $memberProductReviewQueries->shouldReceive('addNew')
        ->once()
        ->with($requestData)
        ->andReturn(new MemberProductReview());

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberProductReviewController = new MemberProductReviewController($memberProductReviewQueries);
    $memberProductReviewController->customerProductReview($data, $request);
});

test('customer product review throws 404 when member not found', function (): void {
    $companyId = 1;

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
    ]);

    $data = new EcommerceMemberProductReviewData(
        product_id: $product->id,
        customer_id: 999,
        review: 'best product',
        rating: 4,
    );

    $this->mock(MemberChannelReferenceQueries::class, function ($mock): void {
        $mock->shouldReceive('getByMemberId')
            ->once()
            ->andReturn(null);
    });

    $this->mock(ProductChannelReferenceQueries::class, function ($mock): void {
        $mock->shouldReceive('getByProductId')
            ->once()
            ->andReturn(null);
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberProductReviewController = new MemberProductReviewController();
    $memberProductReviewController->customerProductReview($data, $request);
})->throws(HttpException::class, 'Member or Product not found.');
