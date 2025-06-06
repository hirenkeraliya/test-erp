<?php

declare(strict_types=1);

namespace App\Domains\Admin\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class AdminData extends Data
{
    public function __construct(
        public string $username,
        public int $employee_id,
        public ?string $password,
        public array $role_ids,
        public ?string $two_factor_secret,
        public ?string $two_factor_recovery_codes,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $adminId = null;
        if (in_array(
            $request->route()?->getName(),
            ['super_admin.admins.update', 'admin.update', 'admin.generate2fa', 'admin.disable2fa']
        )) {
            /** @var string $adminId */
            $adminId = $request->route()?->parameter('adminId');
        }

        $rules = [
            'username' => ['required', 'string', new Unique('admins', 'username', ignore: $adminId)],
            'employee_id' => ['required', 'integer', new Unique('admins', 'employee_id', ignore: $adminId)],
            'role_ids' => ['required', 'array'],
            'two_factor_secret' => ['sometimes', 'string', 'nullable'],
            'two_factor_recovery_codes' => ['sometimes', 'string', 'nullable'],
        ];

        if (! in_array($request->route()?->getName(), ['admin.update', 'admin.generate2fa', 'admin.disable2fa'])) {
            $rules['company_id'] = ['required', 'integer'];
        }

        if ('super_admin.admins.store' === $request->route()?->getName()) {
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
