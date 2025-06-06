<?php

declare(strict_types=1);

namespace App\Domains\Region\Services;

use App\Domains\Region\DataObjects\RegionData;

class RegionService
{
    public function getRegionData(array $regionDetails): RegionData
    {
        return new RegionData(
            name: (string) $regionDetails['name'],
            code: (string) $regionDetails['code'] ?: null,
            manager_name: array_key_exists(
                'manager_name',
                $regionDetails
            ) && $regionDetails['manager_name'] ? (string) $regionDetails['manager_name'] : null,
            manager_email: array_key_exists(
                'manager_email',
                $regionDetails
            ) && $regionDetails['manager_email'] ? (string) $regionDetails['manager_email'] : null,
        );
    }
}
