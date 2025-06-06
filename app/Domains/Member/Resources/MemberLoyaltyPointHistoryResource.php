<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\CommonFunctions;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\VoidSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberLoyaltyPointHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var LoyaltyPointUpdate $loyaltyPointUpdate */
        $loyaltyPointUpdate = $this;

        /** @var Admin|Sale|SaleReturn|VoidSale|Member|SaleItem|SaleReturnItem $affectedBy */
        $affectedBy = $loyaltyPointUpdate->affectedBy;

        $description = 'Expired';

        if ($loyaltyPointUpdate->type_id === LoyaltyPointUpdateTypes::MANUAL_UPDATE->value) {
            $description = 'Added By Admin';
        }

        if ($affectedBy instanceof Admin) {
            /** @var Employee $employee */
            $employee = $affectedBy->employee;
            $description = CommonFunctions::stringTitleLowerCase($employee->getFullName());
        }

        if ($affectedBy instanceof Member) {
            $description = 'Welcome Benefits';
        }

        if ($affectedBy instanceof Sale) {
            $description = $affectedBy->offline_sale_id;
        }

        if ($affectedBy instanceof SaleItem) {
            $description = $affectedBy->sale?->offline_sale_id;
        }

        if ($affectedBy instanceof SaleReturn) {
            $description = $affectedBy->offline_sale_return_id;
        }

        if ($affectedBy instanceof SaleReturnItem) {
            $description = $affectedBy->saleReturn?->offline_sale_return_id;
        }

        if ($affectedBy instanceof VoidSale) {
            $description = $affectedBy->sale_id . ' (' . $affectedBy->void_sale_number . ')';
        }

        $module = 'System Generated';
        if (null !== $loyaltyPointUpdate->affected_by_type) {
            $module = CommonFunctions::stringTitleLowerCase($loyaltyPointUpdate->affected_by_type);
        }

        $happenedAt = null;
        if ($loyaltyPointUpdate->happened_at) {
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $loyaltyPointUpdate->happened_at);
            $happenedAt = $happenedAtFormat->format('d-m-Y h:i:s A');
        }

        return [
            'description' => $description,
            'module' => $module,
            'points' => $loyaltyPointUpdate->points,
            'closing_loyalty_points_balance' => $loyaltyPointUpdate->closing_loyalty_points_balance,
            'happened_at' => $happenedAt,
        ];
    }
}
