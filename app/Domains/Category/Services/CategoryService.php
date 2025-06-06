<?php

declare(strict_types=1);

namespace App\Domains\Category\Services;

use App\Domains\Category\DataObjects\CategoryData;

class CategoryService
{
    public function getCategoryData(array $categoryDetails): CategoryData
    {
        $description = $categoryDetails['description'] ?? null;

        $status = array_key_exists('status', $categoryDetails) ? $categoryDetails['status'] : 'No';

        $isAvailableInEcommerce = array_key_exists(
            'is_available_in_ecommerce',
            $categoryDetails
        ) ? $categoryDetails['is_available_in_ecommerce'] : 'No';

        $isDisplayOnMenu = array_key_exists(
            'is_display_on_menu',
            $categoryDetails
        ) ? $categoryDetails['is_display_on_menu'] : 'No';

        return new CategoryData(
            name: (string) $categoryDetails['name'],
            code: $categoryDetails['code'] ? (string) $categoryDetails['code'] : null,
            parent_category_id: null,
            description: $description,
            status: 'Yes' === $status,
            is_available_in_ecommerce: 'Yes' === $isAvailableInEcommerce,
            is_display_on_menu: 'Yes' === $isDisplayOnMenu,
            square_image: null,
            portrait_images: [],
            landscape_images: [],
        );
    }
}
