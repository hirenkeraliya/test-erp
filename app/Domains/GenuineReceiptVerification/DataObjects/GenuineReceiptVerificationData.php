<?php

declare(strict_types=1);

namespace App\Domains\GenuineReceiptVerification\DataObjects;

use Spatie\LaravelData\Data;

class GenuineReceiptVerificationData extends Data
{
    public function __construct(
        public string $receipt_number,
        public string $captcha,
    ) {
    }

    public static function rules(): array
    {
        return [
            'receipt_number' => ['required', 'string', 'max:50'],
            'captcha' => ['required', 'captcha'],
        ];
    }
}
