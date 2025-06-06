<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\DataObjects\CashierLoginData;
use App\Domains\Common\DataObjects\UrlFromConfigurationKeyDataForPos;
use App\Domains\Location\Enums\LocationTypes;
use App\Http\Controllers\Api\Pos\Auth\LoginController;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('cashier can login', function (): void {
    [$cashierLoginData, $cashier, $requestDetails] = commonCashierLogin($employeeStatus = true);

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashierLoginData, $cashier): void {
        $mock->shouldReceive('getByUsernameWithEmployeeDetails')
            ->once()
            ->with($cashierLoginData->username)
            ->andReturn($cashier);
        $mock->shouldReceive('updateLastLoginTime')
            ->once()
            ->with($cashier);
        $mock->shouldReceive('generateToken')
            ->once()
            ->with($cashier)
            ->andReturn('123123');
    });

    $adminController = new LoginController($cashierQueries);
    $response = $adminController->login($cashierLoginData, $cashierQueries);
    expect($response)
        ->toHaveKey('cashier.username', $requestDetails['username'])
        ->toHaveKey('cashier.last_login_at', null)
        ->toHaveKey('token', '123123');
});

test("cashier cannot login if cashier's employee status is inactive", function (): void {
    [$cashierLoginData, $cashier, $requestDetails] = commonCashierLogin($employeeStatus = false);

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashierLoginData, $cashier): void {
        $mock->shouldReceive('getByUsernameWithEmployeeDetails')
            ->once()
            ->with($cashierLoginData->username)
            ->andReturn($cashier);
    });

    $adminController = new LoginController($cashierQueries);
    $adminController->login($cashierLoginData, $cashierQueries);
})->throws(HttpException::class, 'Your account is inactive. Please contact Admin/Store Manager.');

test('cashier cannot login with invalid credentials', function (): void {
    [$cashierLoginData, $cashier, $requestDetails] = commonCashierLogin($employeeStatus = true);

    $cashier->pin = '12345';

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashierLoginData, $cashier): void {
        $mock->shouldReceive('getByUsernameWithEmployeeDetails')
            ->once()
            ->with($cashierLoginData->username)
            ->andReturn($cashier);
    });

    $adminController = new LoginController($cashierQueries);
    $adminController->login($cashierLoginData, $cashierQueries);
})->throws(HttpException::class, 'Credentials are incorrect.');

test(
    'It calls the loadDetailsForMeApiEndpoint of the cashier queries class and returns an array',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $cashierAndEmployeeData = makeCashierAndEmployeeForPosWithCounterUpdateId($counterUpdate->id);

        $employee = $cashierAndEmployeeData['employee'];

        $employee->company = $company;

        $cashier = $cashierAndEmployeeData['cashier'];

        $cashierGroup = CashierGroup::factory()->make([
            'company_id' => 1,
        ]);

        $cashier->employee = $employee;
        $cashier->cashierGroup = $cashierGroup;
        $cashier->counterUpdate = $counterUpdate;
        $cashier->counterUpdate->counter = $counter;
        $cashier->counterUpdate->counter->location = $location;

        $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('loadDetailsForMeApiEndpoint')
                ->once()
                ->with($cashier)
                ->andReturn($cashier);
        });

        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $adminController = new LoginController();
        $response = $adminController->me($request, $cashierQueries);

        expect($response['cashier']->resource)->toHaveKey('username', $cashier->username);
        expect($response['cashier']->resource)->toHaveKey('employee');
        expect($response['round_off_configuration'][0])->toHaveKeys(['decimal_place', 'value']);
        expect($response['store']->resource)->toHaveKeys(['name', 'code']);
        expect($response['counter']->resource)->toHaveKey('opening_balance');
    }
);

test(
    'it returns the url as per the configuration key',
    function (): void {
        $urlFromConfigurationKeyData = [
            'configuration_key' => 'Test',
        ];
        $urlFromConfigurationKeyDataForPos = new UrlFromConfigurationKeyDataForPos(...$urlFromConfigurationKeyData);
        Config::set('services.list_of_web_app_urls_and_keys', 'Test=http://localhost/api/pos');
        $adminController = new LoginController();
        $response = $adminController->getUrlFromConfigurationKey($urlFromConfigurationKeyDataForPos);
        expect($response)->toHaveKey('url', 'http://localhost/api/pos');
    }
);

test(
    'it display the error if the configuration key is invalid',
    function (): void {
        $urlFromConfigurationKeyData = [
            'configuration_key' => 'Test1',
        ];
        $urlFromConfigurationKeyDataForPos = new UrlFromConfigurationKeyDataForPos(...$urlFromConfigurationKeyData);
        Config::set('services.list_of_web_app_urls_and_keys', 'Test=http://localhost/api/pos');
        $adminController = new LoginController();
        $adminController->getUrlFromConfigurationKey($urlFromConfigurationKeyDataForPos);
    }
)->throws(HttpException::class, 'The specified configuration key is invalid.');

test(
    'it display the error if the configuration key is not given',
    function (): void {
        $urlFromConfigurationKeyData = [
            'configuration_key' => '',
        ];
        $urlFromConfigurationKeyDataForPos = new UrlFromConfigurationKeyDataForPos(...$urlFromConfigurationKeyData);
        Config::set('services.list_of_web_app_urls_and_keys', 'Test=http://localhost/api/pos');
        $adminController = new LoginController();
        $adminController->getUrlFromConfigurationKey($urlFromConfigurationKeyDataForPos);
    }
)->throws(HttpException::class);

function commonCashierLogin(bool $employeeStatus): array
{
    $cashierAndEmployeeData = makeCashierAndEmployeeForPosWithoutCounterUpdateId($employeeStatus);
    $employee = $cashierAndEmployeeData['employee'];
    $cashier = $cashierAndEmployeeData['cashier'];

    $cashier->employee = $employee;

    $requestDetails = [
        'username' => 'cashier',
        'pin' => '1234',
        'device_type' => 'mobile',
    ];

    $cashierLoginData = new CashierLoginData(...$requestDetails);

    return [$cashierLoginData, $cashier, $requestDetails];
}
