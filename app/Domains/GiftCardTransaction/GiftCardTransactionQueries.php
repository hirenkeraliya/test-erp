<?php

declare(strict_types=1);

namespace App\Domains\GiftCardTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use Illuminate\Support\Collection;

class GiftCardTransactionQueries
{
    public function addNew(
        GiftCard $giftCard,
        int $affectedById,
        string $affectedByType,
        float $paymentAmount,
        int $giftCardTransactionType,
    ): void {
        GiftCardTransaction::create([
            'gift_card_id' => $giftCard->id,
            'affected_by_id' => $affectedById,
            'affected_by_type' => $affectedByType,
            'type_id' => $giftCardTransactionType,
            'amount' => (float) ('-' . $paymentAmount),
        ]);
    }

    public function getBySaleId(int $saleId): Collection
    {
        return GiftCardTransaction::select('id', 'gift_card_id', 'affected_by_id', 'affected_by_type')
            ->with('affectedBy:' . $this->getMorphAffectedByBasicColumns())
            ->whereHasMorph(
                'affectedBy',
                [ModelMapping::SALE_PAYMENT->name],
                function ($query) use ($saleId): void {
                    $query->where('sale_id', $saleId);
                }
            )->get();
    }

    public function addNewForVoidSale(
        int $giftCardId,
        int $voidSaleId,
        int $giftCardTransactionType,
        float $paymentAmount,
    ): void {
        GiftCardTransaction::create([
            'gift_card_id' => $giftCardId,
            'affected_by_id' => $voidSaleId,
            'affected_by_type' => ModelMapping::VOID_SALE->name,
            'type_id' => $giftCardTransactionType,
            'amount' => $paymentAmount,
        ]);
    }

    private function getMorphAffectedByBasicColumns(): string
    {
        return 'id,sale_id,amount';
    }
}
