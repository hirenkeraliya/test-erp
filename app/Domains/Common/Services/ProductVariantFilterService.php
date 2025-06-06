<?php

declare(strict_types=1);

namespace App\Domains\Common\Services;

class ProductVariantFilterService
{
    public function filterByDepartmentAndBrandIds(string $field, array $ids): mixed
    {
        return function ($query) use ($field, $ids): void {
            if (config('app.product_variant')) {
                $query->select('products.id')
                    ->from('products')
                    ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                    ->whereIntegerInRaw('master_products.' . $field, $ids);
            } else {
                $query->select('products.id')
                    ->from('products')
                    ->whereIntegerInRaw($field, $ids);
            }
        };
    }

    public function filterByDepartmentAndBrandAndArticleNumber(string $field, int|string $id): mixed
    {
        return function ($query) use ($field, $id): void {
            if (config('app.product_variant')) {
                $query->select('products.id')
                    ->from('products')
                    ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                    ->where('master_products.' . $field, $id);
            } else {
                $query->select('products.id')
                    ->from('products')
                    ->where($field, $id);
            }
        };
    }

    public function filterIsNonInventoryOrSellingItem(string $field): mixed
    {
        return function ($query) use ($field): void {
            if (config('app.product_variant')) {
                $query->select('id', 'master_product_id')
                    ->whereHas('masterProduct', function ($query) use ($field): void {
                        $query->where($field, false);
                    });
            } else {
                $query->select('id')
                    ->where($field, false);
            }
        };
    }
}
