<?php

declare(strict_types=1);

namespace App\Domains\Location\Imports;

use App\Domains\Brand\BrandQueries;
use App\Domains\City\CityQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\Enums\LocationImportColumns;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Services\LocationService;
use App\Domains\State\StateQueries;
use App\Models\ImportRecord;

class ImportLocationBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return string[]
     */
    public function validate(array $locationDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $locationQueries = resolve(LocationQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $cityQueries = resolve(CityQueries::class);

        $locationType = LocationTypes::getValueByCaseName($locationDetails['type']);

        if (! array_key_exists('type', $locationDetails) || ! $locationDetails['type']) {
            $validationErrors[] = 'The type is required.';
        } elseif (! $locationType) {
            $validationErrors[] = 'Invalid type specified.';
        }

        if ($locationType) {
            if (! array_key_exists('phone', $locationDetails) || ! $locationDetails['phone']) {
                $validationErrors[] = 'The phone is required.';
            } elseif (! $locationQueries->existsByPhoneAndTypeId(
                (string) $locationDetails['phone'],
                $locationType,
                $importRecord->company_id
            )) {
                $validationErrors[] = 'The specified phone is not available in our records.';
            }

            if (! array_key_exists('name', $locationDetails) || ! $locationDetails['name']) {
                $validationErrors[] = 'The name is required.';
            } elseif ($locationQueries->isLocationNameTakenByAnother(
                (string) $locationDetails['name'],
                (string) $locationDetails['phone'],
                $locationType,
                $importRecord->company_id
            )) {
                $validationErrors[] = 'The specified name is already taken by another location.';
            }

            if (! array_key_exists('code', $locationDetails) || ! $locationDetails['code']) {
                $validationErrors[] = 'The code is required';
            } elseif ($locationQueries->isLocationCodeTakenByAnother(
                (string) $locationDetails['code'],
                (string) $locationDetails['phone'],
                $locationType,
                $importRecord->company_id
            )) {
                $validationErrors[] = 'The specified code is already taken by another location.';
            }

            if ($locationType === LocationTypes::STORE->value) {
                if (! array_key_exists(
                    'sales_tax_percentage',
                    $locationDetails
                ) || null === $locationDetails['sales_tax_percentage']) {
                    $validationErrors[] = 'The sales tax percentage is mandatory.';
                }

                if (! array_key_exists(
                    'sales_return_days_limit',
                    $locationDetails
                ) || null === $locationDetails['sales_return_days_limit']) {
                    $validationErrors[] = 'The sales return day limit is mandatory.';
                }

                if (! array_key_exists('receipt_footer', $locationDetails) || ! $locationDetails['receipt_footer']) {
                    $validationErrors[] = 'The receipt footer is mandatory.';
                }

                if (! array_key_exists('disclaimer', $locationDetails) || ! $locationDetails['disclaimer']) {
                    $validationErrors[] = 'The disclaimer is mandatory.';
                }

                if (! array_key_exists(
                    'price_fall_down_percentage',
                    $locationDetails
                ) || ! $locationDetails['price_fall_down_percentage']) {
                    $validationErrors[] = 'The price fall down percentage is mandatory.';
                }

                if (array_key_exists(
                    'price_fall_down_percentage',
                    $locationDetails
                ) && $locationDetails['price_fall_down_percentage'] < 0 || $locationDetails['price_fall_down_percentage'] > 100) {
                    $validationErrors[] = 'The price fall down percentage field must be between 0 and 100.';
                }

                if (! array_key_exists('brands', $locationDetails) || ! $locationDetails['brands']) {
                    $validationErrors[] = 'The brand is mandatory.';
                } elseif (array_key_exists('brands', $locationDetails)) {
                    $brandNames = explode(',', $locationDetails['brands']);
                    $brandQueries = resolve(BrandQueries::class);

                    $brands = $brandQueries->existsByNames($brandNames, $importRecord->company_id)->pluck(
                        'name'
                    )->toArray();

                    if ([] === $brands) {
                        $validationErrors[] = 'The specified brands is not available in our records.';
                    }
                }
            }
        }

        if (! array_key_exists('registration_number', $locationDetails) || ! $locationDetails['registration_number']) {
            $validationErrors[] = 'The registration number is mandatory.';
        }

        if (! array_key_exists('sst_number', $locationDetails) || ! $locationDetails['sst_number']) {
            $validationErrors[] = 'The SST number is required.';
        }

        if (! array_key_exists('email', $locationDetails) || ! $locationDetails['email']) {
            $validationErrors[] = 'An email address is required.';
        }

        if (! array_key_exists('address_line_1', $locationDetails) || ! $locationDetails['address_line_1']) {
            $validationErrors[] = 'Please provide the address line 1..';
        }

        if (! array_key_exists('area_code', $locationDetails) || ! $locationDetails['area_code']) {
            $validationErrors[] = 'The area code is mandatory.';
        }

        if (! array_key_exists('country', $locationDetails) || ! $locationDetails['country']) {
            $validationErrors[] = 'The country is required';
        } elseif (! $countryQueries->existsByName((string) $locationDetails['country'])) {
            $validationErrors[] = 'The specified country is not available in our records.';
        }

        if (! array_key_exists('state', $locationDetails) || ! $locationDetails['state']) {
            $validationErrors[] = 'The state is required';
        } elseif (! $stateQueries->existsByName((string) $locationDetails['state'])) {
            $validationErrors[] = 'The specified state is not available in our records.';
        }

        if (! array_key_exists('city', $locationDetails) || ! $locationDetails['city']) {
            $validationErrors[] = 'The city is required';
        } elseif (! $cityQueries->existsByName((string) $locationDetails['city'])) {
            $validationErrors[] = 'The specified city is not available in our records.';
        }

        return $validationErrors;
    }

    public function save(array $locationDetails, ImportRecord $importRecord): void
    {
        $locationType = LocationTypes::getValueByCaseName($locationDetails['type']);
        $locationService = resolve(LocationService::class);
        $locationData = $locationService->getLocationData($locationDetails, $importRecord->company_id);

        $locationQueries = resolve(LocationQueries::class);
        $locationQueries->updateByPhone(
            $locationData->all(),
            (string) $locationDetails['phone'],
            $locationType,
            $importRecord->company_id
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(LocationImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
