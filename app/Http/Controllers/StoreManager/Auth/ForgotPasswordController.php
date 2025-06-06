<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager\Auth;

use App\Domains\StoreManager\Jobs\ForgotPasswordEmailJob;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected StoreManagerQueries $storeManagerQuery
    ) {
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
        ]);

        $storeManager = $this->storeManagerQuery->fetchStoreManagerByUsername($request->input('username'));

        if ($storeManager instanceof StoreManager) {
            if (null === $storeManager->employee?->email) {
                return back()->with([
                    'error' => 'Please contact the admin to set up your email address!',
                ]);
            }

            /** @var string $forgotPasswordToken */
            $forgotPasswordToken = $storeManager->forgot_password_token;
            ForgotPasswordEmailJob::dispatch(
                $storeManager->id,
                $storeManager->employee->company_id,
                $forgotPasswordToken
            )->onQueue(config('horizon.default_queue_name'));
        }

        return back()->with([
            'success' => 'If an account with the provided email address exists, you will receive an email.',
        ]);
    }
}
