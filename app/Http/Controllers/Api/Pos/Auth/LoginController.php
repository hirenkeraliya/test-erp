<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos\Auth;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\DataObjects\CashierLoginData;
use App\Domains\Cashier\Resources\CashierBasicDetailsResource;
use App\Domains\Common\DataObjects\UrlFromConfigurationKeyDataForPos;
use App\Domains\Common\Enums\LogoutEnums;
use App\Domains\Company\Resources\CompanyBasicDetailForMeResource;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Counter\Resources\PosCounterMeApiResource;
use App\Domains\Store\Resources\StoreBasicDetailsResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use Dotenv\Dotenv;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    public function login(CashierLoginData $cashierLoginData, CashierQueries $cashierQueries): array
    {
        $cashier = $cashierQueries->checkCompanyAndGetByUsernameWithEmployeeDetails($cashierLoginData->username);
        if (! $cashier instanceof Cashier || ! $cashier->getEmployee()?->getStatus()) {
            abort(412, 'Your account is inactive. Please contact Admin/Store Manager.');
        }

        if ($cashier->getPin() === $cashierLoginData->pin) {
            $cashierDetails = [
                'username' => $cashierLoginData->username,
                'last_login_at' => $cashier->last_login_at ?? null,
            ];

            $cashierQueries->updateLastLoginTime($cashier);
            $token = $cashierQueries->generateToken($cashier);

            return [
                'cashier' => $cashierDetails,
                'token' => $token,
            ];
        }

        abort(412, 'Credentials are incorrect.');
    }

    public function logout(Request $request): void
    {
        $validatedData = $request->validate([
            'logout_from_all' => ['nullable', 'in:'. LogoutEnums::getValues()],
        ], [
            'logout_from_all.in' => 'The logout from all field must be either 0 or 1.',
        ]);

        $logoutFromAll = $validatedData['logout_from_all'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if ((int) $logoutFromAll === LogoutEnums::LOGOUT_FROM_ALL->value) {
            $cashier->tokens()->delete();
        } else {
            /** @var PersonalAccessToken $currentAccessToken */
            $currentAccessToken = $cashier->currentAccessToken();
            $tokenId = $currentAccessToken->id;
            $cashier->revokeCurrentToken($tokenId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function me(Request $request, CashierQueries $cashierQueries): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $cashier = $cashierQueries->loadDetailsForMeApiEndpoint($cashier);

        $location = null;
        $counterUpdate = null;

        if ($cashier->getCounterUpdateId()) {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $cashier->getCounterUpdate();

            /** @var Counter $counter */
            $counter = $counterUpdate->getCounter();

            $location = $counter->location;
        }

        $roundOffConfiguration = resolve(RoundOffConfiguration::class);

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();
        /** @var Company $company */
        $company = $employee->company;

        return [
            'cashier' => new CashierBasicDetailsResource($cashier),
            'round_off_configuration' => $roundOffConfiguration->getList(),
            'store' => $location ? new StoreBasicDetailsResource($location) : null,
            'location' => $location ? new StoreBasicDetailsResource($location) : null,
            'counter' => $counterUpdate instanceof CounterUpdate ?
                new PosCounterMeApiResource($counterUpdate) : null,
            'company' => new CompanyBasicDetailForMeResource($company),
        ];
    }

    /**
     * @return array<string, string>|null[]
     */
    public function getUrlFromConfigurationKey(
        UrlFromConfigurationKeyDataForPos $urlFromConfigurationKeyDataForPos
    ): array {
        $configurationKey = $urlFromConfigurationKeyDataForPos->configuration_key;

        $listOfWebAppUrlsAndKeys = Dotenv::parse(config('services.list_of_web_app_urls_and_keys'));

        if (! array_key_exists($configurationKey, $listOfWebAppUrlsAndKeys)) {
            abort(412, 'The specified configuration key is invalid.');
        }

        return [
            'url' => $listOfWebAppUrlsAndKeys[$configurationKey],
        ];
    }
}
