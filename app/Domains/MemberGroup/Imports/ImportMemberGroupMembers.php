<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\Enums\ImportMemberGroupMembersColumn;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Models\ImportRecord;
use Illuminate\Support\Collection;

class ImportMemberGroupMembers implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $memberDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $memberQueries = resolve(MemberQueries::class);

        if (empty($memberDetails['mobile_number']) && empty($memberDetails['card_number'])) {
            $validationErrors[] = 'A mobile number or card number is required.';

            return $validationErrors;
        }

        if (! empty($memberDetails['mobile_number'])) {
            $mobileNumber = (string) $memberDetails['mobile_number'];
            if (! $memberQueries->checkMobileNumberExists($mobileNumber)) {
                $validationErrors[] = 'The provided mobile number does not exist in our records.';
            }

            return $validationErrors;
        }

        $cardNumber = (string) $memberDetails['card_number'];
        if (! $memberQueries->checkCardNumberExists($cardNumber)) {
            $validationErrors[] = 'The provided card number does not exist in our records.';
        }

        return $validationErrors;
    }

    public function save(array $memberDetails, ImportRecord $importRecord): void
    {
        $memberQueries = resolve(MemberQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);
        $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
        $memberData = [
            'mobile_number' => $memberDetails['mobile_number'] ?? null,
            'card_number' => $memberDetails['card_number'] ?? null,
        ];
        $memberGroup = $memberGroupQueries->getByIdForImportRecord(
            (int) $importRecord->module_id,
            $importRecord->company_id
        );
        $member = $memberQueries->findMemberByMobileNumberOrCardNumber($memberData, $importRecord->company_id);

        /** @var Collection $members */
        $members = $memberGroup->memberGroupMembers;

        if ($member && ! in_array($member->id, $members->pluck('member_id')->toArray())) {
            $data = [
                'member_id' => $member->id,
                'member_group_id' => $memberGroup->id,
                'is_synced' => true,
            ];
            $memberGroupMemberQueries->addNew($data);
        }
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(ImportMemberGroupMembersColumn::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
