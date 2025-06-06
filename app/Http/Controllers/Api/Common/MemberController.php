<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;

class MemberController extends Controller
{
    public function getMobileNumberRegex(): array
    {
        return [
            'regex' => config('app.mobile_number_regex'),
        ];
    }
}
