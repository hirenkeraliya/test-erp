<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidatePrimaryAddress implements ValidationRule
{
    /**
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $requestData = request()->all();

        $memberAddresses = (array) ($requestData['member_addresses'] ?? []);

        $primaryCount = collect($memberAddresses)->where('is_primary', true)->count();

        if (0 === $primaryCount) {
            $fail('At least one address must be marked as primary.');
        }

        if ($primaryCount > 1) {
            $fail('Exactly one address must be marked as primary.');
        }
    }
}
