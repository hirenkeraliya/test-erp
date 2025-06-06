<?php

namespace App\Domains\MasterProduct\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;

class UniqueCodeUpcVariant implements ValidationRule
{
    public function __construct(
        protected string $field,
        protected string $idField,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $index = explode('.', $attribute)[1];
        $id = request()->input(sprintf('variants.%s.%s', $index, $this->idField));

        if (null === $id) {
            $fieldValues = array_column(request()->input('variants'), $this->field);
            $fieldValues = array_filter($fieldValues, fn ($value): bool => null !== $value);

            $count = array_count_values($fieldValues);

            if ($count[$value] > 1) {
                $fail(sprintf('The %s must be unique within the submitted variants.', $this->field));
            }
        } else {
            $rule = Rule::unique('products', $this->field)->ignore($id);

            $validator = validator([
                $this->field => $value,
            ], [
                $this->field => $rule,
            ]);

            if ($validator->fails()) {
                $fail(sprintf('The %s has already been taken.', $this->field));
            }
        }
    }
}
