<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\Domains\Category\CategoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PromoCode\PromotionPromoCodeQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Tag\TagQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;

class PromotionCheckRequestService
{
    public function validateLocationIds(int $companyId, array $locationIds): void
    {
        $locationQueries = resolve(LocationQueries::class);

        $allStoresExist = $locationQueries->doAllStoresExist($companyId, $locationIds);

        if (! $allStoresExist) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'One of the selected stores does not match the current company.'
            );
        }
    }

    public function validateTagIds(int $companyId, array $tagIds): void
    {
        $tagQueries = resolve(TagQueries::class);

        $allTagsExist = $tagQueries->doAllTagsExist($companyId, $tagIds);

        if (! $allTagsExist) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'One of the selected tags does not match the current company.'
            );
        }
    }

    public function validateSaleChannelIds(int $companyId, array $saleChannelIds): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $allSaleChannelExist = $saleChannelQueries->doAllSaleChannelExist($companyId, $saleChannelIds);
        if (! $allSaleChannelExist) {
            throw new RedirectBackWithErrorException(
                'One of the selected sale channel does not match the current company.'
            );
        }
    }

    public function validateCategoryIds(int $companyId, array $categoryIds): void
    {
        $categoryQueries = resolve(CategoryQueries::class);

        $allCategoriesExist = $categoryQueries->doAllCategoriesExist($companyId, $categoryIds);

        if (! $allCategoriesExist) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'One of the selected categories does not match the current company.'
            );
        }
    }

    public function validateRegularProductIds(int $companyId, array $regularProductIds): void
    {
        $productQueries = resolve(ProductQueries::class);

        $allProductsExist = $productQueries->doAllProductsExist($companyId, $regularProductIds);

        if (! $allProductsExist) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'One of the selected products does not match the current company.'
            );
        }
    }

    public function validateRegularProductPrice(int $companyId, array $regularProductIds): void
    {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getRetailPriceByIds($companyId, $regularProductIds);

        if ($products->pluck('retail_price')->unique()->count() > 1) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'Some of the selected products have different prices.'
            );
        }
    }

    public function validateBuyProductIds(int $companyId, array $buyProductIds): void
    {
        $productQueries = resolve(ProductQueries::class);

        $allProductsExist = $productQueries->doAllProductsExist($companyId, $buyProductIds);

        if (! $allProductsExist) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'One of the selected buy products does not match with the current company.'
            );
        }
    }

    public function validateGetProductIds(int $companyId, array $getProductIds): void
    {
        $productQueries = resolve(ProductQueries::class);

        $allProductsExist = $productQueries->doAllProductsExist($companyId, $getProductIds);

        if (! $allProductsExist) {
            throw new RedirectWithErrorException(
                'admin.promotions.index',
                'One of the selected get products does not match with the current company.'
            );
        }
    }

    public function validatePromCodes(int $companyId, array $promoCodes, ?int $promotionId = null): void
    {
        $promotionPromoCodeQueries = resolve(PromotionPromoCodeQueries::class);

        $allPromoCodesExist = $promotionPromoCodeQueries->doPromoCodeExists($promoCodes, $companyId, $promotionId);

        if ($allPromoCodesExist) {
            throw new RedirectBackWithErrorException('The Promo code is already present in our records.');
        }
    }
}
