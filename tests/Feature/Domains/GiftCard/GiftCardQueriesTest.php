<?php

declare(strict_types=1);

use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Models\Company;
use App\Models\GiftCard;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->giftCardA = GiftCard::factory()->create([
        'company_id' => $this->companyA->id,
        'number' => 'XYZ',
        'status' => GiftCardStatuses::USED->value,
    ]);

    $this->giftCardB = GiftCard::factory()->create([
        'company_id' => $this->companyA->id,
        'status' => GiftCardStatuses::USED->value,
    ]);

    $this->giftCardQueries = new GiftCardQueries();
});

test('Gift Cards can be searched', function (): void {
    GiftCard::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $response = $this->giftCardQueries->listQuery([
        'search_text' => 'XYZ',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => null,
        'type' => null,
        'expiry_date' => null,
        'created_date' => null,
    ], $this->companyA->id);
    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('number', $this->giftCardA->number)
        ->toHaveKey('total_amount', $this->giftCardA->total_amount);
});

test(
    'the getPaginatedList method returns the paginated gift card list',
    function (): void {
        $filterData = [
            'per_page' => 1,
            'after_updated_at' => null,
        ];

        $response = $this->giftCardQueries->getPaginatedList($filterData, $this->companyA->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->giftCardA->id)
            ->toHaveKey('number', $this->giftCardA->number);
    }
);

test('Gift Cards can be added', function (): void {
    $giftCards = GiftCard::factory(2)->make([
        'company_id' => $this->companyA->id,
    ])->toArray();

    $giftCards[0]['type_id'] = GiftCardTypes::SINGLE_USE_ONLY->value;
    $giftCards[1]['type_id'] = GiftCardTypes::SINGLE_USE_ONLY->value;

    $this->giftCardQueries->createMany($giftCards);

    $this->assertDatabaseHas('gift_cards', [
        'company_id' => $this->companyA->id,
        'number' => $giftCards[0]['number'],
    ]);
});

test(' getGiftCardsForExport method returns gift cards as expected', function (): void {
    GiftCard::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $response = $this->giftCardQueries->getGiftCardsForExport([
        'search_text' => 'XYZ',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => null,
        'type' => null,
        'expiry_date' => null,
        'created_date' => null,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('number', $this->giftCardA->number)
        ->toHaveKey('total_amount', $this->giftCardA->total_amount);
});

test('the getByIds method returns the gift cards list as expected', function (): void {
    $giftCard = GiftCard::factory()->create([
        'company_id' => $this->companyA->id,
        'available_amount' => 100,
        'status' => GiftCardStatuses::ACTIVE->value,
    ]);

    $response = $this->giftCardQueries->getByIds([$giftCard->id], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $giftCard->id)
        ->toHaveKey('available_amount', $giftCard->available_amount);
});

test(
    'the decreaseAvailableAmountAndMarkAsUsed method updates the gift card available amount and change the status when gift card is single use only.',
    function (): void {
        $giftCard = GiftCard::factory()->create([
            'available_amount' => 100,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'status' => GiftCardStatuses::ACTIVE->value,
        ]);

        $this->giftCardQueries->decreaseAvailableAmountAndMarkAsUsed($giftCard, 50);

        $this->assertDatabaseHas('gift_cards', [
            'id' => $giftCard->id,
            'available_amount' => 50,
            'status' => GiftCardStatuses::USED->value,
        ]);
    }
);

test(
    'the decreaseAvailableAmountAndMarkAsUsed method updates the gift card available amount and do not change the status when the gift card type is multi-use.',
    function (): void {
        $giftCard = GiftCard::factory()->create([
            'available_amount' => 100,
            'type_id' => GiftCardTypes::MULTIPLE_USES->value,
            'status' => GiftCardStatuses::ACTIVE->value,
        ]);

        $this->giftCardQueries->decreaseAvailableAmountAndMarkAsUsed($giftCard, 50);

        $this->assertDatabaseHas('gift_cards', [
            'id' => $giftCard->id,
            'available_amount' => 50,
            'status' => GiftCardStatuses::ACTIVE->value,
        ]);
    }
);

test('incrementAvailableAmountAndActivate update the available amount and status', function (): void {
    $giftCard = GiftCard::factory()->create([
        'available_amount' => 100,
        'status' => GiftCardStatuses::USED->value,
    ]);

    $this->giftCardQueries->incrementAvailableAmountAndActivate($giftCard->id, 50);

    $this->assertDatabaseHas('gift_cards', [
        'id' => $giftCard->id,
        'available_amount' => 150,
        'status' => GiftCardStatuses::ACTIVE->value,
    ]);
});

test(
    'the markGiftCardsAsExpired method returns the count of gift cards that are updated',
    function (): void {
        $giftCard = GiftCard::factory()->create([
            'status' => GiftCardStatuses::ACTIVE->value,
            'expiry_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->giftCardQueries->markGiftCardsAsExpired();

        $this->assertEquals(1, $response);

        $this->assertDatabaseHas('gift_cards', [
            'id' => $giftCard->id,
            'status' => GiftCardStatuses::EXPIRED->value,
        ]);
    }
);
