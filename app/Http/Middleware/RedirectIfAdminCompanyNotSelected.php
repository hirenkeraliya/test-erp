<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Panel\Service\PanelManagementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAdminCompanyNotSelected
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (
            Auth::guard('admin')->check()
            && PanelManagementService::requestForAdmin($request)
            && ! session('admin_company_id')
        ) {
            Auth::guard('admin')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/admin');
        }

        return $next($request);
    }
}
