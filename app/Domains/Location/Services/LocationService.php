<?php

declare(strict_types=1);

namespace App\Domains\Location\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\City\CityQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Location\DataObjects\LocationData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\State\StateQueries;
use App\Domains\Store\Enums\StoreTimings;

class LocationService
{
    public function getLocationData(array $locationDetails, int $companyId): LocationData
    {
        $brandQueries = resolve(BrandQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $cityQueries = resolve(CityQueries::class);

        $countryId = $countryQueries->getIdByName((string) $locationDetails['country']);
        $stateId = $stateQueries->getIdByName((string) $locationDetails['state']);
        $cityId = $cityQueries->getIdByName((string) $locationDetails['city']);

        $locationType = LocationTypes::getValueByCaseName($locationDetails['type']);

        $brandIds = [];

        if ($locationType === LocationTypes::STORE->value) {
            $brandIds = $brandQueries->getIdsByNames(explode(',', $locationDetails['brands']), $companyId);
        }

        return new LocationData(
            name: (string) $locationDetails['name'],
            code: (string) $locationDetails['code'],
            type_id: (int) $locationType,
            registration_number: (string) $locationDetails['registration_number'],
            sst_number: (string) $locationDetails['sst_number'],
            email: (string) $locationDetails['email'],
            phone: (string) $locationDetails['phone'],
            mobile: array_key_exists('mobile', $locationDetails) ? (string) $locationDetails['mobile'] : null,
            fax: array_key_exists('fax', $locationDetails) ? (string) $locationDetails['fax'] : null,
            address_line_1: (string) $locationDetails['address_line_1'],
            address_line_2: (string) $locationDetails['address_line_2'] ?: null,
            area_code: (string) $locationDetails['area_code'],
            web_site: (string) $locationDetails['website'] ?: null,
            sales_tax_percentage: (float) $locationDetails['sales_tax_percentage'],
            sales_return_days_limit: array_key_exists(
                'sales_return_days_limit',
                $locationDetails
            ) ? (int) $locationDetails['sales_return_days_limit'] : 0,
            credit_note_expiration_days: array_key_exists(
                'credit_note_expiration_days',
                $locationDetails
            ) ? (int) $locationDetails['credit_note_expiration_days'] : 0,
            loyalty_point_expiration_days: array_key_exists(
                'loyalty_point_expiration_days',
                $locationDetails
            ) ? (int) $locationDetails['loyalty_point_expiration_days'] : 0,
            receipt_footer: (string) $locationDetails['receipt_footer'],
            disclaimer: (string) $locationDetails['disclaimer'],
            cash_out_limit_info: array_key_exists(
                'cash_out_limit_info',
                $locationDetails
            ) ? (float) $locationDetails['cash_out_limit_info'] : 0,
            cash_out_limit_warning: array_key_exists(
                'cash_out_limit_warning',
                $locationDetails
            ) ? (float) $locationDetails['cash_out_limit_warning'] : 0,
            cash_out_limit_restrict: array_key_exists(
                'cash_out_limit_restrict',
                $locationDetails
            ) ? (float) $locationDetails['cash_out_limit_restrict'] : 0,
            brand_ids: $brandIds,
            is_automatic_day_close: false,
            share_inventory_to_external_companies: false,
            automatic_day_close_time: null,
            price_fall_down_percentage: (float) $locationDetails['price_fall_down_percentage'],
            open_time: isset($locationDetails['open_time']) && null !== $locationDetails['open_time'] ? $locationDetails['open_time'] : (string) StoreTimings::OPEN_TIME->value,
            close_time: isset($locationDetails['close_time']) && null !== $locationDetails['close_time'] ? $locationDetails['close_time'] : (string) StoreTimings::CLOSE_TIME->value,
            country_id: $countryId,
            state_id: $stateId,
            city_id: $cityId,
        );
    }
}
