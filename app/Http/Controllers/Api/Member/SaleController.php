<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Member\Resources\MemberSaleResource;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedSaleList(Request $request): array
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,number'],
            'sort_direction' => ['sometimes', 'string'],
        ]);

        $filteredData = [
            'per_page' => $request->per_page,
            'sort_by' => $request->sort_by,
            'sort_direction' => $request->sort_direction,
        ];

        /** @var Member $member */
        $member = $request->user();

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getPaginatedSaleListForMemberApi($filteredData, $member->id);

        return [
            'sales' => MemberSaleResource::collection($sales),
            'total_records' => $sales->total(),
            'last_page' => $sales->lastPage(),
            'current_page' => $sales->currentPage(),
            'per_page' => $sales->perPage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSaleDetails(Request $request, int $saleId): array
    {
        /** @var Member $member */
        $member = $request->user();

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSaleDetailsById($saleId, $member->id);

        return [
            'sales' => MemberSaleResource::collection($sales),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getStatuses(): array
    {
        return [
            'sale_status' => SaleStatus::getList(),
        ];
    }
}
