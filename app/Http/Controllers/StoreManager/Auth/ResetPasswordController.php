<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager\Auth;

use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function __construct(
        protected StoreManagerQueries $storeManagerQuery
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

        $storeManager = $this->storeManagerQuery->getByToken($request->input('token'));

        $this->storeManagerQuery->resetPassword($storeManager, $request->input('password'));

        return to_route('store_manager.login')->with('success', 'Password changed successfully.');
    }
}
