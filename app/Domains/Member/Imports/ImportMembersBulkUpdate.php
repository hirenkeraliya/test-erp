<?php

declare(strict_types=1);

namespace App\Domains\Member\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberBulkUpdateImportColumns;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Models\ImportRecord;

class ImportMembersBulkUpdate implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $memberDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        if (
            array_key_exists('type', $memberDetails)
            && $memberDetails['type']
            && ! Types::getValueByCaseName($memberDetails['type'])
        ) {
            $validationErrors[] = 'The specified type is not available in our records.';
        }

        if (
            array_key_exists('title', $memberDetails)
            && $memberDetails['title']
            && ! Titles::getValueByCaseName($memberDetails['title'])
        ) {
            $validationErrors[] = 'The specified title is not available in our records.';
        }

        if (
            array_key_exists('race', $memberDetails)
            && $memberDetails['race']
            && ! Races::getValueByCaseName($memberDetails['race'])
        ) {
            $validationErrors[] = 'The specified race is not available in our records.';
        }

        if (
            array_key_exists('gender', $memberDetails)
            && $memberDetails['gender']
            && ! Genders::getValueByCaseName($memberDetails['gender'])
        ) {
            $validationErrors[] = 'The specified gender is not available in our records.';
        }

        if (! array_key_exists('first_name', $memberDetails) || ! $memberDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('mobile_number', $memberDetails) || ! $memberDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! $memberQueries->memberExistsByMobileNumber(
            $importRecord->company_id,
            (string) $memberDetails['mobile_number']
        )) {
            $validationErrors[] = 'Specified mobile number is not available in our records.';
        }

        if (! array_key_exists('created_location', $memberDetails) || ! $memberDetails['created_location']) {
            $validationErrors[] = 'A created location is required.';
        } elseif (! $locationQueries->doStoreNameExist($memberDetails['created_location'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified created location is not available in our records.';
        }

        if (! array_key_exists('email', $memberDetails) || ! $memberDetails['email']) {
            $validationErrors[] = 'An email address is required.';
        } elseif ($memberQueries->emailTakenByAnotherMember(
            $memberDetails['email'],
            $importRecord->company_id,
            (string) $memberDetails['mobile_number']
        )) {
            $validationErrors[] = 'The specified email address is already taken by another member.';
        }

        return $validationErrors;
    }

    public function save(array $memberDetails, ImportRecord $importRecord): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $locationId = $locationQueries->getIdByName($memberDetails['created_location'], $importRecord->company_id);
        $isCorporate = Types::getValueByCaseName((string) $memberDetails['type']) === Types::CORPORATE->value;

        $memberData = [
            'type_id' => Types::getValueByCaseName((string) $memberDetails['type']),
            'title_id' => Titles::getValueByCaseName((string) $memberDetails['title']),
            'race_id' => Races::getValueByCaseName((string) $memberDetails['race']),
            'first_name' => (string) $memberDetails['first_name'],
            'last_name' => (string) $memberDetails['last_name'],
            'gender_id' => Genders::getValueByCaseName((string) $memberDetails['gender']),
            'date_of_birth' => (string) $memberDetails['date_of_birth'],
            'mobile_number' => (string) $memberDetails['mobile_number'],
            'email' => (string) $memberDetails['email'],
            'company_name' => $isCorporate ? (string) $memberDetails['company_name'] : null,
            'company_registration_number' => $isCorporate ? (string) $memberDetails['company_registration_number'] : null,
            'company_tax_number' => $isCorporate ? (string) $memberDetails['company_tax_number'] : null,
            'company_address' => $isCorporate ? (string) $memberDetails['company_address'] : null,
            'company_phone' => $isCorporate ? (string) $memberDetails['company_phone'] : null,
            'pic_name' => (string) $memberDetails['pic_name'],
            'pic_contact' => (string) $memberDetails['pic_contact'],
            'created_location_id' => $locationId,
        ];

        $memberQueries->updateByMobileNumber(
            $memberData,
            $importRecord->company_id,
            (string) $memberDetails['mobile_number']
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(MemberBulkUpdateImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
