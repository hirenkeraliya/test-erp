<?php

declare(strict_types=1);

namespace App\Domains\User\DataObjects;

use App\Domains\User\Enums\UserTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $username,
        public int $type_id,
        public int $employee_id,
        public ?string $password,
    ) {
    }

    public static function rules(Request $request): array
    {
        $userid = null;
        if ('admin.users.update' === $request->route()?->getName()) {
            /** @var string $userid */
            $userid = $request->route()->parameter('userId');
        }

        $rules = [
            'username' => ['required', 'string'],
            'type_id' => ['required', 'integer', 'in:' . UserTypes::getValues()],
            'employee_id' => ['required', 'integer', new Unique('users', 'employee_id', ignore: $userid)],
        ];

        if ('admin.users.store' === $request->route()?->getName()) {
            $rules['password'] = ['required', Password::defaults(), 'confirmed', 'max:20'];
        }

        return $rules;
    }
}
