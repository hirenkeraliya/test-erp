<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Domains\Admin\AdminQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function __construct(
        protected AdminQueries $adminQuery
    ) {
    }

    public function index(string $token): Response
    {
        return Inertia::render('guest/ResetPassword', [
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $admin = $this->adminQuery->checkResetPasswordToken($request->input('token'));

        $this->adminQuery->resetPassword($admin, $request->input('password'));

        return to_route('admin.login')->with('success', 'Password changed successfully.');
    }
}
