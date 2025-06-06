<?php

declare(strict_types=1);

namespace App\Domains\CustomFieldValue;

use App\Models\CustomFieldValue;
use App\Models\MasterProduct;
use App\Models\Product;

class CustomFieldValueQueries
{
    public function addNew(array $customFieldValueRecord): CustomFieldValue
    {
        return CustomFieldValue::create($customFieldValueRecord);
    }

    public function delete(Product $product): void
    {
        foreach ($product->customFieldValues as $customFieldValue) {
            $customFieldValue->delete();
        }
    }

    public function deleteMasterProduct(MasterProduct $masterProduct): void
    {
        foreach ($masterProduct->customFieldValues as $customFieldValue) {
            $customFieldValue->delete();
        }
    }
}
