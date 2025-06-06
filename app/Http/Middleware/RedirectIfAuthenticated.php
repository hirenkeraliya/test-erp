<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Panel\Enums\PanelDashboardUrls;
use App\Domains\Panel\Service\PanelManagementService;
use App\Models\Admin;
use App\Services\SsoLoginService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::guard('super_admin')->check() && PanelManagementService::requestForSuperAdmin($request)) {
            return redirect(PanelDashboardUrls::SUPER_ADMIN->value);
        }

        if (Auth::guard('store_manager')->check() && PanelManagementService::requestForStoreManager($request)) {
            return redirect(PanelDashboardUrls::STORE_MANAGER->value);
        }

        if (Auth::guard('warehouse_manager')->check() && PanelManagementService::requestForWarehouseManager($request)) {
            return redirect(PanelDashboardUrls::WAREHOUSE_MANAGER->value);
        }

        if (! Auth::guard('admin')->check()) {
            return $next($request);
        }

        if (! PanelManagementService::requestForAdmin($request)) {
            return $next($request);
        }

        /** @var SsoLoginService $ssoLoginService */
        $ssoLoginService = resolve(SsoLoginService::class);
        if ($ssoLoginService->ssoRequested($request) && $ssoLoginService->ssoRequestedWithValidUrl($request)) {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();
            $redirectUrl = $ssoLoginService->recordEventAndGetRedirectUrl($request, $admin);

            return redirect($redirectUrl);
        }

        return redirect(PanelDashboardUrls::ADMIN->value);
    }
}
