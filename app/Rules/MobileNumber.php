<?php

declare(strict_types=1);

namespace App\Rules;

use App\CommonFunctions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MobileNumber implements ValidationRule
{
    /**
     * Mobile numbers should be in the range of 8 to 14 digits. The mobile number should be passed with the specified
     * regex.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('app.validate_mobile_number')) {
            return;
        }

        $minDigits = 8;
        $maxDigits = 14;

        if (strlen($value) < $minDigits || strlen($value) > $maxDigits) {
            $fail('Mobile number should be within the range of ' . $minDigits . ' to ' . $maxDigits . ' digits.');
        }

        if (CommonFunctions::checkMobileNumber((string) $value)) {
            return;
        }

        $fail('Please enter a valid mobile number.');
    }
}
