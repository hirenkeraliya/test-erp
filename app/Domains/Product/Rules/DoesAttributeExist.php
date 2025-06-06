<?php

namespace App\Domains\Product\Rules;

use App\Domains\Attribute\AttributeQueries;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Translation\PotentiallyTranslatedString;

class DoesAttributeExist implements ValidationRule
{
    public function __construct(
        protected AttributeQueries $attributeQueries,
        protected Request $request
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $attributeId, Closure $fail): void
    {
        $arrayIndex = explode('.', $attribute)[1];
        $currentTemplateId = $this->request->custom_field_values[$arrayIndex]['id'];
        $attributeExists = $this->attributeQueries->doesAttributeExist(
            $currentTemplateId,
            $attributeId,
            session('admin_company_id')
        );
        if (! $attributeExists) {
            $fail('This attribute is invalid.');
        }
    }
}
