<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Interfaces;

use App\Models\ExportRecord;
use Illuminate\Support\Collection;

interface ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void;

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection;
}
