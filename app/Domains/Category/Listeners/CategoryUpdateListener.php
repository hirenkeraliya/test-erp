<?php

namespace App\Domains\Category\Listeners;

use App\Domains\Category\Events\CategoryUpdateEvent;
use App\Domains\Category\Services\CategoryEcommerceService;
use App\Domains\Category\Services\CategoryWebspertService;

class CategoryUpdateListener
{
    /**
     * Handle the event.
     */
    public function handle(CategoryUpdateEvent $categoryUpdateEvent): void
    {
        $category = $categoryUpdateEvent->category;

        $category->refresh();

        $categoryEcommerceService = resolve(CategoryEcommerceService::class);
        $categoryEcommerceService->updateCategory($category);

        $categoryWebspertService = resolve(CategoryWebspertService::class);
        $categoryWebspertService->updateCategory($category);
    }
}
