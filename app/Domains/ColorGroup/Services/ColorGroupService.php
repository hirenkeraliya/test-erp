<?php

declare(strict_types=1);

namespace App\Domains\ColorGroup\Services;

use App\Domains\ColorGroup\DataObjects\ColorGroupData;

class ColorGroupService
{
    public function getColorGroupData(array $colorGroupDetails): ColorGroupData
    {
        return new ColorGroupData(
            name: (string) $colorGroupDetails['name'],
            code: (string) $colorGroupDetails['code'] ?: null,
            color_code: (string) $colorGroupDetails['color_code'] ?: null,
        );
    }
}
