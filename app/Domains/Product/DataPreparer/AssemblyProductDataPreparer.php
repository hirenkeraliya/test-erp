<?php

declare(strict_types=1);

namespace App\Domains\Product\DataPreparer;

use App\Models\AssemblyChildMasterProduct;
use App\Models\AssemblyChildProduct;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

class AssemblyProductDataPreparer
{
    public function getAssemblyChildProducts(Collection $assemblyChildProducts): ?array
    {
        if ($assemblyChildProducts->isEmpty()) {
            return [];
        }

        return $assemblyChildProducts->map(function (AssemblyChildProduct $assemblyChildProduct): array {
            /** @var Product $product */
            $product = $assemblyChildProduct->product;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'article_number' => $product->article_number,
            ];
        })->toArray();
    }

    public function getAssemblyChildMasterProducts(Collection $assemblyChildMasterProducts): ?array
    {
        if ($assemblyChildMasterProducts->isEmpty()) {
            return [];
        }

        return $assemblyChildMasterProducts->map(
            function (AssemblyChildMasterProduct $assemblyChildMasterProduct): array {
                /** @var MasterProduct $masterProduct */
                $masterProduct = $assemblyChildMasterProduct->item;

                return [
                    'master_product_id' => $masterProduct->id,
                    'master_product_name' => $masterProduct->name,
                    'article_number' => $masterProduct->article_number,
                ];
            }
        )->toArray();
    }
}
