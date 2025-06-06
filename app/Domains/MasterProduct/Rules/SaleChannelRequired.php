<?php

namespace App\Domains\MasterProduct\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SaleChannelRequired implements ValidationRule
{
    public function __construct(
        protected ?array $variants = null
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $index = explode('.', $attribute)[1] ?? null;

        if (null === $this->variants || ! isset($this->variants[$index])) {
            return;
        }

        if (! empty($this->variants[$index]['is_available_in_ecommerce']) && empty($value)) {
            $fail('The sale channels is required when available in eCommerce.');
        }
    }
}
