<?php

declare(strict_types=1);

use App\Domains\Director\DataObjects\ChangePasscodeData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test(
    'Passcode validations works while updating the passcode',
    function (string $newPassword, $confirmPassword): void {
        $request = new Request([
            'new_password' => $newPassword,
            'new_password_confirmation' => $confirmPassword,
        ]);

        ChangePasscodeData::validate($request);
    }
)->with([['', ''], ['111111', '1111111']])->throws(ValidationException::class);

test('Director change passcode request is validated.', function (): void {
    $request = new Request([
        'new_passcode' => '111111',
        'new_passcode_confirmation' => '111111',
    ]);

    ChangePasscodeData::validate($request);
    $this->assertTrue(true);
});
