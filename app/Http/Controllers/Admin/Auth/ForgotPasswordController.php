<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\Jobs\ForgotPasswordEmailJob;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected AdminQueries $adminQuery
    ) {
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
        ]);

        $admin = $this->adminQuery->fetchAdminByUsername($request->input('username'));

        if ($admin instanceof Admin) {
            if (null === $admin->employee?->email) {
                return back()->with([
                    'error' => 'Please contact the admin to set up your email address!',
                ]);
            }

            /** @var string $forgotPasswordToken */
            $forgotPasswordToken = $admin->forgot_password_token;
            ForgotPasswordEmailJob::dispatch($admin->id, $admin->employee->company_id, $forgotPasswordToken)->onQueue(
                config('horizon.default_queue_name')
            );
        }

        return back()->with([
            'success' => 'If an account with the provided email address exists, you will receive an email.',
        ]);
    }
}
