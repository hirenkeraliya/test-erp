<?php

namespace App\Services;

use App\CommonFunctions;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenCage\Geocoder\Geocoder;

class GoogleGeocodingService
{
    public function isEnabled(): bool
    {
        return (bool) config('app.geo_code_api_key');
    }

    public function getCoordinates(string $address): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $geoCoder = new Geocoder(config('app.geo_code_api_key'));
            $response = $geoCoder->geocode($address);

            if (200 === $response['status']['code'] && 'OK' === $response['status']['message']) {
                if (count($response['results']) === 0) {
                    return null;
                }

                return [
                    'lat' => $response['results'][0]['geometry']['lat'],
                    'lng' => $response['results'][0]['geometry']['lng'],
                ];
            }
        } catch (Exception $exception) {
            Log::channel('google_geocoding')
                ->error('Error while fetching coordinates from Google Geocoding API', [
                    'error_message' => $exception->getMessage(),
                    'error_code' => 'Error code: ' . $exception->getCode(),
                    'file' => 'File: ' . $exception->getFile(),
                    'line' => 'Line: ' . $exception->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$exception],
                ]);
        }

        return null;
    }

    public function getCoordinatesForLocation(array $locationData): array
    {
        if (! $this->isEnabled()) {
            Log::channel('google_geocoding')
                ->error('Google Geocoding API key is not set in the configuration');

            $locationData['latitude'] = null;
            $locationData['longitude'] = null;

            return $locationData;
        }

        $countryId = $locationData['country_id'];
        $stateId = $locationData['state_id'];
        $cityId = $locationData['city_id'];

        [$cityName, $stateName, $countryName] = CommonFunctions::getCityStateCountryNames(
            $cityId,
            $stateId,
            $countryId
        );

        $fullAddress = $locationData['address_line_1'] . ' ' . $locationData['address_line_2'] . ' ' . $cityName . ' ' . $stateName . ' ' . $countryName . ' ' . $locationData['area_code'];

        $coordinates = $this->getCoordinates($fullAddress);

        if ($coordinates) {
            $locationData['latitude'] = $coordinates['lat'];
            $locationData['longitude'] = $coordinates['lng'];
        }

        return $locationData;
    }
}
