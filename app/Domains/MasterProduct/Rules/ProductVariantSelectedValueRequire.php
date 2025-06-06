<?php

namespace App\Domains\MasterProduct\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;

class ProductVariantSelectedValueRequire implements ValidationRule
{
    public function __construct(
        protected string $field,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rule = [Rule::requiredIf(true), 'nullable', 'string'];

        $validator = validator([
            $this->field => $value,
        ], [
            $this->field => $rule,
        ]);

        if ($validator->fails()) {
            $fail('The attribute is required.');
        }
    }
}
