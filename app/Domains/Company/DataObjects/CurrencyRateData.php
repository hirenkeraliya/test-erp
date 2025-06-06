<?php

declare(strict_types=1);

namespace App\Domains\Company\DataObjects;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class CurrencyRateData extends Data
{
    public function __construct(
        public array $currency_data,
        public int $company_id,
    ) {
    }

    public static function rules(): array
    {
        return [
            'currency_data' => ['required', 'array'],
            'currency_data.*.id' => ['required', 'integer', Rule::exists('currency_rates', 'id')],
            'currency_data.*.rate' => ['required', 'numeric', 'min:0.01'],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
        ];
    }
}
