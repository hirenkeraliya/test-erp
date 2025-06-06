<?php

declare(strict_types=1);

namespace App\Domains\Member\Imports;

use App\CommonFunctions;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Member\Enums\MemberAddressImportColumns;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Models\ImportRecord;

class ImportMemberAddress implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $memberAddressDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        if (! array_key_exists('first_name', $memberAddressDetails) || ! $memberAddressDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('address_line_1', $memberAddressDetails) || ! $memberAddressDetails['address_line_1']) {
            $validationErrors[] = 'A Address line 1 is required.';
        }

        if (! array_key_exists('is_primary', $memberAddressDetails) || ! $memberAddressDetails['is_primary']) {
            $validationErrors[] = 'A Is Primary is required.';
        }

        if (! array_key_exists('mobile_number', $memberAddressDetails) || ! $memberAddressDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! CommonFunctions::checkMobileNumber((string) $memberAddressDetails['mobile_number'])) {
            $validationErrors[] = 'The mobile number is invalid.';
        }

        if (! array_key_exists('area_code', $memberAddressDetails) || ! $memberAddressDetails['area_code']) {
            $validationErrors[] = 'Area code is required.';
        }

        return $validationErrors;
    }

    public function save(array $memberAddressDetails, ImportRecord $importRecord): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $memberAddressQueries = resolve(MemberAddressQueries::class);

        $memberId = $memberQueries->getIdByName(
            (string) $memberAddressDetails['first_name'],
            (string) $memberAddressDetails['mobile_number'],
            $importRecord->company_id
        );

        $isPrimary = array_key_exists('is_primary', $memberAddressDetails) ? $memberAddressDetails['is_primary'] : 'No';

        $memberAddressData = [
            'member_id' => $memberId,
            'name' => (string) $memberAddressDetails['name'],
            'contact_mobile_number' => (string) $memberAddressDetails['contact_mobile_number'] ?: null,
            'contact_email' => (string) $memberAddressDetails['contact_email'] ?: null,
            'address_line_1' => (string) $memberAddressDetails['address_line_1'],
            'address_line_2' => (string) $memberAddressDetails['address_line_2'] ?: null,
            'city_name' => (string) $memberAddressDetails['city'] ?: null,
            'area_code' => (string) $memberAddressDetails['area_code'],
            'is_primary' => 'Yes' === $isPrimary,
        ];
        $memberAddressQueries->addNew($memberAddressData);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(MemberAddressImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
