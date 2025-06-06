<?php

declare(strict_types=1);

namespace App\Domains\Color\Services;

use App\Domains\Color\DataObjects\ColorData;

class ColorService
{
    public function getColorData(array $colorDetails): ColorData
    {
        return new ColorData(
            name: (string) $colorDetails['name'],
            code: $colorDetails['code'] ? (string) $colorDetails['code'] : null,
            color_code: $colorDetails['color_code'] ? (string) $colorDetails['color_code'] : null,
            group_id: $colorDetails['color_group_id'],
        );
    }
}
