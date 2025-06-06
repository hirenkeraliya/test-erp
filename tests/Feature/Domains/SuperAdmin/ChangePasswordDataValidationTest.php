<?php

declare(strict_types=1);

use App\Domains\SuperAdmin\DataObjects\ChangePasswordData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'change password validation works while changing the password',
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

test('Change password request is validated.', function (): void {
    $request = new Request([
        'current_password' => '1234567812121',
        'new_password' => '12345678',
        'new_password_confirmation' => '12345678',
    ]);

    ChangePasswordData::validate($request);
    $this->assertTrue(true);
});
