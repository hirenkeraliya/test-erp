<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Resources;

use App\Domains\HappyHourDiscount\DataPreparer\HappyHourDiscountDataPreparer;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Models\Director;
use App\Models\Employee;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\Location;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class HappyHourDiscountListApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var HappyHourDiscount $happyHourDiscount */
        $happyHourDiscount = $this;

        /** @var Location $location */
        $location = $happyHourDiscount->getLocation();

        /** @var Collection $brands */
        $brands = $happyHourDiscount->brands;

        /** @var Collection $styles */
        $styles = $happyHourDiscount->styles;

        /** @var Collection $categories */
        $categories = $happyHourDiscount->categories;

        /** @var Collection $departments */
        $departments = $happyHourDiscount->departments;

        /** @var Collection $happyHourDiscountTransactions */
        $happyHourDiscountTransactions = $happyHourDiscount->happyHourDiscountTransactions;

        /** @var HappyHourDiscountTransaction $happyHourDiscountTransaction */
        $happyHourDiscountTransaction = $happyHourDiscount->happyHourDiscountTransaction;

        /** @var Director|StoreManager $authorizer */
        $authorizer = $happyHourDiscountTransaction->authorizer;

        /** @var ?Employee $employee */
        $employee = $authorizer->employee;

        return [
            'id' => $happyHourDiscount->id,
            'offline_id' => $happyHourDiscountTransaction->offline_id,
            'offline_ids' => HappyHourDiscountDataPreparer::getOfflineIds($happyHourDiscountTransactions),
            'name' => $happyHourDiscount->name,
            'new_price' => $happyHourDiscount->new_price,
            'store' => $location->name,
            'location' => $location->name,
            'product_type_id' => $happyHourDiscount->product_type_id,
            'product_type' => ProductTypes::getCaseNameByValue($happyHourDiscount->product_type_id),
            'authorizer_id' => $happyHourDiscountTransaction->authorizer_id,
            'brand_ids' => $brands->pluck('id')->toArray(),
            'style_ids' => $styles->pluck('id')->toArray(),
            'category_ids' => $categories->pluck('id')->toArray(),
            'department_ids' => $departments->pluck('id')->toArray(),
            'authorizer_name' => $employee instanceof Employee ? $employee->getFullName() . ' (' . $happyHourDiscountTransaction->authorizer_type . ')' : null,
            'authorizer_names' => HappyHourDiscountDataPreparer::getAuthorizerNames($happyHourDiscountTransactions),
            'start_date' => $happyHourDiscount->start_date,
            'end_date' => $happyHourDiscount->end_date,
            'happened_at' => $happyHourDiscountTransaction->happened_at,
            'happened_at_dates' => HappyHourDiscountDataPreparer::getHappenedAtDatesForApi(
                $happyHourDiscountTransactions
            ),
        ];
    }
}
