<?php

declare(strict_types=1);

use App\Domains\Admin\DataObjects\ChangePasswordData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'Password validations works while updating the password',
    function (?string $currentPassword, string $newPassword, $confirmPassword): void {
        $request = new Request([
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'new_password_confirmation' => $confirmPassword,
        ]);

        ChangePasswordData::validate($request);
    }
)->with([
    [null, '', ''],
    ['123456', '123456', '123456'],
    ['123456', '111111', '1111111'],
])->throws(ValidationException::class);

test('Admin change password request is validated.', function (): void {
    $request = new Request([
        'current_password' => '12345678',
        'new_password' => '11111111',
        'new_password_confirmation' => '11111111',
    ]);

    ChangePasswordData::validate($request);
    $this->assertTrue(true);
});
