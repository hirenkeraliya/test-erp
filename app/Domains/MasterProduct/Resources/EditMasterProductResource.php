<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\Resources;

use App\Domains\Product\Enums\ProductTypes;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EditMasterProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $masterProduct = $this->resource;

        /** @var Collection $categories */
        $categories = $masterProduct->categories;

        /** @var Collection $tags */
        $tags = $masterProduct->tags;

        /** @var Collection $itemVariants */
        $itemVariants = $masterProduct->productVariants;

        /** @var Collection $assemblyChildItems */
        $assemblyChildItems = $masterProduct->assemblyChildMasterProducts;

        return [
            'id' => $masterProduct->id,
            'name' => $masterProduct->name,
            'type_name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
            'code' => $masterProduct->code,
            'description' => $masterProduct->description,
            'unit_of_measure_id' => $masterProduct->unit_of_measure_id,
            'brand_id' => $masterProduct->brand_id,
            'department_id' => $masterProduct->department_id,
            'vendor_id' => $masterProduct->vendor_id,
            'article_number' => $masterProduct->article_number,
            'type_id' => $masterProduct->type_id,
            'has_batch' => $masterProduct->has_batch,
            'is_non_inventory' => $masterProduct->is_non_inventory,
            'is_non_selling_item' => $masterProduct->is_non_selling_item,
            'variant_template_id' => $masterProduct->variant_template_id,
            'assembly_child_master_products' => $assemblyChildItems->map(fn ($assemblyChildItem): array => [
                'units' => $assemblyChildItem->units,
                'child_master_product_id' => $assemblyChildItem->child_master_product_id,
            ]),
            'categories' => $categories->map(function ($category): array {
                /** @var Category $itemCategory */
                $itemCategory = $category;

                return [
                    'id' => $itemCategory->id,
                    'name' => $itemCategory->name,
                ];
            }),
            'tags' => $tags->map(fn ($tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'status' => $masterProduct->status,
            'uploaded_images' => $masterProduct->uploaded_images,
            'uploaded_videos' => $masterProduct->uploaded_videos,
            'thumbnail_url' => $masterProduct->thumbnail_url,
            'custom_field_values' => $masterProduct->custom_field_values,
            'original_created_at' => $masterProduct->original_created_at,
            'variants' => $this->getVariantsDetails($itemVariants),
        ];
    }

    public function getVariantsDetails(Collection $itemVariants): Collection
    {
        return $itemVariants->map(fn ($itemVariant): array => [
            'id' => $itemVariant->id,
            'name' => $itemVariant->name,
            'code' => $itemVariant->code,
            'description' => $itemVariant->description,
            'upc' => $itemVariant->upc,
            'ean' => $itemVariant->ean,
            'custom_sku' => $itemVariant->custom_sku,
            'manufacturer_sku' => $itemVariant->manufacturer_sku,
            'retail_price' => $itemVariant->retail_price,
            'wholesale_price' => $itemVariant->wholesale_price,
            'staff_price' => $itemVariant->staff_price,
            'minimum_price' => $itemVariant->minimum_price,
            'purchase_cost' => $itemVariant->purchase_cost,
            'online_price' => $itemVariant->online_price,
            'is_temporarily_unavailable' => $itemVariant->is_temporarily_unavailable,
            'is_available_in_pos' => $itemVariant->is_available_in_pos,
            'is_available_in_ecommerce' => $itemVariant->is_available_in_ecommerce,
            'is_sold_as_single_item' => $itemVariant->is_sold_as_single_item,
            'uploaded_images' => $itemVariant->getDiskBasedMediaUrls('images'),
            'uploaded_videos' => $itemVariant->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $itemVariant->getDiskBasedFirstMediaUrl('thumbnail'),
            'tiers' => $this->getTiers($itemVariant->tiers),
            'boxes' => $this->getBoxes($itemVariant->boxes),
            'product_variant_values' => $this->getItemVariantValues($itemVariant->productVariantValues),
            'sale_channels' => $itemVariant->saleChannels,
        ]);
    }

    public function getTiers(Collection $tiers): array
    {
        return $tiers->map(fn ($tier): array => [
            'points' => $tier->points,
            'membership_id' => $tier->membership_id,
        ])->toArray();
    }

    public function getBoxes(Collection $boxes): array
    {
        return $boxes->map(fn ($box): array => [
            'units' => $box->units,
            'package_type_id' => $box->package_type_id,
            'retail_price' => $box->retail_price,
            'minimum_price' => $box->minimum_price,
            'staff_price' => $box->staff_price,
            'purchase_cost' => $box->purchase_cost,
            'wholesale_price' => $box->wholesale_price,
            'box_product_loyalty_points' => $this->getBoxItemVariantLoyaltyPoints($box->boxProductLoyaltyPoints),
        ])->toArray();
    }

    public function getBoxItemVariantLoyaltyPoints(Collection $boxProductLoyaltyPoints): array
    {
        return $boxProductLoyaltyPoints->map(fn ($boxProductLoyaltyPoint): array => [
            'points' => $boxProductLoyaltyPoint->points,
            'membership_id' => $boxProductLoyaltyPoint->membership_id,
        ])->toArray();
    }

    public function getItemVariantValues(Collection $itemVariantValues): array
    {
        return $itemVariantValues->map(fn ($itemVariantValue): array => [
            'id' => $itemVariantValue->attribute_id,
            'selected_value' => $itemVariantValue->value,
            'is_required' => $itemVariantValue->attribute->is_required,
        ])->toArray();
    }
}
