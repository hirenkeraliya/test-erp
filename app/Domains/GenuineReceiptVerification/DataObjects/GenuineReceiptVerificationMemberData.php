<?php

declare(strict_types=1);

namespace App\Domains\GenuineReceiptVerification\DataObjects;

use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;
use Spatie\LaravelData\Data;

class GenuineReceiptVerificationMemberData extends Data
{
    public function __construct(
        public string $receipt_number,
        public string $name,
        public string $mobile_number,
        public string $email,
        public string $captcha,
    ) {
    }

    /**
     * @return array<string, array<(int|MobileNumber|Exists|string)>>
     */
    public static function rules(Request $request): array
    {
        return [
            'receipt_number' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'numeric', new MobileNumber()],
            'email' => ['required', 'email', 'max:255'],
            'captcha' => ['required', 'captcha'],
        ];
    }
}
