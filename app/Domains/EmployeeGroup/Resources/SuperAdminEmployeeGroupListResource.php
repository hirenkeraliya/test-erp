<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Resources;

use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\Company;
use App\Models\EmployeeGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuperAdminEmployeeGroupListResource extends JsonResource
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

        /** @var Company $company */
        $company = $employeeGroup->company;

        return [
            'id' => $employeeGroup->id,
            'company' => $company->name,
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
