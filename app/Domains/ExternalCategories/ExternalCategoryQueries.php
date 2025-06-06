<?php

declare(strict_types=1);

namespace App\Domains\ExternalCategories;

use App\Models\ExternalCategory;

class ExternalCategoryQueries
{
    public function addNew(array $externalCategoryData): void
    {
        ExternalCategory::create($externalCategoryData);
    }

    public function getParentCategoryId(int $parentId): int
    {
        $externalCategoryParentId = ExternalCategory::where([
            'external_category_id' => $parentId,
        ])
        ->first();

        if ($externalCategoryParentId) {
            return $externalCategoryParentId->id;
        }

        return 0;
    }
}
