<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Currency\CurrencyQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function getConfiguration(Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($member->company_id);

        return [
            'currency_symbol' => $currency->getSymbol(),
        ];
    }
}
