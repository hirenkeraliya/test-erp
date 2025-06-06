<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SaleReturnReasonController extends Controller
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

        $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);
        $saleReturnReasonsList = $saleReturnReasonQueries->getListForPOSOrOrders(
            $companyId,
            afterUpdatedAt: $afterUpdatedAt
        );

        return [
            'sale_return_reasons' => $saleReturnReasonsList,
        ];
    }
}
