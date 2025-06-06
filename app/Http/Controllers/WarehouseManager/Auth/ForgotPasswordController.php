<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager\Auth;

use App\Domains\WarehouseManager\Jobs\ForgotPasswordEmailJob;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected WarehouseManagerQueries $warehouseManagerQuery
    ) {
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
        ]);

        $warehouseManager = $this->warehouseManagerQuery->fetchWarehouseManagerByUsername($request->input('username'));

        if ($warehouseManager instanceof WarehouseManager) {
            if (null === $warehouseManager->employee?->email) {
                return back()->with([
                    'error' => 'Please contact the admin to set up your email address!',
                ]);
            }

            /** @var string $forgotPasswordToken */
            $forgotPasswordToken = $warehouseManager->forgot_password_token;
            ForgotPasswordEmailJob::dispatch(
                $warehouseManager->id,
                $warehouseManager->employee->company_id,
                $forgotPasswordToken
            )->onQueue(config('horizon.default_queue_name'));
        }

        return back()->with([
            'success' => 'If an account with the provided email address exists, you will receive an email.',
        ]);
    }
}
