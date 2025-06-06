<?php

declare(strict_types=1);

use App\Domains\EmailRecipient\DataObjects\EmailRecipientData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('email recipient validation works', function (): void {
    $request = new Request([
        'email_type_id' => '',
        'receiver_name' => '',
        'receiver_email' => '',
    ]);

    EmailRecipientData::validate($request);
})->throws(ValidationException::class);
