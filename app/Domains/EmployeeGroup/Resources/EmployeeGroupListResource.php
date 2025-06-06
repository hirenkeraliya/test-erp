<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Resources;

use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\EmployeeGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeGroupListResource extends JsonResource
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
            'purchase_limit_type_id' => PurchaseLimitTypes::getFormattedCaseName(
                (int) $employeeGroup->purchase_limit_type_id
            ),
            'item_purchase_limit' => $employeeGroup->item_purchase_limit,
            'limit_reset_type_id' => LimitResetTypes::getFormattedCaseName((int) $employeeGroup->limit_reset_type_id),
            'limit_reset' => $this->getLimitReset($employeeGroup->limit_reset_type_id, $employeeGroup->limit_reset),
        ];
    }

    private function getLimitReset(int $limitResetTypeId, int $limitReset): int|string
    {
        if ($limitResetTypeId === LimitResetTypes::BY_WEEK->value) {
            return LimitResetDays::getFormattedCaseName($limitReset);
        }

        return $limitReset;
    }
}
