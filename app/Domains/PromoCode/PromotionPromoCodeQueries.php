<?php

declare(strict_types=1);

namespace App\Domains\PromoCode;

use App\Domains\Promotion\PromotionQueries;
use App\Models\PromotionPromoCode;
use Closure;

class PromotionPromoCodeQueries
{
    public function addNew(int $promotionId, string $promoCode): void
    {
        PromotionPromoCode::create([
            'promotion_id' => $promotionId,
            'promo_code' => $promoCode,
        ]);
    }

    public function existsByPromoCode(string $generatedPromoCode, int $companyId): bool
    {
        $promotionQueries = resolve(PromotionQueries::class);

        return PromotionPromoCode::whereCaseSensitive('promo_code', $generatedPromoCode)
            ->whereHas('promotion', $promotionQueries->filterByCompany($companyId))
            ->exists();
    }

    public function doPromoCodeExists(array $promoCodes, int $companyId, ?int $promotionId = null): bool
    {
        $promotionQueries = resolve(PromotionQueries::class);
        $totalRecords = PromotionPromoCode::whereIn('promo_code', $promoCodes)
            ->whereHas('promotion', $promotionQueries->filterByCompany($companyId))
            ->when(null !== $promotionId, function ($query) use ($promotionId): void {
                $query->whereNot('promotion_id', $promotionId);
            })
            ->count();

        return count($promoCodes) === $totalRecords;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,promotion_id,promo_code';
    }

    public function searchByPromoCode(string $searchText): Closure
    {
        return fn ($query) => $query->where('promo_code', 'like', '%' . $searchText . '%');
    }
}
