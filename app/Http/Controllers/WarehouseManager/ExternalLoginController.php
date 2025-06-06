<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\ExternalConnection;
use App\Models\WarehouseManager;
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
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getByIdWithExternalConnection($externalCompanyId);

        if (! $externalCompany) {
            abort(412, 'External connection not active.');
        }

        $token = Str::random(10);

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerQueries->updateExternalLoginToken(
            $warehouseManager->id,
            session('warehouse_manager_selected_location_company_id'),
            $token
        );

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;

        $encryptedToken = Crypt::encryptString($warehouseManager->id . '|' . $token . '|' . $externalCompany->id);

        return [
            'url' => $externalConnection->url . '/warehouse-manager/logging?url=' . config(
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
                    $validateData['url'] . '/api/external-connection/warehouse-manager-verify-external-token',
                    [
                        'token' => $validateData['token'],
                    ]
                );

                if ($response->successful()) {
                    $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                    $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
                    $warehouseManager = $warehouseManagerQueries->getByStaffIdAndCompanyId(
                        $data['staff_id'],
                        (int) $data['company_id']
                    );

                    if ($warehouseManager) {
                        Auth::guard('warehouse_manager')->login($warehouseManager);

                        return redirect()->intended(route('warehouse_manager.warehouse_selection'))->with(
                            'success',
                            'You have successfully logged in.'
                        );
                    }

                    return redirect()->intended(route('warehouse_manager.login'))
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

        return redirect()->intended(route('warehouse_manager.login'))->with('error', 'Token Expire. Please try again.');
    }
}
