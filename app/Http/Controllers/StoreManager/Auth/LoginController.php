<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager\Auth;

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember_me' => ['boolean', 'exclude'],
        ]);

        if (Auth::guard('store_manager')->attempt($credentials, (bool) $request->input('remember'))) {
            /** @var StoreManager $storemanager */
            $storemanager = Auth::guard('store_manager')->user();

            $employeeQueries = resolve(EmployeeQueries::class);
            $storemanager->load([
                'employee' => $employeeQueries->checkActiveCompanyAndGetStatusAndCompanyIdColumns(),
            ]);

            $employee = $storemanager->employee;

            if (! $employee || ! $employee->getStatus()) {
                $this->doLogout($request);

                throw new RedirectBackWithErrorException('Your account is inactive. Please contact the admin.');
            }

            $request->session()->regenerate();

            return to_route('store_manager.store_selection');
        }

        throw new RedirectBackWithErrorException('Credentials are incorrect.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->doLogout($request);

        return to_route('store_manager.login')->with('success', 'You have been successfully logged out.');
    }

    public function doLogout(Request $request): void
    {
        // Refactored logout code so that it can be (re-)used across multiple functions.
        Auth::guard('store_manager')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        CommonFunctions::forgotAllSession();
    }
}
