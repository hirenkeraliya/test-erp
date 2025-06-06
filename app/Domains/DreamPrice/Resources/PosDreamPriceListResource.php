<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Resources;

use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\EmployeeGroup;
use App\Models\MemberGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosDreamPriceListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var DreamPrice $dreamPrice */
        $dreamPrice = $this;

        /** @var Collection $dreamPriceProducts */
        $dreamPriceProducts = $dreamPrice->dreamPriceProducts;

        /** @var Collection $memberGroups */
        $memberGroups = $dreamPrice->memberGroups;

        /** @var Collection $employeeGroups */
        $employeeGroups = $dreamPrice->employeeGroups;

        return [
            'id' => $dreamPrice->id,
            'start_date' => $dreamPrice->start_date,
            'end_date' => $dreamPrice->end_date,
            'allow_walk_in_member' => $dreamPrice->allow_walk_in_member,
            'allow_registered_member' => $dreamPrice->allow_registered_member,
            'allow_employee' => $dreamPrice->allow_employee,

            'member_groups' => $memberGroups->map(function ($memberGroup): array {
                /** @var MemberGroup $dreamPriceMemberGroup */
                $dreamPriceMemberGroup = $memberGroup;

                return [
                    'id' => $dreamPriceMemberGroup->id,
                    'name' => $dreamPriceMemberGroup->name,
                ];
            }),

            'employee_groups' => $employeeGroups->map(function ($employeeGroup): array {
                /** @var EmployeeGroup $dreamPriceEmployeeGroup */
                $dreamPriceEmployeeGroup = $employeeGroup;

                return [
                    'id' => $dreamPriceEmployeeGroup->id,
                    'name' => $dreamPriceEmployeeGroup->name,
                ];
            }),

            'products' => $dreamPriceProducts->map(function ($product): array {
                /** @var DreamPriceProduct $dreamPriceProduct */
                $dreamPriceProduct = $product;

                return [
                    'id' => $dreamPriceProduct->id,
                    'product_id' => $dreamPriceProduct->product_id,
                    'product' => $dreamPriceProduct->product,
                    'price' => (float) $dreamPriceProduct->price,
                ];
            }),
        ];
    }
}
