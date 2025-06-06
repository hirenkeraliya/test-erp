<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoTrailingSlashUrls implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('#https?:\/\/\S+\/$#', $value)) {
            $fail('The :attribute field must not contain URLs with a trailing slash.');
        }
    }
}
