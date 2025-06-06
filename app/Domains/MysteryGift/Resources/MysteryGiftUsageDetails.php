<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Resources;

use App\Domains\Product\Services\ProductService;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\MysteryGiftUsage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MysteryGiftUsageDetails extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MysteryGiftUsage $mysteryGiftUsage */
        $mysteryGiftUsage = $this;

        /** @var Product $product */
        $product = $mysteryGiftUsage->product;

        /** @var Member $member */
        $member = $mysteryGiftUsage->member;

        return [
            'used_at' => $mysteryGiftUsage->used_at,
            'product' => $this->getProductDetails($product),
            'member' => $this->getMemberDetails($member),
        ];
    }

    private function getProductDetails(Product $product): array
    {
        $productService = resolve(ProductService::class);

        $masterProductArray = null;
        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct instanceof MasterProduct) {
            $masterProductArray = [
                'id' => $masterProduct->id,
                'name' => $masterProduct->name,
                'article_number' => $masterProduct->article_number,
                'image' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'attributes' => $product->productVariantValues->isNotEmpty() ? $productService->getAttributesArrayForApi(
                $product
            ) : null,
            'article_number' => $product->article_number,
            'color' => $product->color?->name ?? 'N/A',
            'size' => $product->size?->name ?? 'N/A',
            'upc' => $product->upc,
            'image' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'retail_price' => (float) $product->retail_price,
            'master_product' => $masterProductArray,
        ];
    }

    private function getMemberDetails(Member $member): array
    {
        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'mobile' => $member->mobile_number,
        ];
    }
}
