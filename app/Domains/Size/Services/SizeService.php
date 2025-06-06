<?php

declare(strict_types=1);

namespace App\Domains\Size\Services;

use App\Domains\Size\DataObjects\SizeData;

class SizeService
{
    public function getSizeData(array $sizeDetails): SizeData
    {
        return new SizeData(
            name: (string) $sizeDetails['name'],
            code: $sizeDetails['code'] ? (string) $sizeDetails['code'] : null,
            sort_order: $sizeDetails['sort_order_id'],
            group_id: $sizeDetails['size_group_id'],
        );
    }
}
