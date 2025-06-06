<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionUpdate\Resources;

use App\Models\Department;
use App\Models\PromoterCommissionUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterCommissionDetailsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PromoterCommissionUpdate $promoterCommissionUpdate */
        $promoterCommissionUpdate = $this;

        /** @var Department $department */
        $department = $promoterCommissionUpdate->department;

        return [
            'offline_id' => $promoterCommissionUpdate->getOfflineId($promoterCommissionUpdate->affected_by_type),
            'product' => $promoterCommissionUpdate->affected_by->product->name,
            'color' => config(
                'app.product_variant'
            ) ? null : $promoterCommissionUpdate->affected_by->product?->color->name ?? 'N/A',
            'size' => config(
                'app.product_variant'
            ) ? null : $promoterCommissionUpdate->affected_by->product?->size->name ?? 'N/A',
            'brand' => config(
                'app.product_variant'
            ) ? $promoterCommissionUpdate->affected_by->product?->masterProduct?->brand->name ?? 'N/A' : $promoterCommissionUpdate->affected_by->product?->brand->name ?? 'N/A',
            'department_id' => $department->name ?? 'N/A',
            'commission_percentage' => $promoterCommissionUpdate->commission_percentage,
            'commission_amount' => $promoterCommissionUpdate->commission_amount,
            'amount' => $promoterCommissionUpdate->amount,
            'product_variant_values' => config(
                'app.product_variant'
            ) ? $promoterCommissionUpdate->affected_by?->product?->productVariantValues ?? [] : [],
        ];
    }
}
