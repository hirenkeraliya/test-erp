<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenus\DataObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class DynamicMenuListData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $slug,
        public ?int $parent_id,
        public string $type,
        public ?int $module_id,
        /** @var ?DynamicMenuListData[]|DataCollection */
        public ?DataCollection $children,
    ) {
    }
}
