<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ColumnValidationIssueTypes: int
{
    use PrepareEnumDataMethods;

    case COLUMN_ISSUE = 1;
    case PERMISSION_ISSUE = 2;

    public static function getErrorMessageForSpecificIssue(
        int $columnValidationIssueTypeId,
        ?array $withoutPermissionColumns = []
    ): string {
        if (self::COLUMN_ISSUE->value === $columnValidationIssueTypeId) {
            return 'Columns do not match with the sample file.';
        }

        return implode(',', $withoutPermissionColumns) . ' Specified Columns do not have permission.';
    }
}
