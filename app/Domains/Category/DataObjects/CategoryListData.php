<?php

declare(strict_types=1);

namespace App\Domains\Category\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CategoryListData extends Data
{
    public function __construct(
        public int $id,
        public int $company_id,
        public ?int $parent_category_id,
        public string $name,
        public ?string $code,
        /** @var ?CategoryListData[]|DataCollection */
        public ?DataCollection $children,
    ) {
    }
}
