<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Panel\Enums\PanelDashboardUrls;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfStoreIsNotSelected
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! session('store_manager_selected_location_id')) {
            return redirect(PanelDashboardUrls::STORE_MANAGER->value);
        }

        if (! session('store_manager_selected_location_company_id')) {
            Auth::guard('store_manager')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->forget(['store_manager_selected_location_id', 'store_manager_selected_location_company_id']);

            return redirect('/store-manager');
        }

        return $next($request);
    }
}
