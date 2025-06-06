<?php

declare(strict_types=1);

namespace App\Domains\GenuineProductVerification\DataObjects;

use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;
use Spatie\LaravelData\Data;

class GenuineProductVerificationData extends Data
{
    public function __construct(
        public string $name,
        public string $mobile_number,
        public string $email,
        public string $qr_code,
        public string $receipt_number,
        public string $captcha,
    ) {
    }

    /**
     * @return array<string, array<(int|MobileNumber|Exists|string)>>
     */
    public static function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'numeric', new MobileNumber()],
            'email' => ['required', 'email', 'max:255'],
            'receipt_number' => ['required', 'string', 'max:50'],
            'qr_code' => ['required', 'string', 'max:255'],
            'captcha' => ['required', 'captcha'],
        ];
    }
}
