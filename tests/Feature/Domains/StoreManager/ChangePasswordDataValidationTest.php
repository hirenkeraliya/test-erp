<?php

declare(strict_types=1);

use App\Domains\StoreManager\DataObjects\ChangePasswordData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'Password validations works while updating the password',
    function (string $newPassword, $confirmPassword): void {
        $request = new Request([
            'new_password' => $newPassword,
            'new_password_confirmation' => $confirmPassword,
        ]);

        ChangePasswordData::validate($request);
    }
)->with([['', ''], ['111111', '1111111']])->throws(ValidationException::class);

test('validate password successfully', function (): void {
    $request = new Request([
        'new_password' => '11111111',
        'new_password_confirmation' => '11111111',
    ]);

    ChangePasswordData::validate($request);
    $this->assertTrue(true);
});
