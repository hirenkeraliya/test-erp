<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Denomination\DenominationQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DenominationController extends Controller
{
    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function getList(Request $request): array
    {
        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $denominationQueries = resolve(DenominationQueries::class);
        $denominationList = $denominationQueries->getList($companyId, $afterUpdatedAt);

        return [
            'denominations' => $denominationList,
        ];
    }
}
