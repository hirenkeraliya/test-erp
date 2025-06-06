<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class StyleController extends Controller
{
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;
        /** @var Cashier $cashier */
        $cashier = $request->user();
        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $styleQueries = resolve(StyleQueries::class);
        $styles = $styleQueries->getByCompanyId($companyId, $afterUpdatedAt);

        return [
            'styles' => $styles,
        ];
    }
}
