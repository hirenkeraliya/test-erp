<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Services\TwoFactorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TwoFactorController extends Controller
{
    public TwoFactorService $twoFactorService;

    public function __construct()
    {
        $this->twoFactorService = resolve(TwoFactorService::class);
    }

    /**
     * This function is used to show the 2FA Validation page.
     */
    public function showValidationPage(): InertiaResponse
    {
        return Inertia::render('guest/TwoFactorValidation');
    }

    /**
     * This function is used to generate the secret key and QR code for 2FA.
     */
    public function generate2FA(Request $request): JsonResponse
    {
        return $this->twoFactorService->generate2FA($request);
    }

    /**
     * This function is used to Validate OTP at the time of login.
     */
    public function validateOtp(Request $request): RedirectResponse
    {
        return $this->twoFactorService->validateOtp($request);
    }

    /**
     * This function is used to confirm 2FA at the time of setting up 2FA from profile section.
     */
    public function confirm2FA(Request $request): JsonResponse
    {
        return $this->twoFactorService->confirm2FA($request);
    }

    /**
     * This function is used to disable 2FA.
     */
    public function disable2FA(Request $request): void
    {
        $this->twoFactorService->disable2FA($request);
    }
}
