<?php

declare(strict_types=1);

use App\Domains\PromotionChannelReference\PromotionChannelReferenceQueries;
use App\Models\Promotion;
use App\Models\PromotionChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->promotionChannelReferenceQueries = new PromotionChannelReferenceQueries();
});

test('a promotion channel reference can be added', function (): void {
    $promotion = Promotion::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $promotionChannelReferenceRecord = PromotionChannelReference::factory()->make([
        'promotion_id' => $promotion,
        'sale_channel_id' => $saleChannelId,
        'external_promotion_id' => $promotion,
    ]);

    $this->promotionChannelReferenceQueries->addNew($promotionChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(PromotionChannelReference::class, $promotionChannelReferenceRecord->toArray());
});

test('it calls the getByPromotionIdAndSaleChannelId to get the external promotion', function (): void {
    $promotionId = Promotion::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $promotionChannelReference = PromotionChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'promotion_id' => $promotionId,
        'external_promotion_id' => 1,
    ]);

    $response = $this->promotionChannelReferenceQueries->getByPromotionIdAndSaleChannelId(
        $promotionId,
        $saleChannelId
    );

    expect($response)
        ->toHaveKey('id', $promotionChannelReference->getKey())
        ->toHaveKey('promotion_id', $promotionId)
        ->toHaveKey('external_promotion_id', 1);
});
