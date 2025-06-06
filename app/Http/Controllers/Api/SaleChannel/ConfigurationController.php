<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel;

use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Domains\OnlineSalesCharges\Resources\OnlineSalesChargesEcommerceResource;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\SaleChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigurationController extends Controller
{
    public function __construct(
        protected SaleChannelQueries $saleChannelQueries
    ) {
    }

    public function getConfiguration(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $saleChannel = $this->saleChannelQueries->loadWithLocationsAndCompany($saleChannel);

        /** @var Company $company */
        $company = $saleChannel->company;

        /** @var Location $location */
        $location = $saleChannel->location;

        /** @var City $city */
        $city = $location->city;

        $roundOffConfiguration = resolve(RoundOffConfiguration::class);

        $onlineSalesChargesQueries = resolve(OnlineSalesChargesQueries::class);
        $onlineSalesCharges = $onlineSalesChargesQueries->onlineSalesChargesForEcommerce($company->id);

        $currencyQueries = resolve(CurrencyQueries::class);

        $currency = $currencyQueries->getByCompanyIdWithCountry($company->id);
        $countryName = 'N/A';
        if ($currency->country) {
            $countryName = $currency->country->getName();
        }

        return [
            'id' => $location->id,
            'name' => $location->name,
            'email' => $location->email,
            'phone' => $location->phone,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'city' => $city->name ?? null,
            'area_code' => $location->area_code,
            'sales_tax_percentage' => (float) $location->sales_tax_percentage,
            'sales_return_days_limit' => $location->sales_return_days_limit,
            'registration_number' => $location->registration_number,
            'sst_number' => $location->sst_number,
            'company_name' => $company->name,
            'light_logo' => $company->getDiskBasedFirstMediaUrl('light_logo'),
            'light_logo_detail' => $company->getIdAndName('light_logo'),
            'dark_logo' => $company->getDiskBasedFirstMediaUrl('dark_logo'),
            'dark_logo_detail' => $company->getIdAndName('dark_logo'),
            'round_off_configuration' => $roundOffConfiguration->getList(),
            'country_name' => $countryName,
            'currency_symbol' => $currency->getSymbol(),
            'currency_name' => $currency->getName(),
            'currency_code' => $currency->getCode(),
            'online_sales_charges' => OnlineSalesChargesEcommerceResource::collection($onlineSalesCharges),
        ];
    }

    public function getEcommerceConfiguration(Request $request): array
    {
        $saleChannel = $request->user();
        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $saleChannel = $this->saleChannelQueries->loadWithLocationsAndCompanyEcommerce($saleChannel);

        /** @var Company $company */
        $company = $saleChannel->company;

        /** @var Location $location */
        $location = $saleChannel->location;

        /** @var City $city */
        $city = $location->city;

        $currencyQueries = resolve(CurrencyQueries::class);

        $currency = $currencyQueries->getByCompanyIdWithCountry($company->id);
        $countryName = 'N/A';
        if ($currency->country) {
            $countryName = $currency->country->getName();
        }

        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
        $siteConfigurations = $siteConfigurationQueries->getEcommerceData();

        $siteConfigurationCompanyName = $siteConfigurations->firstWhere(
            'type_id',
            SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME
        );

        $siteConfigurationCompanyFavicon = $siteConfigurations->firstWhere(
            'type_id',
            SiteConfigurationTypes::ECOMMERCE_FAVICON
        );

        $siteConfigurationCompanyLogo = $siteConfigurations->firstWhere(
            'type_id',
            SiteConfigurationTypes::ECOMMERCE_COMPANY_LOGO
        );

        return [
            'name' => $location->name,
            'email' => $location->email,
            'phone' => $location->phone,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'city' => $city->name ?? null,
            'area_code' => $location->area_code,
            'sales_tax_percentage' => (float) $location->sales_tax_percentage,
            'company_name' => $company->name,
            'discount_applicable_type' => $company->discount_applicable_type,
            'country_name' => $countryName,
            'currency_symbol' => $currency->getSymbol(),
            'currency_name' => $currency->getName(),
            'currency_code' => $currency->getCode(),
            'registration_number' => $location->registration_number,
            'sst_number' => $location->sst_number,
            'ecommerce_company_name' => $siteConfigurationCompanyName->value,
            'ecommerce_favicon' => $siteConfigurationCompanyFavicon?->getDiskBasedFirstMediaUrl('ecommerce_favicon'),
            'ecommerce_logo' => $siteConfigurationCompanyLogo?->getDiskBasedFirstMediaUrl('ecommerce_company_logo'),
            'display_variants' => $saleChannel->display_variants,
            'display_dynamic_menus' => $saleChannel->display_dynamic_menus,
            'round_off_configuration' => $saleChannel->round_off_configuration,
        ];
    }

    public function getEcommerceToken(Request $request): array
    {
        $validatedData = $request->validate([
            'mobile_number' => ['required', 'string'],
        ]);

        /** @var SaleChannel $saleChannel */
        $saleChannel = $request->user();

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->getMemberByMobileNumber($validatedData['mobile_number'], $saleChannel->company_id);
        if (! $member) {
            return [
                'response' => [
                    'status' => false,
                    'message' => 'Member not found',
                ],
            ];
        }

        if (! $member->email) {
            return [
                'response' => [
                    'status' => false,
                    'message' => 'Member email not found. Please contact admin to set the email address.',
                ],
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(config('services.http_time_out'))->post($saleChannel->url . '/api/get-customer-token', [
            'email' => $member->email,
            'password' => Member::DEFAULT_E_COMMERCE_PASSWORD,
            'password_confirmation' => Member::DEFAULT_E_COMMERCE_PASSWORD,
        ]);

        if ($response->successful()) {
            Log::channel('e_commerce')->info('Response: Customer token get from the E-commerce', [
                'status_code' => $response->status(),
                'response_body' => $response->body() ?: 'No response body provided',
                'request_details' => [
                    'email' => $member->email,
                    'password' => Member::DEFAULT_E_COMMERCE_PASSWORD,
                    'password_confirmation' => Member::DEFAULT_E_COMMERCE_PASSWORD,
                ],
            ]);

            return [
                'status' => true,
                'response' => json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR),
            ];
        }

        Log::channel('e_commerce')->info('Response: Error on Customer token get from the E-commerce', [
            'status_code' => $response->status(),
            'response_body' => $response->body() ?: 'No response body provided',
        ]);

        return [
            'response' => [
                'status' => false,
                'message' => 'Error on Customer token get from the E-commerce',
            ],
        ];
    }
}
