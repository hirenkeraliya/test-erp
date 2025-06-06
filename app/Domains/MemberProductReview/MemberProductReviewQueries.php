<?php

declare(strict_types=1);

namespace App\Domains\MemberProductReview;

use App\Models\MemberProductReview;

class MemberProductReviewQueries
{
    public function addNew(array $memberProductReviewData): MemberProductReview
    {
        return MemberProductReview::create($memberProductReviewData);
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $memberProductReviews = MemberProductReview::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($memberProductReviews as $memberProductReview) {
            $memberProductReview->member_id = $newMemberId;
            $memberProductReview->save();
        }
    }

    public function updateProductId(int $oldProductId, int $newProductId): void
    {
        $memberProductReviews = MemberProductReview::query()
            ->select('id', 'product_id')
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($memberProductReviews as $memberProductReview) {
            $memberProductReview->product_id = $newProductId;
            $memberProductReview->save();
        }
    }
}
