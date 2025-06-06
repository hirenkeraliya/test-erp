<?php

declare(strict_types=1);

namespace App\Domains\Promoter\services;

use App\Domains\Promoter\DataObjects\PromoterData;

class PromoterService
{
    public function getPromoterData(
        array $promoterDetails,
        array $locationIds,
        int $employeeId,
        ?int $groupId = null
    ): PromoterData {
        return new PromoterData(
            employee_id: $employeeId,
            username: (string) $promoterDetails['username'],
            password: $promoterDetails['password'],
            monthly_sales_target: array_key_exists(
                'monthly_sales_target',
                $promoterDetails
            ) ? (float) $promoterDetails['monthly_sales_target'] : 0,
            code: (string) $promoterDetails['code'],
            location_ids: $locationIds,
            default_commission_amount_percentage: array_key_exists(
                'default_commission_amount_percentage',
                $promoterDetails
            ) ? (float) $promoterDetails['default_commission_amount_percentage'] : 0,
            monthly_target_commission_percentage: array_key_exists(
                'monthly_target_commission_percentage',
                $promoterDetails
            ) ? (float) $promoterDetails['monthly_target_commission_percentage'] : 0,
            group_id: $groupId,
        );
    }
}
