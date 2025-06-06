<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Rules\MobileNumber;
use Illuminate\Support\Facades\Cookie;
use Spatie\LaravelData\Data;

class FrontMemberData extends Data
{
    public function __construct(
        public string $first_name,
        public string $mobile_number,
        public string $email,
        public ?string $date_of_birth,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'unique:members,mobile_number', new MobileNumber()],
            'email' => ['required', 'email'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:' . now()->format('Y-m-d')],
        ];
        if (! Cookie::get('member-registration')) {
            $rules['captcha'] = ['required', 'captcha'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'mobile_number.digits' => 'Please enter a valid mobile number.',
            'captcha' => 'Captcha validation failed. Please try again.',
        ];
    }
}
