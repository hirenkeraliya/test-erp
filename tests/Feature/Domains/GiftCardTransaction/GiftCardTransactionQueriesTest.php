<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\GiftCardTransaction\Enums\GiftCardTransactionTypes;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Models\Company;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\SalePayment;
use App\Models\VoidSale;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->giftCard = GiftCard::factory()->create([
        'company_id' => $this->companyA->id,
        'number' => 'XYZ',
    ]);

    $this->giftCardTransactionA = GiftCardTransaction::factory()->create([
        'gift_card_id' => $this->giftCard->id,
        'type_id' => GiftCardTransactionTypes::USED->value,
        'amount' => '-100',
    ]);

    $this->giftCardTransactionQueries = new GiftCardTransactionQueries();
});

test('Gift Card transaction can be added', function (): void {
    $salePaymentId = SalePayment::factory()->create()->id;

    $this->giftCardTransactionQueries->addNew(
        $this->giftCard,
        $salePaymentId,
        ModelMapping::SALE_PAYMENT->name,
        100,
        GiftCardTransactionTypes::USED->value
    );

    $this->assertDatabaseHas('gift_card_transactions', [
        'gift_card_id' => $this->giftCard->id,
        'type_id' => GiftCardTransactionTypes::USED->value,
        'amount' => '-100',
    ]);
});

test(
    'the getBySaleId method returns the gift card transaction data with morph relation data',
    function (): void {
        $salePayment = SalePayment::factory()->create();

        $this->giftCardTransactionA->affected_by_id = $salePayment->id;
        $this->giftCardTransactionA->affected_by_type = ModelMapping::SALE_PAYMENT->name;
        $this->giftCardTransactionA->save();
        $this->giftCardTransactionA->affectedBy = $salePayment;

        $response = $this->giftCardTransactionQueries->getBySaleId($salePayment->sale_id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->giftCardTransactionA->id)
            ->toHaveKey('gift_card_id', $this->giftCard->id)
            ->toHaveKey('affected_by_id', $salePayment->id);
    }
);

test('Gift Card transaction can be added for void sale', function (): void {
    $voidSaleId = VoidSale::factory()->create()->id;

    $this->giftCardTransactionQueries->addNewForVoidSale(
        $this->giftCard->id,
        $voidSaleId,
        GiftCardTransactionTypes::VOID_SALE->value,
        100
    );

    $this->assertDatabaseHas('gift_card_transactions', [
        'gift_card_id' => $this->giftCard->id,
        'type_id' => GiftCardTransactionTypes::VOID_SALE->value,
        'amount' => '100',
    ]);
});
