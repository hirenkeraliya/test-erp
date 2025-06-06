<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Models\Membership;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoyaltyPointProductExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $loyaltyPointProducts
    ) {
    }

    public function collection(): Collection
    {
        return $this->loyaltyPointProducts->map(function (ProductLoyaltyPoint $productLoyaltyPoint): array {
            /** @var Product $product */
            $product = $productLoyaltyPoint->product;

            /** @var Membership $membership */
            $membership = $productLoyaltyPoint->membership;

            return [
                'upc' => $product->upc,
                'membership' => $membership->name,
                'loyalty_points' => $productLoyaltyPoint->points,
            ];
        });
    }

    public function headings(): array
    {
        return ['upc', 'membership', 'loyalty_points'];
    }
}
