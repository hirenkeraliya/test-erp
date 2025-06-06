<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\MemberGroup;

use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\DataObjects\EcommerceMemberGroupData;
use App\Domains\MemberGroup\DataObjects\PaginatedMemberGroupListDataForEcommerce;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\MemberGroup\Resources\EcommerceMemberGroupListResource;
use App\Domains\MemberGroupChannelReference\MemberGroupChannelReferenceQueries;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class MemberGroupController extends Controller
{
    public function __construct(
        protected MemberGroupQueries $memberGroupQueries
    ) {
    }

    public function list(
        PaginatedMemberGroupListDataForEcommerce $paginatedMemberGroupListDataForEcommerce,
        Request $request
    ): array {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $filteredData = [
            'per_page' => $paginatedMemberGroupListDataForEcommerce->per_page,
            'sort_by' => $paginatedMemberGroupListDataForEcommerce->sort_by,
            'search_text' => $paginatedMemberGroupListDataForEcommerce->search_text,
            'sort_direction' => $paginatedMemberGroupListDataForEcommerce->sort_direction,
            'after_updated_at' => $paginatedMemberGroupListDataForEcommerce->after_updated_at,
        ];

        $memberGroups = $this->memberGroupQueries->getListWithMemberIds($filteredData, $saleChannel->getCompanyId());

        return [
            'member_groups' => EcommerceMemberGroupListResource::collection($memberGroups),
            'total_records' => $memberGroups->total(),
            'last_page' => $memberGroups->lastPage(),
            'current_page' => $memberGroups->currentPage(),
            'per_page' => $memberGroups->perPage(),
        ];
    }

    public function getMemberIds(Request $request): array
    {
        $saleChannel = $request->user();

        $memberQueries = resolve(MemberQueries::class);

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'member_group_id' => ['required', 'integer'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'member_group_id' => $validatedData['member_group_id'],
        ];

        $lengthAwarePaginator = $memberQueries->getMemberIdsForSalesChannel($filteredData);

        return [
            'member_ids' => $lengthAwarePaginator->pluck('id'),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function store(EcommerceMemberGroupData $ecommerceMemberGroupData, Request $request): void
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $codeExists = $this->memberGroupQueries->existsByCode($ecommerceMemberGroupData->code);
        if ($codeExists) {
            abort(401, 'code already taken.');
        }

        $nameExists = $this->memberGroupQueries->existsByName($ecommerceMemberGroupData->name);
        if ($nameExists) {
            abort(401, 'name already taken.');
        }

        $memberGroup = $this->memberGroupQueries->addMemberGroupForEcommerce(
            $ecommerceMemberGroupData,
            $saleChannel->company_id
        );

        $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);
        $memberGroupChannelReferenceQueries->firstOrCreate([
            'sale_channel_id' => $saleChannel->id,
            'member_group_id' => $memberGroup->id,
            'external_member_group_id' => $ecommerceMemberGroupData->external_member_group_id,
        ]);
    }

    public function update(
        EcommerceMemberGroupData $ecommerceMemberGroupData,
        Request $request,
        int $externalMemberGroupId
    ): void {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $memberGroupChannelReferenceQueries = resolve(MemberGroupChannelReferenceQueries::class);

        /** @var int $memberGroupId */
        $memberGroupId = $memberGroupChannelReferenceQueries->getByMemberGroupId(
            $externalMemberGroupId,
            $saleChannel->id
        );

        $codeExists = $this->memberGroupQueries->existsByCode($ecommerceMemberGroupData->code, $memberGroupId);
        if ($codeExists) {
            abort(401, 'code already taken.');
        }

        $nameExists = $this->memberGroupQueries->existsByName($ecommerceMemberGroupData->name, $memberGroupId);
        if ($nameExists) {
            abort(401, 'name already taken.');
        }

        $this->memberGroupQueries->updateForEcommerce($ecommerceMemberGroupData, $memberGroupId);
    }
}
