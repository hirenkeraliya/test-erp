<?php

declare(strict_types=1);

namespace App\Domains\SizeGroup\Services;

use App\Domains\SizeGroup\DataObjects\SizeGroupData;

class SizeGroupService
{
    public function getSizeGroupData(array $sizeGroupDetails): SizeGroupData
    {
        return new SizeGroupData(
            name: (string) $sizeGroupDetails['name'],
            code: (string) $sizeGroupDetails['code'] ?: null,
        );
    }
}
