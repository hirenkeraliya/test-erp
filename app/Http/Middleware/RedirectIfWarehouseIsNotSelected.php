<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Panel\Enums\PanelDashboardUrls;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfWarehouseIsNotSelected
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! session('warehouse_manager_selected_location_id')) {
            return redirect(PanelDashboardUrls::WAREHOUSE_MANAGER->value);
        }

        if (! session('warehouse_manager_selected_location_company_id')) {
            Auth::guard('warehouse_manager')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->forget(
                ['warehouse_manager_selected_location_id', 'warehouse_manager_selected_location_company_id']
            );

            return redirect('/warehouse-manager');
        }

        return $next($request);
    }
}
