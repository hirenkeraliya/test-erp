<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExportRecordJobStatus: string
{
    use PrepareEnumDataMethods;

    case JOB_TIME_OUT = 'job-time-out';
    case RECORD_COMPLETION = 'record-completion';
}
