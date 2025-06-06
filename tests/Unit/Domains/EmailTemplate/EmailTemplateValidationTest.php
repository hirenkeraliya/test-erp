<?php

declare(strict_types=1);

use App\Domains\EmailTemplate\DataObjects\EmailTemplateData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('user cannot add if required field missing.', function (): void {
    $request = new Request([
        'name' => null,
        'template_json' => null,
        'html' => null,
    ]);

    EmailTemplateData::validate($request);
})->throws(ValidationException::class);
