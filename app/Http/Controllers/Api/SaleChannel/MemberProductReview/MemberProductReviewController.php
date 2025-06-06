<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\MemberProductReview;

use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\MemberProductReview\DataObjects\EcommerceMemberProductReviewData;
use App\Domains\MemberProductReview\MemberProductReviewQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class MemberProductReviewController extends Controller
{
    public function customerProductReview(
        EcommerceMemberProductReviewData $ecommerceMemberProductReviewData,
        Request $request
    ): void {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberSaleChannelReferenceQueries = resolve(MemberChannelReferenceQueries::class);
        $memberId = $memberSaleChannelReferenceQueries->getByMemberId(
            $ecommerceMemberProductReviewData->customer_id,
            $saleChannel->id
        );

        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);
        $productId = $productChannelReferenceQueries->getByProductId(
            $ecommerceMemberProductReviewData->product_id,
            $saleChannel->id
        );

        if (! $memberId || ! $productId) {
            abort(404, 'Member or Product not found.');
        }

        $requestData = [
            'company_id' => $saleChannel->company_id,
            'member_id' => $memberId,
            'product_id' => $productId,
            'review' => $ecommerceMemberProductReviewData->review,
            'rating' => $ecommerceMemberProductReviewData->rating,
        ];

        $memberProductReviewQueries = resolve(MemberProductReviewQueries::class);
        $memberProductReviewQueries->addNew($requestData);
    }
}
