<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\PaymentType\Enums\PaymentTypeImportColumns;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\PaymentType\Services\PaymentTypeService;
use App\Models\ImportRecord;

class ImportPaymentType implements ImportRecordClassInterface
{
    public function validate(array $paymentTypeDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        if (! array_key_exists('name', $paymentTypeDetails) || ! $paymentTypeDetails['name']) {
            $validationErrors[] = 'The name is required.';
        }

        if (! array_key_exists(
            'is_member_required',
            $paymentTypeDetails
        ) || ! $paymentTypeDetails['is_member_required']) {
            $validationErrors[] = 'The is member is required.';
        }

        if (! array_key_exists(
            'is_available_for_refund',
            $paymentTypeDetails
        ) || ! $paymentTypeDetails['is_available_for_refund']) {
            $validationErrors[] = 'The is_available_for_refund is required.';
        }

        return $validationErrors;
    }

    public function save(array $paymentTypeDetails, ImportRecord $importRecord): void
    {
        $paymentTypeService = resolve(PaymentTypeService::class);
        $paymentTypeData = $paymentTypeService->getPaymentTypeData($paymentTypeDetails);

        $paymentTypeQueries = resolve(PaymentTypeQueries::class);
        $paymentTypeQueries->addNew($paymentTypeData, $importRecord->company_id);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(PaymentTypeImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
