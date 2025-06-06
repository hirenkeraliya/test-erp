<?php

declare(strict_types=1);

namespace App\Domains\Product\DataPreparer;

use App\Models\BoxProduct;
use App\Models\PackageType;

class BoxProductDataPreparer
{
    public function getBoxProducts(BoxProduct $boxProduct): ?array
    {
        /** @var PackageType $packageType */
        $packageType = $boxProduct->packageType;

        return [
            'id' => $boxProduct->id,
            'package_type_id' => $boxProduct->package_type_id,
            'package_type_name' => $packageType->name,
            'units' => $boxProduct->units,
            'retail_price' => $boxProduct->retail_price,
            'staff_price' => $boxProduct->staff_price,
        ];
    }
}
