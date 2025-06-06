<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Resources;

use App\CommonFunctions;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Cashback;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCashbackListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Cashback $cashback */
        $cashback = $this;

        $saleDiscount = $cashback->saleDiscountCashback;
        $saleItemDiscount = $cashback->saleItemDiscountCashback;

        $endDate = Carbon::createFromFormat('Y-m-d', $cashback->end_date);
        $startDate = Carbon::createFromFormat('Y-m-d', $cashback->start_date);

        /** @var Company $company */
        $company = $cashback->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $currencySymbol = $currency->getSymbol();

        $discountValue = $cashback->discount_value . '%';
        if ($cashback->discount_type_id === DiscountTypes::FLAT->value) {
            $discountValue = CommonFunctions::currencySymbolDisplayWithAmount(
                $currencySymbol,
                (float) $cashback->discount_value
            );
        }

        return [
            'id' => $cashback->id,
            'exclude_by_type' => ExcludeByTypes::getFormattedCaseName($cashback->exclude_by_type),
            'name' => $cashback->name,
            'discount_type' => DiscountTypes::getFormattedCaseName($cashback->discount_type_id),
            'discount_value' => $discountValue,
            'minimum_spend_amount' => $cashback->minimum_spend_amount,
            'start_date' => $startDate ? $startDate->format('d-m-Y') : '',
            'end_date' => $endDate ? $endDate->format('d-m-Y') : '',
            'total_used_counts' => ($saleDiscount->count() + $saleItemDiscount->count()),
            'total_discount_amount' => CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            ),
        ];
    }
}
