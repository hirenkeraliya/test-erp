<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Interfaces;

use App\Models\ImportRecord;

interface ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $recordDetails, ImportRecord $importRecord): array;

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array;

    public function save(array $recordDetails, ImportRecord $importRecord): void;
}
