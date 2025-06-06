<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\CashMovementReason\Resources\PosCashMovementReasonListResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashMovementReasonController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
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

        $cashMovementReasonQueries = resolve(CashMovementReasonQueries::class);
        $cashMovementReasonsList = $cashMovementReasonQueries->getList($companyId, $afterUpdatedAt);

        return [
            'cash_movement_reasons' => PosCashMovementReasonListResource::collection($cashMovementReasonsList),
        ];
    }
}
