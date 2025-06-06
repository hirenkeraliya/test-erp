<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Resources;

use App\Domains\HappyHourDiscount\DataPreparer\HappyHourDiscountDataPreparer;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class HappyHourDiscountListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $happyHourDiscount = $this->resource;

        /** @var ?Location $location */
        $location = $happyHourDiscount?->location;

        /** @var Collection $happyHourDiscountTransactions */
        $happyHourDiscountTransactions = $happyHourDiscount->happyHourDiscountTransactions;

        /** @var Carbon $startAtFormat */
        $startAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happyHourDiscount->start_date);
        $startDate = $startAtFormat->format('d-m-Y h:i:s A');

        /** @var Carbon $endAtFormat */
        $endAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happyHourDiscount->end_date);
        $endDate = $endAtFormat->format('d-m-Y h:i:s A');

        return [
            'id' => $happyHourDiscount->id,
            'offline_ids' => HappyHourDiscountDataPreparer::getOfflineIds($happyHourDiscountTransactions),
            'location' => $location?->name,
            'name' => $happyHourDiscount->name,
            'product_type' => ProductTypes::getFormattedCaseName((int) $happyHourDiscount->product_type_id),
            'new_price' => $happyHourDiscount->new_price,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'authorizer_names' => HappyHourDiscountDataPreparer::getAuthorizerNames($happyHourDiscountTransactions),
            'happened_at_dates' => HappyHourDiscountDataPreparer::getHappenedAtDates($happyHourDiscountTransactions),
        ];
    }
}
