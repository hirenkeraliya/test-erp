<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\CommonFunctions;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
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

        if (Auth::guard('super_admin')->attempt($credentials, (bool) $request->input('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('super_admin.dashboard'))->with('success', 'Logged in successfully.');
        }

        throw new RedirectBackWithErrorException('Credentials are incorrect.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        CommonFunctions::forgotAllSession();

        return to_route('super_admin.login')->with('success', 'You have been successfully logged out.');
    }
}
