<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByDepartmentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $department = $this->resource;

        $releasedDate = $department['original_created_at'] ?? $department['created_at'];

        return [
            'id' => $department['department_id'],
            'name' => $department['name'],
            'received' => $department['received'] ? CommonFunctions::numberFormat((float) $department['received']) : 0,
            'sold' => $department['sold'] ? CommonFunctions::numberFormat((float) $department['sold']) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $department['online_sold']),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $department['net_sale_amount']),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $department['online_sale_amount']),
            'balance' => CommonFunctions::numberFormat((float) $department['balance']),
            'sell_through' => CommonFunctions::numberFormat((float) $department['sell_through']),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
