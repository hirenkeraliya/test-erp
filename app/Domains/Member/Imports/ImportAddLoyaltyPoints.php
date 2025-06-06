<?php

declare(strict_types=1);

namespace App\Domains\Member\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Enums\UpdateMemberLoyaltyPointsImportColumns;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\ImportRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportAddLoyaltyPoints implements ImportRecordClassInterface
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

        /** @var Admin $admin */
        $admin = $importRecord->createdBy;

        DB::beginTransaction();

        try {
            $member = $memberQueries->getMemberByCardNumber($memberDetails['card_number'], $importRecord->company_id);

            if (null === $member) {
                return;
            }

            $loyaltyPointService = resolve(LoyaltyPointService::class);
            $loyaltyPointService->increaseLoyaltyPointsForAdmin(
                $member,
                $admin,
                (int) $memberDetails['loyalty_points'],
                (string) $memberDetails['reasons'],
                (int) $member->loyalty_points
            );

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('import record add loyalty point', [
                'error_message' => $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            abort(417, 'An error occurred. Please try again');
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
