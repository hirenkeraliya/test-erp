<?php

declare(strict_types=1);

use App\Domains\PromoCode\PromotionPromoCodeQueries;
use App\Models\Company;
use App\Models\Promotion;
use App\Models\PromotionPromoCode;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->promotionA = Promotion::factory()->create([
        'name' => 'ABCD',
        'company_id' => $this->companyId,
        'status' => true,
    ]);

    $this->promotionPromoCodeA = PromotionPromoCode::factory()->create([
        'promotion_id' => $this->promotionA->id,
    ]);

    $this->promotionA->promotionPromoCodes = $this->promotionPromoCodeA;

    $this->promotionPromoCodeQueries = new PromotionPromoCodeQueries();
});

test('new promotion promo code can be added', function (): void {
    $this->promotionPromoCodeQueries->addNew($this->promotionA->getKey(), '123456789@testing');

    $this->assertDatabaseHas(PromotionPromoCode::class, [
        'promotion_id' => $this->promotionA->getKey(),
        'promo_code' => '123456789@testing',
    ]);
});

test('it checks the promo code is exist or not', function (): void {
    $responseA = $this->promotionPromoCodeQueries->existsByPromoCode(
        (string) $this->promotionPromoCodeA->promo_code,
        $this->companyId
    );

    expect($responseA)->toBeTrue();

    $responseB = $this->promotionPromoCodeQueries->existsByPromoCode('testing', $this->companyId);

    expect($responseB)->toBeFalse();
});

test('it checks the counts of the promo code is same with the count from the database', function (): void {
    $responseA = $this->promotionPromoCodeQueries->doPromoCodeExists(
        [$this->promotionPromoCodeA->promo_code],
        $this->companyId
    );

    expect($responseA)->toBeTrue();

    $responseB = $this->promotionPromoCodeQueries->doPromoCodeExists(
        [$this->promotionPromoCodeA->promo_code],
        $this->companyId,
        $this->promotionA->getKey()
    );

    expect($responseB)->toBeFalse();
});
