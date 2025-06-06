<?php

declare(strict_types=1);

namespace App\Domains\ProductVariantValue;

use App\Models\ProductVariantValue;
use Illuminate\Support\Collection;

class ProductVariantValueQueries
{
    public function addNew(int $productVariantId, int $attributeId, string $value): void
    {
        ProductVariantValue::create([
            'product_id' => $productVariantId,
            'attribute_id' => $attributeId,
            'value' => $value,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,attribute_id,value';
    }

    public function getProductsWithMatchingVariants(int $id, array $values): Collection
    {
        return ProductVariantValue::select('product_id')->whereIn('value', $values)
            ->whereNot('product_id', $id)
            ->groupBy('product_id')
            ->havingRaw('COUNT(DISTINCT value) = ?', [count($values)])
            ->get();
    }

    public function firstOrCreate(array $productVariantValue, int $attributeId, int $productId): void
    {
        ProductVariantValue::firstOrCreate([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'value' => $productVariantValue['value'],
        ]);
    }
}
