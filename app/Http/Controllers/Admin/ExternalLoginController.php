<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ExternalConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ExternalLoginController extends Controller
{
    public function index(): Response
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAllCompanies();

        return Inertia::render('external_login/Index', [
            'externalCompanies' => $externalCompanies,
        ]);
    }

    public function getExternalLoginDetails(int $externalCompanyId, Request $request): array
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection($externalCompanyId);

        if (! $externalCompany) {
            abort(412, 'External connection not active.');
        }

        $token = Str::random(10);

        $adminQueries = resolve(AdminQueries::class);
        $adminQueries->updateExternalLoginToken($admin->id, session('admin_company_id'), $token);

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $encryptedToken = Crypt::encryptString($admin->id . '|' . $token . '|' . $externalCompany->id);

        return [
            'url' => $externalConnection->url . '/admin/logging?url=' . config(
                'app.url'
            ) . '&token=' . $encryptedToken,
        ];
    }

    public function logging(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'url' => ['required', 'url', 'max:255'],
        ]);

        if ($validator->passes()) {
            $validateData = $validator->validated();

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->get(
                    $validateData['url'] . '/api/external-connection/admin-verify-external-token',
                    [
                        'token' => $validateData['token'],
                    ]
                );

                if ($response->successful()) {
                    $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                    $adminQueries = resolve(AdminQueries::class);
                    $admin = $adminQueries->getByStaffIdAndCompanyId($data['staff_id'], (int) $data['company_id']);

                    if ($admin) {
                        Auth::guard('admin')->login($admin);

                        session([
                            'admin_company_id' => (int) $data['company_id'],
                        ]);

                        return redirect()->intended(route('admin.dashboard'))->with(
                            'success',
                            'You have successfully logged in.'
                        );
                    }

                    return redirect()->intended(route('admin.login'))
                        ->with('error', 'User not found with similar staff id.');
                }
            } catch (Throwable $throwable) {
                Log::channel('external_login')->error('external login failed', [
                    'Error message' => $throwable->getMessage(),
                    'Error code' => $throwable->getCode(),
                    'File' => $throwable->getFile(),
                    'Line' => $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);
            }
        }

        return redirect()->intended(route('admin.login'))->with('error', 'Token Expire. Please try again.');
    }
}
