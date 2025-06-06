<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Models\BoxProduct;
use App\Models\PackageType;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BoxProductExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $boxProducts
    ) {
    }

    public function collection(): Collection
    {
        return $this->boxProducts->map(function (BoxProduct $boxProduct): array {
            /** @var Product $product */
            $product = $boxProduct->product;

            /** @var PackageType $packageType */
            $packageType = $boxProduct->packageType;

            return [
                'upc' => $product->upc,
                'package_type_name' => $packageType->name,
                'units' => $boxProduct->units,
                'retail_price' => $boxProduct->retail_price,
                'staff_price' => $boxProduct->staff_price,
            ];
        });
    }

    public function headings(): array
    {
        return ['upc', 'package_type_name', 'units', 'retail_price', 'staff_price'];
    }
}
