<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Vendor\Enums\VendorImportColumns;
use App\Domains\Vendor\Services\VendorService;
use App\Domains\Vendor\VendorQueries;
use App\Models\ImportRecord;

class ImportVendor implements ImportRecordClassInterface
{
    /**
     * @return string[]
     */
    public function validate(array $vendorDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $vendorQueries = resolve(VendorQueries::class);

        if (! array_key_exists('name', $vendorDetails) || ! $vendorDetails['name']) {
            $validationErrors[] = 'The name is required.';
        } elseif ($vendorQueries->existsByName((string) $vendorDetails['name'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified name is already present in our records.';
        }

        if (! array_key_exists('email', $vendorDetails) || ! $vendorDetails['email']) {
            $validationErrors[] = 'An email address is required.';
        }

        if (! array_key_exists('phone', $vendorDetails) || ! $vendorDetails['phone']) {
            $validationErrors[] = 'The phone is required.';
        } elseif ($vendorQueries->existsByPhone((string) $vendorDetails['phone'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified phone is already available in our records.';
        }

        if (! array_key_exists('address_line_1', $vendorDetails) || ! $vendorDetails['address_line_1']) {
            $validationErrors[] = 'Please provide the address line 1..';
        }

        if (! array_key_exists('city', $vendorDetails) || ! $vendorDetails['city']) {
            $validationErrors[] = 'The city is being required.';
        }

        if (! array_key_exists('area_code', $vendorDetails) || ! $vendorDetails['area_code']) {
            $validationErrors[] = 'The area code is mandatory.';
        }

        if (array_key_exists(
            'consignment',
            $vendorDetails
        ) && 'Yes' === $vendorDetails['consignment'] && (! array_key_exists(
            'commission_percentage',
            $vendorDetails
        ) || $vendorDetails['commission_percentage'] < 1)) {
            $validationErrors[] = 'When consignment is Yes, the commission percentage must be specified and must be at least 1.';
        }

        return $validationErrors;
    }

    public function save(array $vendorDetails, ImportRecord $importRecord): void
    {
        $vendorService = resolve(VendorService::class);
        $vendorData = $vendorService->getVendorData($vendorDetails);

        $vendorQueries = resolve(VendorQueries::class);
        $vendorQueries->addNew($vendorData, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(VendorImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
