<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Resources;

use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\EmployeeGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosEmployeeGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var EmployeeGroup $employeeGroup */
        $employeeGroup = $this;

        return [
            'id' => $employeeGroup->id,
            'name' => $employeeGroup->name,
            'code' => $employeeGroup->code,
            'purchase_limit_type' => [
                'id' => $employeeGroup->purchase_limit_type_id,
                'name' => PurchaseLimitTypes::getFormattedCaseName($employeeGroup->purchase_limit_type_id),
                'key' => PurchaseLimitTypes::getCaseNameByValue($employeeGroup->purchase_limit_type_id),
            ],
            'limit_reset_type' => [
                'id' => $employeeGroup->limit_reset_type_id,
                'name' => LimitResetTypes::getFormattedCaseName($employeeGroup->limit_reset_type_id),
                'key' => LimitResetTypes::getCaseNameByValue($employeeGroup->limit_reset_type_id),
            ],
            'item_purchase_limit' => $employeeGroup->item_purchase_limit,
            'limit_reset' => $employeeGroup->item_purchase_limit,
        ];
    }
}
