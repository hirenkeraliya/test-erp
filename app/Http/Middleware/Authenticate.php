<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Panel\Service\PanelManagementService;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if (PanelManagementService::requestForAdmin($request)) {
            return route('admin.login');
        }

        if (PanelManagementService::requestForSuperAdmin($request)) {
            return route('super_admin.login');
        }

        if (PanelManagementService::requestForStoreManager($request)) {
            return route('store_manager.login');
        }

        if (PanelManagementService::requestForWarehouseManager($request)) {
            return route('warehouse_manager.login');
        }

        return null;
    }
}
