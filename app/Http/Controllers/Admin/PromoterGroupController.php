<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\PromoterGroup\DataObjects\PromoterGroupData;
use App\Domains\PromoterGroup\Exports\PromoterGroupExport;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\PromoterGroup\Resources\PromoterGroupListsResource;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromoterGroupController extends Controller
{
    public function __construct(
        protected PromoterGroupQueries $promoterGroupQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('promoter_group/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('promoter_group'),
        ]);
    }

    public function fetchPromoterGroups(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->promoterGroupQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PromoterGroupListsResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('promoter_group/Manage', [
            'types' => SaleReturnOrVoidSaleReasonTypes::formattedForSelection(),
            'staticTypes' => SaleReturnOrVoidSaleReasonTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(PromoterGroupData $promoterGroupData, Request $request): RedirectResponse
    {
        /** @var Admin $user */
        $user = $request->user();

        $this->promoterGroupQueries->addNew($promoterGroupData, session('admin_company_id'), $user);

        return to_route('admin.promoter_groups.index')->with(
            'success',
            'The promoter group has been added successfully.'
        );
    }

    public function edit(int $promoterGroupId): Response
    {
        return Inertia::render('promoter_group/Manage', [
            'promoterGroup' => $this->promoterGroupQueries->getById($promoterGroupId, session('admin_company_id')),
            'types' => SaleReturnOrVoidSaleReasonTypes::formattedForSelection(),
            'staticTypes' => SaleReturnOrVoidSaleReasonTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(PromoterGroupData $promoterGroupData, int $promoterGroupId): RedirectResponse
    {
        $this->promoterGroupQueries->update($promoterGroupData, $promoterGroupId, session('admin_company_id'));

        return to_route('admin.promoter_groups.index')->with(
            'success',
            'The promoter group has been updated successfully.'
        );
    }

    public function exportPromoterGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $promoterGroups = $this->promoterGroupQueries->getPromoterGroupsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new PromoterGroupExport($promoterGroups), $filename);
    }

    /**
     * @return array<string, Collection>
     */
    public function getPromoterGroupsList(): array
    {
        return [
            'promoterGroups' => $this->promoterGroupQueries->getPromoterGroupByCompanyId(session('admin_company_id')),
        ];
    }
}
