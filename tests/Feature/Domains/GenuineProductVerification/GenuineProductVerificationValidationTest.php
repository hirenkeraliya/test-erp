<?php

declare(strict_types=1);

use App\Domains\GenuineProductVerification\DataObjects\GenuineProductVerificationData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('type id required validation works while import record.', function (): void {
    $genuineProductVerificationDetails = [
        'name' => '',
        'qr_code' => '',
    ];

    $request = new Request($genuineProductVerificationDetails);

    $request->validate(GenuineProductVerificationData::rules($request));
})->throws(ValidationException::class);
