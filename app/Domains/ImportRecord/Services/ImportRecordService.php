<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Services;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Exceptions\RedirectBackWithErrorException;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportRecordService
{
    public function hasMoreRecords(int $highestRow, int $rowIndex, int $totalRecords): bool
    {
        return $highestRow === $rowIndex && $rowIndex < $totalRecords;
    }

    public function headerColumnsAlreadySet(int $rowIndex, array $headerColumns): bool
    {
        return 1 === $rowIndex && count(array_filter($headerColumns));
    }

    public function getJobRestartTime(): \Illuminate\Support\Carbon
    {
        $jobExpirationTimeoutSeconds = config(
            'horizon.environments.' . config('app.env') . '.supervisor-1.timeout',
            60
        );

        return now()->addSeconds((int) $jobExpirationTimeoutSeconds * 80 / 100);
    }

    public function jobIsReadyToExpire(Carbon $jobRestartTime): bool
    {
        return now()->greaterThanOrEqualTo($jobRestartTime);
    }

    /**
     * Decides the new end row number based on the parameters passed:
     * - If current start and end row numbers are there, we just add inserted rows count to the current end row number.
     * - otherwise, we add 80% of the inserted rows count to the current end row number.
     *
     * Finally, we cross check that the total number of rows in the file before returning it.
     */
    public function getNewEndRowNumber(
        int $insertedRowsCount,
        ?int $currentEndRowNumber,
        ?int $currentStartRowNumber,
        int $totalRecordsInFile
    ): int {
        $totalRecords = $currentEndRowNumber
            ? $currentEndRowNumber - $currentStartRowNumber
            : (($insertedRowsCount - 1) * 80 / 100);

        $totalRecords = (int) $totalRecords;

        $newEndRowNumber = $insertedRowsCount + $totalRecords;

        if ($totalRecordsInFile < $newEndRowNumber) {
            return $totalRecordsInFile + 1;
        }

        return $newEndRowNumber;
    }

    public function isThisFirstImportCycle(?int $startRowNumber, ?int $endRowNumber): bool
    {
        return ! $startRowNumber && ! $endRowNumber;
    }

    public function validateColumns(
        UploadedFile $uploadFile,
        array $allPermissionLists,
        int $companyId,
        int $importTypeTypeId,
    ): void {
        $spreadsheet = IOFactory::load($uploadFile->getPathname());
        /** @phpstan-ignore-next-line */
        $headers = array_flip(collect(current($spreadsheet->getActiveSheet()->toArray()))->filter()->toArray());

        $importRecordClassObject = ImportTypes::getClassFor($importTypeTypeId);

        $isInvalidHeaderColumns = $importRecordClassObject->validateColumns($headers, $allPermissionLists, $companyId);

        if (ColumnValidationIssueTypes::COLUMN_ISSUE->value === $isInvalidHeaderColumns['type'] && $isInvalidHeaderColumns['status']) {
            throw new RedirectBackWithErrorException(ColumnValidationIssueTypes::getErrorMessageForSpecificIssue(
                ColumnValidationIssueTypes::COLUMN_ISSUE->value
            ));
        }

        if (config('app.env') === 'local') {
            return;
        }

        if (ColumnValidationIssueTypes::PERMISSION_ISSUE->value !== $isInvalidHeaderColumns['type']) {
            return;
        }

        if (! $isInvalidHeaderColumns['status']) {
            return;
        }

        throw new RedirectBackWithErrorException(ColumnValidationIssueTypes::getErrorMessageForSpecificIssue(
            ColumnValidationIssueTypes::PERMISSION_ISSUE->value,
            $isInvalidHeaderColumns['columns']
        ));
    }

    public function validateColumn(array $requiredHeaderColumns, array $uploadHeaderColumns): bool
    {
        $missingColumns = array_diff($requiredHeaderColumns, array_keys($uploadHeaderColumns));

        return [] !== $missingColumns;
    }
}
