<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\SsoLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    public function showLoginPage(Request $request, SsoLoginService $ssoLoginService): InertiaResponse
    {
        if (! $ssoLoginService->ssoRequested($request)) {
            return Inertia::render('guest/Login');
        }

        if (! $ssoLoginService->ssoRequestedWithValidUrl($request)) {
            abort(412, 'URL malformed. Please try with correct URL.');
        }

        $queryParams = (array) $request->query();

        return Inertia::render('guest/Login', [
            'intent' => 'sso',
            'redirectBackTo' => $queryParams['redirectBackTo'],
        ]);
    }

    public function login(Request $request): RedirectResponse|Response
    {
        /** @var SsoLoginService $ssoLoginService */
        $ssoLoginService = resolve(SsoLoginService::class);
        if ($ssoLoginService->ssoRequested($request) && ! $ssoLoginService->ssoRequestedWithValidUrl($request)) {
            abort(412, 'URL malformed. Please try with correct URL.');
        }

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean', 'exclude'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, (bool) $request->input('remember'))) {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();

            $employeeQueries = resolve(EmployeeQueries::class);
            $admin->load([
                'employee' => $employeeQueries->checkActiveCompanyAndGetStatusAndCompanyIdColumns(),
            ]);

            $employee = $admin->employee;

            if (! $employee || ! $employee->getStatus()) {
                Auth::guard('admin')->logout();

                throw new RedirectBackWithErrorException('Your account is inactive. Please contact the super admin.');
            }

            session([
                'admin_company_id' => $employee->getCompanyId(),
            ]);

            $request->session()->regenerate();

            if ($ssoLoginService->ssoRequested($request)) {
                $redirectUrl = $ssoLoginService->recordEventAndGetRedirectUrl($request, $admin);

                return Inertia::location($redirectUrl);
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', 'You have successfully logged in.');
        }

        throw new RedirectBackWithErrorException('Credentials are incorrect.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        CommonFunctions::forgotAllSession();

        return to_route('admin.login')->with('success', 'You have been successfully logged out.');
    }
}
