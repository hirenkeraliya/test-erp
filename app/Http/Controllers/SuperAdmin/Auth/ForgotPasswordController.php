<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\Domains\SuperAdmin\Jobs\ForgotPasswordEmailJob;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $superAdminQueries = resolve(SuperAdminQueries::class);
        $superAdmin = $superAdminQueries->fetchSuperAdminByEmail($request->input('email'));

        if (null !== $superAdmin) {
            ForgotPasswordEmailJob::dispatch($request->only('email'))->onQueue(config('horizon.default_queue_name'));
        }

        return back()->with([
            'success' => 'If an account with the provided email address exists, you will receive an email.',
        ]);
    }
}
