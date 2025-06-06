<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;

class CurrencyController extends Controller
{
    public function getCurrencySymbol(): array
    {
        return [
            'currency_symbol' => config('app.currency_symbol'),
        ];
    }
}
