<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterSalesListResource extends JsonResource
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
        $promoter = $this->resource;

        return [
            'date' => $promoter->happened_at,
            'item_sold' => CommonFunctions::truncateDecimal((float) $promoter->total_units_sold),
            'sale_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $this->currencySymbol,
                (float) $promoter->total_amount_sold
            ),
            'item_returned' => CommonFunctions::truncateDecimal((float) $promoter->total_units_returned),
            'return_amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $this->currencySymbol,
                (float) $promoter->total_returned_amount
            ),
            'amount' => CommonFunctions::currencySymbolDisplayWithAmount(
                $this->currencySymbol,
                (float) $promoter->total_amount
            ),
        ];
    }
}
