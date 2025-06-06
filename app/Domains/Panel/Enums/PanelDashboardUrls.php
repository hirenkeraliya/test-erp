<?php

declare(strict_types=1);

namespace App\Domains\Panel\Enums;

enum PanelDashboardUrls: string
{
    // Note: All the models used for panel login must have the `username` column.
    // Used in `app/Http/Middleware/HandleInertiaRequests.php`

    case SUPER_ADMIN = '/super-admin/dashboard';
    case ADMIN = '/admin/dashboard';
    case STORE_MANAGER = '/store-manager/store-selection';
    case WAREHOUSE_MANAGER = '/warehouse-manager/warehouse-selection';
}
