<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Domains\User\UserQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserResetPasswordController extends Controller
{
    public function __construct(
        protected UserQueries $userQueries
    ) {
    }

    public function index(string $token): Response
    {
        return Inertia::render('guest/UserResetPassword', [
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $admin = $this->userQueries->checkResetPasswordToken($request->input('token'));

        $this->userQueries->resetPassword($admin, $request->input('password'));

        return to_route('admin.user_password_changed')->with('success', 'Password changed successfully.');
    }

    public function passwordChanged(): Response
    {
        return Inertia::render('guest/passwordChanged');
    }
}
