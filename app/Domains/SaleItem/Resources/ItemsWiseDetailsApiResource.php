<?php

declare(strict_types=1);

namespace App\Domains\SaleItem\Resources;

use App\CommonFunctions;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ItemsWiseDetailsApiResource extends JsonResource
{
    public function __construct(
        $resource,
        protected string $currencySymbol
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleItemOrSaleReturnItem = $this->resource;

        $promoters = collect([]);

        if ($saleItemOrSaleReturnItem instanceof SaleItem) {
            /** @var Collection $promoters */
            $promoters = $saleItemOrSaleReturnItem->promoters;
        }

        if ($saleItemOrSaleReturnItem instanceof SaleReturnItem) {
            /** @var SaleItem $saleReturnsSaleItem */
            $saleReturnsSaleItem = $saleItemOrSaleReturnItem->saleItem;

            /** @var Collection $promoters */
            $promoters = $saleReturnsSaleItem->promoters;
        }

        /** @var Product $product */
        $product = $saleItemOrSaleReturnItem->product;

        /** @var Department $department */
        $department = $product->department;

        /** @var Brand $brand */
        $brand = $product->brand;

        $masterProductArray = null;
        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct instanceof MasterProduct) {
            $masterProductArray = [
                'master_product_name' => $masterProduct->name,
                'master_product_article_number' => (string) $masterProduct->article_number,
                'master_product_department' => $masterProduct->department->name ?? 'N/A',
                'master_product_brand' => $masterProduct->brand->name ?? 'N/A',
            ];
        }

        return [
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_size' => $product->size instanceof Size ? $product->size->getName() : null,
            'product_color' => $product->color instanceof Color ? $product->color->getName() : null,
            'product_department' => $department->name ?? 'N/A',
            'product_article_number' => $product->article_number,
            'product_brand' => $brand->name,
            'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
            'master_product' => $masterProductArray,
            'quantity' => $saleItemOrSaleReturnItem->quantity,
            'amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $this->currencySymbol,
                (float) $saleItemOrSaleReturnItem->total_price_paid,
                $saleItemOrSaleReturnItem instanceof SaleReturnItem
            ),
            'other_promoters' => $this->getPromoters($promoters),
        ];
    }

    public function getPromoters(Collection $promoters): ?array
    {
        if ($promoters->isEmpty()) {
            return null;
        }

        return $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'name' => $employee->getFullName(),
                'code' => $promoter->code,
            ];
        })->toArray();
    }
}
