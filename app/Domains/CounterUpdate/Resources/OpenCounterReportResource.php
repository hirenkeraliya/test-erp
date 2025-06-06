<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Resources;

use App\CommonFunctions;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpenCounterReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $this;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        /** @var Cashier $cashier */
        $cashier = $counterUpdate->cashier;

        /** @var Employee $employee */
        $employee = $cashier->employee;

        /** @var Company $company */
        $company = $location->company;

        /** @var Country $country */
        $country = $company->defaultCountry;

        /** @var Currency $currency */
        $currency = $country->currency;

        $currencySymbol = $currency->getSymbol();

        return [
            'id' => $counterUpdate->id,
            'location' => $location->name,
            'counter_name' => $counter->name,
            'cashier_name' => $employee->getFullName(),
            'opening_balance' => CommonFunctions::currencySymbolDisplayWithAmount($currencySymbol,
                (float) $counterUpdate->opening_balance
            ),
        ];
    }
}
