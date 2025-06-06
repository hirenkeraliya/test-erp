<?php

declare(strict_types=1);

namespace App\Domains\Promoter\DataObjects;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;

class ChangePasswordData extends Data
{
    public function __construct(
        public string $new_password
    ) {
    }

    /**
     * @return array<string, array<Password|string|null>>
     */
    public static function rules(): array
    {
        return [
            'new_password' => ['required', 'string', 'confirmed', 'max:20', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'new_password' => 'A password must be at least 8 characters long and include a combination of uppercase and lowercase letters, numbers, and symbols.',
            'new_password.confirmed' => 'The confirmed password does not match the original password. Please re-enter your password and confirm it.',
        ];
    }
}
