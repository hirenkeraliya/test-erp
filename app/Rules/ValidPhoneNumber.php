<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use libphonenumber\PhoneNumberUtil;

class ValidPhoneNumber implements ValidationRule
{
    public function __construct(
        protected int|string $isdCode
    ) {
    }

    /**
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('app.validate_mobile_number')) {
            return;
        }

        $phoneNumberUtil = PhoneNumberUtil::getInstance();

        $phoneNumber = $phoneNumberUtil->parse('+' . $this->isdCode . $value, null);

        if ($phoneNumberUtil->isValidNumber($phoneNumber)) {
            return;
        }

        $fail('Please enter a valid mobile number for the selected country.');
    }
}
