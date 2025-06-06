<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Models\Brand;
use App\Models\Department;
use App\Models\Product;
use App\Models\SaleChannel;
use App\Models\Season;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class AdminProductListResource extends JsonResource
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

        /** @var Carbon $createdAt */
        $createdAt = $product->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $product->updated_at;

        /** @var ?Vendor $vendor */
        $vendor = $product->vendor;

        /** @var ?Season $season */
        $season = $product->season;

        /** @var Collection $saleChannels */
        $saleChannels = $product->saleChannels;

        [$category, $parentSubcategory, $subSubcategories] = $this->getProductCategories($product);

        /** @var int ?$typeId */
        $typeId = config('app.product_variant') ? $product?->masterProduct?->type_id : $product->type_id;
        $productService = resolve(ProductService::class);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'brand' => $this->getBrand($product),
            'attributes' => $productService->getAttributesArray($product),
            'color' => config('app.product_variant') ? null : $product->color,
            'size' => config('app.product_variant') ? null : $product->size,
            'department' => $this->getDepartment($product),
            'style' => config('app.product_variant') ? null : $product->style,
            'categories' => $this->getCategories($product),
            'upc' => $product->upc,
            'verification_qr_code' => $product->verification_qr_code,
            'article_number' => $this->getArticleNumber($product),
            'retail_price' => (float) $product->retail_price,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'status' => $product->status,
            'images' => $this->preparedImages($product),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            'product_channel_reference' => $product->productChannelReference,
            'original_created_at' => $this->getOriginalCreatedAt($product),
            'created_by' => $this->getCreatedBy($product),
            'last_editor_by' => $product->lastEditorBy?->employee->staff_id,
            'approved_by' => $product->draftProductTransaction?->approvedBy?->employee->staff_id,
            'is_temporarily_unavailable' => $product->is_temporarily_unavailable ? 'Yes' : 'No',
            'ean' => $product->ean ?: 'N/A',
            'custom_sku' => $product->custom_sku ?: 'N/A',
            'manufacturer_sku' => $product->manufacturer_sku ?: 'N/A',
            'type_id' => $typeId ? ProductTypes::getFormattedCaseName($typeId) : 'N/A',
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
            'purchase_cost' => (float) $product->purchase_cost,
            'online_price' => (float) $product->online_price,
            'subcategory_name' => $parentSubcategory,
            'subsubcategory_name' => $subSubcategories->isNotEmpty() ? $subSubcategories->implode(
                'name',
                ' > '
            ) : 'N/A',
            'has_batch' => config(
                'app.product_variant'
            ) ? $product?->masterProduct?->has_batch ?? 'N/A' : $product->has_batch,
            'is_non_inventory' => $this->getNonInventory($product) ? 'Yes' : 'No',
            'is_non_selling_item' => $this->getNonSelling($product) ? 'Yes' : 'No',
            'is_available_in_pos' => $product->is_available_in_pos ? 'Yes' : 'No',
            'is_available_in_ecommerce' => $product->is_available_in_ecommerce ? 'Yes' : 'No',
            'is_sold_as_single_item' => $product->is_sold_as_single_item ? 'Yes' : 'No',
            'sell_item_via_derivative' => $product->sell_item_via_derivative ? 'Yes' : 'No',
            'tags' => $this->getTags($product),
            'vendor' => $vendor instanceof Vendor ? $vendor->name : 'N/A',
            'sale_channels' => $saleChannels->isNotEmpty() ? implode(
                ',',
                $this->getProductSaleChannels($saleChannels)
            ) : 'N/A',
            'unit_of_measure' => config(
                'app.product_variant'
            ) ? $product->masterProduct?->unitOfMeasure?->getName() ?? 'N/A' : $product?->unitOfMeasure?->getName() ?? 'N/A',
            'description' => $product->description ?: 'N/A',
            'season' => $season instanceof Season ? $season->getName() : 'N/A',
            'sub_department' => $product->getSubDepartmentId() ? SubDepartments::getFormattedCaseName(
                $product->getSubDepartmentId()
            ) : 'N/A',
        ];
    }

    public function preparedImages(Product $product): array
    {
        return [
            'image_urls' => $product->getDiskBasedMediaUrls('images'),
            'video_urls' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }

    private function getTags(Product $product): ?string
    {
        $tags = config('app.product_variant')
        ? $product->masterProduct?->tags
            : $product->tags;

        return $tags?->pluck('name')->implode(', ');
    }

    private function getProductSaleChannels(Collection $saleChannels): array
    {
        return $saleChannels->map(function ($saleChannel): string {
            /** @var SaleChannel $productSaleChannel */
            $productSaleChannel = $saleChannel;

            return $productSaleChannel->getName();
        })->toArray();
    }

    /**
     * @return mixed[]
     */
    private function getProductCategories(Product $product): array
    {
        /** @var Collection $categories */
        $categories = config('app.product_variant')
        ? $product->masterProduct?->categories
            : $product->categories;

        $category = 'N/A';
        $parentSubcategory = 'N/A';
        $subSubcategories = collect([]);

        if (null !== $categories) {
            $category = $categories->first();
            $parentSubcategory = $categories->firstWhere('pivot.sort_order', 1)?->name;
            $subSubcategories = $categories->where('pivot.sort_order', '>=', 2);
        }

        return [$category, $parentSubcategory, $subSubcategories];
    }

    private function getCategories(Product $product): ?array
    {
        $categories = config('app.product_variant')
        ? $product->masterProduct?->categories
            : $product->categories;

        return $categories?->map(fn ($category): array => [
            'id' => $category->id,
            'name' => $category->name,
        ])->toArray();
    }

    private function getDepartment(Product $product): ?Department
    {
        return config('app.product_variant') ? $product->masterProduct?->department : $product->department;
    }

    private function getBrand(Product $product): ?Brand
    {
        return config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;
    }

    private function getArticleNumber(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->article_number : $product->article_number;
    }

    private function getCreatedBy(Product $product): ?string
    {
        return config('app.product_variant')
        ? $product->masterProduct?->createdBy?->employee->staff_id
            : $product->createdBy?->employee->staff_id;
    }

    private function getOriginalCreatedAt(Product $product): string
    {
        if ($product->original_created_at) {
            /** @var Carbon $originalCreatedAt */
            $originalCreatedAt = Carbon::createFromFormat('Y-m-d H:i:s', $product->original_created_at);

            return $originalCreatedAt->format('d-m-Y h:i:s A');
        }

        return 'N/A';
    }

    private function getNonInventory(Product $product): ?bool
    {
        return config('app.product_variant') ? $product->masterProduct?->is_non_inventory : $product->is_non_inventory;
    }

    private function getNonSelling(Product $product): ?bool
    {
        return config(
            'app.product_variant'
        ) ? $product->masterProduct?->is_non_selling_item : $product->is_non_selling_item;
    }
}
