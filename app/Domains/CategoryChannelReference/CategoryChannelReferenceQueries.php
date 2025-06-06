<?php

declare(strict_types=1);

namespace App\Domains\CategoryChannelReference;

use App\Models\CategoryChannelReference;
use Illuminate\Support\Collection;

class CategoryChannelReferenceQueries
{
    public function addNew(array $categoryExternalIdRecords): CategoryChannelReference
    {
        return CategoryChannelReference::create($categoryExternalIdRecords);
    }

    public function getExternalCategoryIdFromCategoryId(int $categoryId): ?CategoryChannelReference
    {
        return CategoryChannelReference::query()
            ->select('id', 'external_category_id')
            ->where('category_id', $categoryId)
            ->first();
    }

    public function getCategoryIdForWebspert(int $categoryId): ?CategoryChannelReference
    {
        return CategoryChannelReference::query()
            ->select('id', 'category_id', 'external_category_id')
            ->where('category_id', $categoryId)
            ->first();
    }

    public function getBySaleChannelIdCategoryId(int $categoryId, int $saleChannelId): ?CategoryChannelReference
    {
        return CategoryChannelReference::select('id', 'sale_channel_id', 'category_id', 'external_category_id')
            ->where('category_id', $categoryId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getBySaleChannelIdCategoryIds(array $categoryIds, int $saleChannelId): Collection
    {
        return CategoryChannelReference::select('id', 'external_category_id')
            ->whereIntegerInRaw('category_id', $categoryIds)
            ->where('sale_channel_id', $saleChannelId)
            ->get();
    }
}
