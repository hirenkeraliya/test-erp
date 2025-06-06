<?php

declare(strict_types=1);

namespace App\Domains\GenuineProductVerification\DataObjects;

use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;
use Spatie\LaravelData\Data;

class GenuineProductVerificationUpdateData extends Data
{
    public function __construct(
        public string $remarks,
        public string $captcha,
    ) {
    }

    /**
     * @return array<string, array<(int|MobileNumber|Exists|string)>>
     */
    public static function rules(Request $request): array
    {
        return [
            'remarks' => ['required', 'string', 'max:5000'],
            'captcha' => ['required', 'captcha'],
        ];
    }
}
