<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Models\AssemblyChildMasterProduct;
use App\Models\AssemblyChildProduct;
use App\Models\BoxProductLoyaltyPoint;
use App\Models\Category;
use App\Models\MasterProduct;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $product = $this->resource;

        /** @var Collection $categories */
        $categories = $product->categories;

        /** @var Collection $tags */
        $tags = $product->tags;

        /** @var Collection $tiers */
        $tiers = $product->tiers;

        /** @var Collection $productBoxes */
        $productBoxes = $product->boxes;

        /** @var Collection $assemblyChildProducts */
        $assemblyChildProducts = $product->assemblyChildProducts;

        /** @var Collection $mergeProductTransactions */
        $mergeProductTransactions = $product->mergeProductTransactions;

        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        $masterProductData = null;
        if ($masterProduct instanceof MasterProduct) {
            /** @var Collection $categories */
            $categories = $masterProduct->categories;

            /** @var Collection $tags */
            $tags = $masterProduct->tags;

            /** @var Collection $assemblyChildMasterProducts */
            $assemblyChildMasterProducts = $masterProduct->assemblyChildMasterProducts;

            $masterProductData = [
                'id' => $masterProduct->id,
                'name' => $masterProduct->name,
                'code' => $masterProduct->code,
                'description' => $masterProduct->description,
                'department' => $masterProduct->department,
                'brand' => $masterProduct->brand,
                'unit_of_measure' => $masterProduct->unitOfMeasure,
                'article_number' => $masterProduct->article_number,
                'type_id' => [
                    'id' => $masterProduct->type_id,
                    'name' => $masterProduct->type_id ? ProductTypes::getFormattedCaseName(
                        $masterProduct->type_id
                    ) : null,
                    'key' => $masterProduct->type_id ? ProductTypes::getCaseNameByValue($masterProduct->type_id) : null,
                ],
                'has_batch' => $masterProduct->has_batch,
                'status' => $masterProduct->status ? Statuses::getFormattedArrayForPos($masterProduct->status) : null,
                'is_non_inventory' => $masterProduct->is_non_inventory,
                'is_non_selling_item' => $masterProduct->is_non_selling_item,
                'categories' => $categories->map(function ($category): array {
                    /** @var Category $productCategory */
                    $productCategory = $category;

                    return [
                        'id' => $productCategory->id,
                        'name' => $productCategory->name,
                    ];
                }),
                'tags' => $tags->map(function ($tag): array {
                    /** @var Tag $productTag */
                    $productTag = $tag;

                    return [
                        'id' => $productTag->id,
                        'name' => $productTag->getName(),
                    ];
                }),
                'assembly_child_master_products' => $assemblyChildMasterProducts->map(
                    function ($assemblyChildProduct): array {
                        /** @var AssemblyChildMasterProduct $assemblyProduct */
                        $assemblyProduct = $assemblyChildProduct;

                        /** @var MasterProduct $masterProduct */
                        $masterProduct = $assemblyProduct->item;

                        return [
                            'id' => $assemblyProduct->id,
                            'master_product_id' => $masterProduct->id,
                            'master_product_name' => $masterProduct->name,
                            'units' => $assemblyProduct->units,
                        ];
                    }
                ),
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'compound_product_name' => $product->compound_product_name,
            'code' => $product->code,
            'unit_of_measure' => $product->unitOfMeasure,
            'season' => $product->season,
            'department' => $product->department,
            'sub_department' => $product->sub_department_id ? SubDepartments::getCaseNameByValue(
                $product->sub_department_id
            ) : null,
            'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
            'color' => $product->color,
            'size' => $product->size,
            'style' => $product->style,
            'brand' => $product->brand,
            'upc' => $product->upc,
            'ean' => $product->ean,
            'custom_sku' => $product->custom_sku,
            'manufacturer_sku' => $product->manufacturer_sku,
            'article_number' => $product->article_number,
            'price' => (float) $product->retail_price,
            'franchise_price_1' => (float) $product->franchise_price_1,
            'franchise_price_2' => (float) $product->franchise_price_2,
            'franchise_price_3' => (float) $product->franchise_price_3,
            'wholesale_price' => (float) $product->wholesale_price,
            'company_or_tender_price' => (float) $product->company_or_tender_price,
            'branch_price' => (float) $product->branch_price,
            'minimum_price' => (float) $product->minimum_price,
            'original_capital_price' => (float) $product->original_capital_price,
            'capital_price' => (float) $product->capital_price,
            'staff_price' => (float) $product->staff_price,
            'is_temporarily_unavailable' => $product->is_temporarily_unavailable,
            'has_batch' => $product->has_batch,
            'type_id' => [
                'id' => $product->type_id,
                'name' => ProductTypes::getFormattedCaseName($product->type_id),
                'key' => ProductTypes::getCaseNameByValue($product->type_id),
            ],
            'status' => Statuses::getFormattedArrayForPos($product->status),
            'is_non_inventory' => $product->is_non_inventory,
            'is_non_selling_item' => $product->is_non_selling_item,
            'is_available_in_pos' => $product->is_available_in_pos,
            'is_sold_as_single_item' => $product->is_sold_as_single_item,
            'sell_item_via_derivative' => $product->sell_item_via_derivative,
            'is_warranty' => $product->is_warranty,
            'warranty_month' => $product->warranty_month,
            'stock' => (float) ($product->inventory ? $product->inventory->stock : 0),
            'batch_numbers' => $product->inventory ? $this->getBatchNumbers($product->inventory->inventoryUnits) : null,
            'serial_numbers' => $this->getSerialNumbers($product->serialNumbers),
            'categories' => $categories->map(function ($category): array {
                /** @var Category $productCategory */
                $productCategory = $category;

                return [
                    'id' => $productCategory->id,
                    'name' => $productCategory->name,
                ];
            }),
            'tags' => $tags->map(function ($tag): array {
                /** @var Tag $productTag */
                $productTag = $tag;

                return [
                    'id' => $productTag->id,
                    'name' => $productTag->getName(),
                ];
            }),
            'loyalty_point_tiers' => $tiers->map(function ($tier): array {
                /** @var ProductLoyaltyPoint $productLoyaltyPoint */
                $productLoyaltyPoint = $tier;

                return [
                    'id' => $productLoyaltyPoint->id,
                    'membership_id' => $productLoyaltyPoint->membership_id,
                    'points' => $productLoyaltyPoint->points,
                ];
            }),
            'product_bundles' => $productBoxes->map(function ($boxProduct): array {
                /** @var PackageType $packageType */
                $packageType = $boxProduct->packageType;
                $boxProductLoyaltyPoints = $boxProduct->boxProductLoyaltyPoints;

                return [
                    'id' => $boxProduct->id,
                    'unit_of_measure_two_id' => $boxProduct->package_type_id, // TODO: Remove after frontend implementation
                    'unit_of_measure_two_name' => $packageType->name,
                    'package_type_id' => $boxProduct->package_type_id,
                    'package_type_name' => $packageType->name,
                    'units' => $boxProduct->units,
                    'retail_price' => $boxProduct->retail_price,
                    'staff_price' => $boxProduct->staff_price,
                    'bundle_product_loyalty_points' => $boxProductLoyaltyPoints->map(
                        function ($tier): array {
                            /** @var BoxProductLoyaltyPoint $boxProductLoyaltyPoint */
                            $boxProductLoyaltyPoint = $tier;

                            return [
                                'id' => $boxProductLoyaltyPoint->id,
                                'membership_id' => $boxProductLoyaltyPoint->membership_id,
                                'points' => $boxProductLoyaltyPoint->points,
                            ];
                        }
                    ),
                    'box_product_loyalty_points' => $boxProductLoyaltyPoints->map(
                        function ($tier): array {
                            /** @var BoxProductLoyaltyPoint $boxProductLoyaltyPoint */
                            $boxProductLoyaltyPoint = $tier;

                            return [
                                'id' => $boxProductLoyaltyPoint->id,
                                'membership_id' => $boxProductLoyaltyPoint->membership_id,
                                'points' => $boxProductLoyaltyPoint->points,
                            ];
                        }
                    ),
                ];
            }),
            'product_boxes' => $productBoxes->map(function ($boxProduct): array {
                /** @var PackageType $packageType */
                $packageType = $boxProduct->packageType;
                $boxProductLoyaltyPoints = $boxProduct->boxProductLoyaltyPoints;

                return [
                    'id' => $boxProduct->id,
                    'unit_of_measure_two_id' => $boxProduct->package_type_id, // TODO: Remove after frontend implementation
                    'unit_of_measure_two_name' => $packageType->name,
                    'package_type_id' => $boxProduct->package_type_id,
                    'package_type_name' => $packageType->name,
                    'units' => $boxProduct->units,
                    'retail_price' => $boxProduct->retail_price,
                    'staff_price' => $boxProduct->staff_price,
                    'bundle_product_loyalty_points' => $boxProductLoyaltyPoints->map(
                        function ($tier): array {
                            /** @var BoxProductLoyaltyPoint $boxProductLoyaltyPoint */
                            $boxProductLoyaltyPoint = $tier;

                            return [
                                'id' => $boxProductLoyaltyPoint->id,
                                'membership_id' => $boxProductLoyaltyPoint->membership_id,
                                'points' => $boxProductLoyaltyPoint->points,
                            ];
                        }
                    ),
                    'box_product_loyalty_points' => $boxProductLoyaltyPoints->map(
                        function ($tier): array {
                            /** @var BoxProductLoyaltyPoint $boxProductLoyaltyPoint */
                            $boxProductLoyaltyPoint = $tier;

                            return [
                                'id' => $boxProductLoyaltyPoint->id,
                                'membership_id' => $boxProductLoyaltyPoint->membership_id,
                                'points' => $boxProductLoyaltyPoint->points,
                            ];
                        }
                    ),
                ];
            }),
            'assembly_child_products' => $assemblyChildProducts->map(function ($assemblyChildProduct): array {
                /** @var AssemblyChildProduct $assemblyProduct */
                $assemblyProduct = $assemblyChildProduct;

                /** @var Product $product */
                $product = $assemblyProduct->product;

                return [
                    'id' => $assemblyProduct->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'units' => $assemblyProduct->units,
                ];
            }),
            'old_upc' => $mergeProductTransactions->pluck('oldProduct.upc')->toArray(),
            'master_product' => $masterProductData,
        ];
    }

    public function getBatchNumbers(Collection $inventoryUnits): Collection
    {
        return $inventoryUnits->groupBy('batch.number')
            ->map(fn ($inventoryUnit): array => [
                'batch_number' => $inventoryUnit[0]->batch?->number,
                'stock' => (float) $inventoryUnit->sum('quantity'),
            ])
            ->values();
    }

    public function getSerialNumbers(Collection $serialNumbers): array
    {
        return $serialNumbers->pluck('serial_number')->toArray();
    }
}
