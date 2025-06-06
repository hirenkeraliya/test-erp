<?php

declare(strict_types=1);

namespace App\Domains\StoreManagerAuthorizationCodeUsage;

use App\Models\StoreManagerAuthorizationCodeUsage;

class StoreManagerAuthorizationCodeUsageQueries
{
    public function addNew(array $storeManagerAuthorizationCodeUsageData): void
    {
        StoreManagerAuthorizationCodeUsage::create($storeManagerAuthorizationCodeUsageData);
    }
}
