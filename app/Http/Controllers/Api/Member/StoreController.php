<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Member;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Store\DataObjects\MemberAppStoreListData;
use App\Domains\Store\Resources\StoreListForMemberAppResource;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function getPaginatedStoreList(MemberAppStoreListData $memberAppStoreListData, Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $companyId = $member->company_id;

        $filterData = [
            'search_text' => $memberAppStoreListData->search_text ?? '',
            'per_page' => $memberAppStoreListData->per_page ?? '',
            'page' => $memberAppStoreListData->page ?? '',
            'sort_by' => $memberAppStoreListData->sort_by ?? '',
            'sort_direction' => $memberAppStoreListData->sort_direction ?? '',
            'type_id' => LocationTypes::STORE->value,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->listQuery($filterData, $companyId);

        return [
            'stores' => StoreListForMemberAppResource::collection($locations),
            'locations' => StoreListForMemberAppResource::collection($locations),
            'total_records' => $locations->total(),
            'last_page' => $locations->lastPage(),
            'current_page' => $locations->currentPage(),
            'per_page' => $locations->perPage(),
        ];
    }
}
