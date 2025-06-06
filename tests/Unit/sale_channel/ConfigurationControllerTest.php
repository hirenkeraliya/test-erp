<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Api\SaleChannel\ConfigurationController;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Location;
use App\Models\SaleChannel;
use App\Models\SiteConfiguration;
use Illuminate\Http\Request;

test('get ecommerce configuration', function (): void {
    $country = Country::factory()->make([
        'id' => 1,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => $country->id,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
    ]);

    $city = City::factory()->make([
        'id' => 1,
        'country_id' => $country->id,
    ]);

    $saleChannel = SaleChannel::factory()->make([
        'company_id' => $company->id,
        'default_location_id' => $location->id,
    ]);

    $location->city = $city;

    $saleChannel->company = $company;
    $saleChannel->location = $location;

    $currency = Currency::factory()->make([
        'country_id' => $country->id,
        'symbol' => '$',
    ]);

    $currency->country = $country;

    $siteConfiguration = SiteConfiguration::query()->make([
        'type_id' => SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME->value,
        'value' => 'Test Company',
    ]);

    $saleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
        $mock->shouldReceive('loadWithLocationsAndCompanyEcommerce')
            ->once()
            ->andReturn($saleChannel);
    });

    $this->mock(CurrencyQueries::class, function ($mock) use ($currency): void {
        $mock->shouldReceive('getByCompanyIdWithCountry')
            ->once()
            ->andReturn($currency);
    });

    $this->mock(SiteConfigurationQueries::class, function ($mock) use ($siteConfiguration): void {
        $mock->shouldReceive('getEcommerceData')
            ->once()
            ->andReturn(collect([$siteConfiguration]));
    });

    $request = new Request();
    $request->setUserResolver(fn () => $saleChannel);

    $configurationController = new ConfigurationController($saleChannelQueries);
    $response = $configurationController->getEcommerceConfiguration($request);

    expect($response)
        ->toBeArray()
        ->toHaveKeys([
            'name',
            'email',
            'phone',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'sales_tax_percentage',
            'company_name',
            'country_name',
            'currency_symbol',
            'currency_name',
            'currency_code',
            'registration_number',
            'sst_number',
            'ecommerce_company_name',
            'ecommerce_favicon',
            'ecommerce_logo',
            'display_variants',
            'display_dynamic_menus',
            'round_off_configuration',
        ])
        ->and($response['company_name'])->toBe($company->name)
        ->and($response['currency_symbol'])->toBe('$')
        ->and($response['ecommerce_company_name'])->toBe('Test Company');
});
