<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ProductVariantData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $description,
        public string $upc,
        public ?string $ean,
        public ?string $custom_sku,
        public ?string $manufacturer_sku,
        public ?float $retail_price,
        public ?float $wholesale_price,
        public ?float $staff_price,
        public ?float $minimum_price,
        public ?float $purchase_cost,
        public ?float $online_price,
        public bool $is_temporarily_unavailable,
        public bool $is_available_in_pos,
        public bool $is_available_in_ecommerce,
        public bool $is_sold_as_single_item,
        public ?UploadedFile $thumbnail,
        public ?array $images = [],
        public ?array $tiers = [],
        public ?array $boxes = [],
        public ?array $videos = [],
        public ?array $product_variant_values = [],
        public ?array $sale_channel_ids = [],
        public ?int $height = 0,
        public ?int $width = 0,
        public ?int $weight = 0,
    ) {
    }
}
