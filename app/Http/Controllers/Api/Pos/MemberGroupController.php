<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\MemberGroup\DataObjects\PaginatedMemberGroupListDataForPos;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class MemberGroupController extends Controller
{
    /**
     * @return mixed[]
     */
    public function getPaginateMemberGroup(
        Request $request,
        PaginatedMemberGroupListDataForPos $paginatedMemberGroupListDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $memberGroupQueries = resolve(MemberGroupQueries::class);

        $filteredData = [
            'per_page' => $paginatedMemberGroupListDataForPos->per_page,
            'sort_by' => $paginatedMemberGroupListDataForPos->sort_by,
            'search_text' => $paginatedMemberGroupListDataForPos->search_text,
            'sort_direction' => $paginatedMemberGroupListDataForPos->sort_direction,
            'after_updated_at' => $paginatedMemberGroupListDataForPos->after_updated_at,
        ];

        $lengthAwarePaginator = $memberGroupQueries->listQuery($filteredData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }
}
