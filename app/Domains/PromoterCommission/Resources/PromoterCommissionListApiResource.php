<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Resources;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterCommissionListApiResource extends JsonResource
{
    public function __construct(
        $resource,
        protected string $currencySymbol
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $promoterCommissionUpdate = $this->resource;
        $promoterCommissionUpdateData = [];

        if ($promoterCommissionUpdate->affected_by_type === ModelMapping::SALE_ITEM->name) {
            $saleItems = $promoterCommissionUpdate->affected_by;
            $sale = $saleItems->sale;

            $promoterCommissionUpdateData = [
                'id' => $promoterCommissionUpdate->id,
                'receipt_no' => $sale->offline_sale_id,
                'unit_sold' => CommonFunctions::truncateDecimal((float) $saleItems->quantity),
                'amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $this->currencySymbol,
                    $promoterCommissionUpdate->amount
                ),
                'commission_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $this->currencySymbol,
                    $promoterCommissionUpdate->commission_amount,
                    false,
                    4
                ),
                'status' => ModelMapping::SALE->name,
            ];
        }

        if ($promoterCommissionUpdate->affected_by_type === ModelMapping::SALE_RETURN_ITEM->name) {
            $saleReturnItem = $promoterCommissionUpdate->affected_by;
            $saleReturn = $saleReturnItem->saleReturn;

            $promoterCommissionUpdateData = [
                'id' => $promoterCommissionUpdate->id,
                'receipt_no' => $saleReturn->offline_sale_return_id,
                'unit_return' => CommonFunctions::truncateDecimal((float) $saleReturnItem->quantity),
                'amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $this->currencySymbol,
                    $promoterCommissionUpdate->amount,
                    true
                ),
                'commission_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $this->currencySymbol,
                    $promoterCommissionUpdate->commission_amount,
                    true,
                    4
                ),
                'status' => ModelMapping::SALE_RETURN->name,
            ];
        }

        return $promoterCommissionUpdateData;
    }
}
