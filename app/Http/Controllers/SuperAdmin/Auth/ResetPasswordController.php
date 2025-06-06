<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function index(string $token): Response
    {
        return Inertia::render('guest/ResetPassword', [
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        /** @var string Illuminate\Auth\Passwords\PasswordBroker\Reset $status */
        $status = Password::broker('super_admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password): void {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        return Password::PASSWORD_RESET === $status
            ? to_route('super_admin.login')->with('success', 'Password reset successfully.')
            : throw new RedirectBackWithErrorException('The password could not be reset. Please try again.');
    }
}
