<?php

declare(strict_types=1);

namespace App\Domains\Member\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\DataObjects\UpdateLoyaltyPointData;
use App\Domains\Member\Enums\UpdateMemberLoyaltyPointsImportColumns;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\ImportRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportUpdateLoyaltyPoints implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $memberDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $memberQueries = resolve(MemberQueries::class);

        if (! array_key_exists('card_number', $memberDetails) || ! $memberDetails['card_number']) {
            $validationErrors[] = 'A card number is required.';
        } elseif (! $memberQueries->existsByCardNumber(
            (string) $memberDetails['card_number'],
            $importRecord->company_id
        )) {
            $validationErrors[] = 'The specified card number is not available in our records..';
        }

        if (! array_key_exists('reasons', $memberDetails) || ! $memberDetails['reasons']) {
            $validationErrors[] = 'A reason is required.';
        }

        return $validationErrors;
    }

    public function save(array $memberDetails, ImportRecord $importRecord): void
    {
        $memberQueries = resolve(MemberQueries::class);

        DB::beginTransaction();

        $updateLoyaltyPointData = new UpdateLoyaltyPointData(
            loyalty_points: $memberDetails['loyalty_points'],
            remarks: $memberDetails['reasons'],
        );

        try {
            $member = $memberQueries->getMemberByCardNumber($memberDetails['card_number'], $importRecord->company_id);
            if (null === $member) {
                return;
            }

            // TODO: We need to make this dynamic as per the import record module.
            // For now we are assuming this is always an admin
            /** @var Admin $admin */
            $admin = $importRecord->createdBy;

            $loyaltyPointService = resolve(LoyaltyPointService::class);
            $loyaltyPointService->updateLoyaltyPointsForAdmin($member, $admin, $updateLoyaltyPointData);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('import record update loyalty points', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(UpdateMemberLoyaltyPointsImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
