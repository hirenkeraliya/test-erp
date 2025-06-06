<?php

declare(strict_types=1);

namespace App\Domains\CurrencyRate\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyRatesListResource extends JsonResource
{
    public function toArray($request): array
    {
        $currencyRate = $this->resource;

        return [
            'id' => $currencyRate->id,
            'name' => $currencyRate->currency->name,
            'symbol' => $currencyRate->currency->symbol,
            'code' => $currencyRate->currency->code,
            'rate' => $currencyRate->rate,
        ];
    }
}
