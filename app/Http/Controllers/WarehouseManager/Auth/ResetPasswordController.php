<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager\Auth;

use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function __construct(
        protected WarehouseManagerQueries $warehouseManagerQuery
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

        $warehouseManager = $this->warehouseManagerQuery->getByToken($request->input('token'));

        $this->warehouseManagerQuery->resetPassword($warehouseManager, $request->input('password'));

        return to_route('warehouse_manager.login')->with('success', 'Password changed successfully.');
    }
}
