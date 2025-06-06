<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Models\MysteryGiftUsage;

class MysteryGiftUsagesQueries
{
    public function addNew(array $mysteryGiftData): void
    {
        $data = $mysteryGiftData;

        MysteryGiftUsage::create($data);
    }

    public function existsByCouponCode(string $couponCode): bool
    {
        return MysteryGiftUsage::whereCaseSensitive('coupon_code', $couponCode)
            ->exists();
    }

    public function getDetailsByCouponCode(string $promoCode): ?MysteryGiftUsage
    {
        $productQueries = resolve(ProductQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return MysteryGiftUsage::query()
            ->select('id', 'product_id', 'member_id', 'used_at')
            ->with([
                'product:' . $productQueries->getBasicColumnNames(),
                'member:' . $memberQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->whereNotNull('product_id')
            ->where('coupon_code', $promoCode)
            ->first();
    }

    public function getDetailsByCouponCodeOnlyNotUsedAt(string $promoCode): ?MysteryGiftUsage
    {
        return MysteryGiftUsage::query()
            ->select('id')
            ->whereNull('used_at')
            ->where('coupon_code', $promoCode)
            ->first();
    }

    public function updateUsedAt(MysteryGiftUsage $mysteryGiftUsage, string $usedAt): void
    {
        $mysteryGiftUsage->used_at = $usedAt;
        $mysteryGiftUsage->save();
    }
}
