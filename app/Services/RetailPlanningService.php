<?php

declare(strict_types=1);

namespace App\Services;

class RetailPlanningService
{
    public function isConfigured(): bool
    {
        return config('services.retail_planning.is_enabled');
    }
}
