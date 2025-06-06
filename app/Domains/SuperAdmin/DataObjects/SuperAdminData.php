<?php

declare(strict_types=1);

namespace App\Domains\SuperAdmin\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class SuperAdminData extends Data
{
    public function __construct(
        public string $username,
        public string $name,
        public ?string $password,
        public string $email,
        public ?string $two_factor_secret,
        public ?string $two_factor_recovery_codes,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $superAdminId = null;
        if (in_array(
            $request->route()?->getName(),
            ['super_admin.super_admins.update', 'super_admin.disable2fa', 'super_admin.generate2fa']
        )) {
            /** @var string $superAdminId */
            $superAdminId = $request->route()?->parameter('superAdminId');
        }

        $rules = [
            'username' => ['required', 'string'],
            'name' => ['required', 'string'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                new Unique('super_admins', 'email', ignore: $superAdminId),
            ],
            'two_factor_secret' => ['sometimes', 'string', 'nullable'],
            'two_factor_recovery_codes' => ['sometimes', 'string', 'nullable'],
        ];

        if ('super_admin.super_admins.store' === $request->route()?->getName()) {
            $rules['password'] = ['required', Password::defaults(), 'confirmed', 'max:20'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password' => 'A password must be at least 8 characters long and include a combination of uppercase and lowercase letters, numbers, and symbols.',
            'password.confirmed' => 'The confirmed password does not match the original password. Please re-enter your password and confirm it.',
        ];
    }
}
