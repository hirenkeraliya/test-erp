<?php

declare(strict_types=1);

namespace App\Domains\Category\Listeners;

use App\Domains\Category\Events\CategoryCreateEvent;
use App\Domains\Category\Services\CategoryEcommerceService;
use App\Domains\Category\Services\CategoryWebspertService;

class CategoryCreateListener
{
    /**
     * Handle the event.
     */
    public function handle(CategoryCreateEvent $categoryCreateEvent): void
    {
        $category = $categoryCreateEvent->category;

        $category->refresh();

        if (false === $category->is_available_in_ecommerce) {
            return;
        }

        $categoryEcommerceService = resolve(CategoryEcommerceService::class);
        $categoryEcommerceService->createCategory($category);

        $categoryWebspertService = resolve(CategoryWebspertService::class);
        $categoryWebspertService->createCategory($category);
    }
}
