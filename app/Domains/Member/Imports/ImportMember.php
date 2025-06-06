<?php

declare(strict_types=1);

namespace App\Domains\Member\Imports;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\MemberImportColumns;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\ImportRecord;

class ImportMember implements ImportRecordClassInterface
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
            (! array_key_exists('type', $memberDetails)
            && null !== $memberDetails['type'])
            && ! Types::getValueByCaseName((string) $memberDetails['type'])
        ) {
            $validationErrors[] = 'The specified type is not available in our records.';
        }

        if (
            (! array_key_exists('title', $memberDetails)
            && null !== $memberDetails['title'])
            && ! Titles::getValueByCaseName((string) $memberDetails['title'])
        ) {
            $validationErrors[] = 'The specified title is not available in our records.';
        }

        if (
            (! array_key_exists('race', $memberDetails)
            && null !== $memberDetails['race'])
            && ! Races::getValueByCaseName((string) $memberDetails['race'])
        ) {
            $validationErrors[] = 'The specified race is not available in our records.';
        }

        if (
            (! array_key_exists('gender', $memberDetails)
            && null !== $memberDetails['gender'])
            && ! Genders::getValueByCaseName((string) $memberDetails['gender'])
        ) {
            $validationErrors[] = 'The specified gender is not available in our records.';
        }

        if (! array_key_exists('first_name', $memberDetails) || ! $memberDetails['first_name']) {
            $validationErrors[] = 'A first name is required.';
        }

        if (! array_key_exists('mobile_number', $memberDetails) || ! $memberDetails['mobile_number']) {
            $validationErrors[] = 'A mobile number is required.';
        } elseif (! CommonFunctions::checkMobileNumber((string) $memberDetails['mobile_number'])) {
            $validationErrors[] = 'The mobile number is invalid.';
        } elseif ($memberQueries->existsByMobileNumber(
            (string) $memberDetails['mobile_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified mobile number is already in our records.';
        }

        if (! array_key_exists('card_number', $memberDetails) || ! $memberDetails['card_number']) {
            $validationErrors[] = 'A card number is required.';
        } elseif ($memberQueries->existsByCardNumber(
            (string) $memberDetails['card_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified card number is already in our records..';
        }

        if (
            array_key_exists('email', $memberDetails) &&
            $memberDetails['email'] &&
            $memberQueries->existsByEmail((string) $memberDetails['email'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'The specified email is already in our records.';
        }

        if (
            array_key_exists('created_location', $memberDetails) &&
            $memberDetails['created_location'] &&
            ! $locationQueries->doStoreNameExist((string) $memberDetails['created_location'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'The specified created location is not available in our records.';
        }

        return $validationErrors;
    }

    public function save(array $memberDetails, ImportRecord $importRecord): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $locationId = $memberDetails['created_location'] ? $locationQueries->getIdByName(
            (string) $memberDetails['created_location'],
            $importRecord->company_id
        ) : null;
        $isCorporate = Types::getValueByCaseName((string) $memberDetails['type']) === Types::CORPORATE->value;

        /** @var Admin $admin */
        $admin = $importRecord->createdBy;

        $memberData = [
            'company_id' => $importRecord->company_id,
            'type_id' => array_key_exists('type', $memberDetails) ? Types::getValueByCaseName(
                (string) $memberDetails['type']
            ) : null,
            'title_id' => array_key_exists('title', $memberDetails) ? Titles::getValueByCaseName(
                (string) $memberDetails['title']
            ) : null,
            'race_id' => array_key_exists('race', $memberDetails) ? Races::getValueByCaseName(
                (string) $memberDetails['race']
            ) : null,
            'first_name' => (string) $memberDetails['first_name'],
            'last_name' => (string) $memberDetails['last_name'] ?: null,
            'gender_id' => array_key_exists('gender', $memberDetails) ? Genders::getValueByCaseName(
                (string) $memberDetails['gender']
            ) : null,
            'date_of_birth' => (string) $memberDetails['date_of_birth'] ?: null,
            'mobile_number' => (string) $memberDetails['mobile_number'],
            'email' => (string) $memberDetails['email'] ?: null,
            'company_name' => $isCorporate ? (string) $memberDetails['company_name'] : null,
            'company_registration_number' => $isCorporate ? (string) $memberDetails['company_registration_number'] : null,
            'company_tax_number' => $isCorporate ? (string) $memberDetails['company_tax_number'] : null,
            'company_address' => $isCorporate ? (string) $memberDetails['company_address'] : null,
            'company_phone' => $isCorporate ? (string) $memberDetails['company_phone'] : null,
            'pic_name' => $isCorporate ? (string) $memberDetails['pic_name'] : null,
            'pic_contact' => $isCorporate ? (string) $memberDetails['pic_contact'] : null,
            'created_by_id' => $importRecord->created_by_id,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_location_id' => $locationId,
            'notes' => (string) $memberDetails['notes'] ?: null,
            'card_number' => (string) $memberDetails['card_number'] ?: null,
        ];

        $member = $memberQueries->create($memberData);

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->increaseLoyaltyPointsForAdmin(
            $member,
            $admin,
            (int) $memberDetails['loyalty_points'],
            'Welcome member loyalty points',
            (int) $member->loyalty_points
        );
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(MemberImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
