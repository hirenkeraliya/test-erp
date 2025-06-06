<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\UnitOfMeasure\Resources\PosUnitOfMeasureListResource;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitOfMeasureController extends Controller
{
    /**
     * @return array<string, AnonymousResourceCollection>
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

        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasures = $unitOfMeasureQueries->getWithBasicColumnsAndDerivatives($companyId, $afterUpdatedAt);

        return [
            'unit_of_measures' => PosUnitOfMeasureListResource::collection($unitOfMeasures),
        ];
    }
}
