<?php

declare(strict_types=1);

use App\Domains\MemberProductReview\MemberProductReviewQueries;
use App\Models\Member;
use App\Models\MemberProductReview;
use App\Models\Product;

test('a member product review can be added', function (): void {
    $memberProductReviewRecord = MemberProductReview::factory()->make();

    $memberProductReviewQueries = resolve(MemberProductReviewQueries::class);
    $memberProductReviewQueries->addNew($memberProductReviewRecord->toArray());

    $this->assertDatabaseHas('member_product_reviews', [
        'member_id' => $memberProductReviewRecord->member_id,
        'product_id' => $memberProductReviewRecord->product_id,
        'rating' => $memberProductReviewRecord->rating,
        'review' => $memberProductReviewRecord->review,
    ]);
});

test('member product reviews can be updated when member is merged', function (): void {
    $product = Product::factory()->create();

    $oldMember = Member::factory()->create();
    $newMember = Member::factory()->create();

    MemberProductReview::factory()->create([
        'member_id' => $oldMember->id,
        'product_id' => $product->id,
        'review' => 'this is best',
        'rating' => 4,
    ]);

    $memberProductReviewQueries = resolve(MemberProductReviewQueries::class);
    $memberProductReviewQueries->updateMember($oldMember->id, $newMember->id);

    $this->assertDatabaseMissing('member_product_reviews', [
        'member_id' => $oldMember->id,
    ]);

    $this->assertDatabaseHas('member_product_reviews', [
        'member_id' => $newMember->id,
        'product_id' => $product->id,
        'review' => 'this is best',
        'rating' => 4,
    ]);
});

test('member product reviews can be updated when product is merged', function (): void {
    $oldProduct = Product::factory()->create();
    $newProduct = Product::factory()->create();

    $member = Member::factory()->create();

    MemberProductReview::factory()->create([
        'member_id' => $member->id,
        'product_id' => $oldProduct->id,
        'review' => 'this is best',
        'rating' => 4,
    ]);

    $memberProductReviewQueries = resolve(MemberProductReviewQueries::class);
    $memberProductReviewQueries->updateProductId($oldProduct->id, $newProduct->id);

    $this->assertDatabaseMissing('member_product_reviews', [
        'product_id' => $oldProduct->id,
    ]);

    $this->assertDatabaseHas('member_product_reviews', [
        'member_id' => $member->id,
        'product_id' => $newProduct->id,
        'review' => 'this is best',
        'rating' => 4,
    ]);
});
