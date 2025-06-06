<?php

declare(strict_types=1);

namespace App\Domains\Category\Services;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryExportService
{
    public array $categories = [];

    public int $headerCounter = 0;

    public function exportCategory(Collection $categories, string $filename): array
    {
        $categories->transform(function ($category): array {
            $childArray = [];

            $this->categories = [];
            $childArray = $this->checkSubCategory($category);

            if ((count($childArray) - 1) > $this->headerCounter) {
                $this->headerCounter = count($childArray) - 1;
            }

            return array_reverse($childArray);
        });

        $columns = [];
        for ($i = 1; $i <= $this->headerCounter; $i++) {
            $columns[] = 'Sub Category';
        }

        return [
            'categories' => $categories,
            'columns' => $columns,
        ];
    }

    private function checkSubCategory(Category $category): array
    {
        $parentCategoryName = $category->name;

        $childCount = $category->children->count();
        if ($childCount > 0) {
            foreach ($category->children as $childCategory) {
                /** @var Category $childCategory */
                $this->checkSubCategory($childCategory);
            }
        }

        $this->categories[] = $parentCategoryName;

        return $this->categories;
    }
}
