<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\VoidSaleReason\DataObjects\VoidSaleReasonData;
use App\Domains\VoidSaleReason\Exports\VoidSaleReasonExport;
use App\Domains\VoidSaleReason\Resources\VoidSaleListResource;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoidSaleReasonController extends Controller
{
    public function __construct(
        protected VoidSaleReasonQueries $voidSaleReasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('void_sale_reasons/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('void_sale_reason'),
        ]);
    }

    public function fetchVoidSaleReasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->voidSaleReasonQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => VoidSaleListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('void_sale_reasons/Manage', [
            'types' => SaleReturnOrVoidSaleReasonTypes::formattedForSelection(),
            'staticTypes' => SaleReturnOrVoidSaleReasonTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(VoidSaleReasonData $voidSaleReasonData): RedirectResponse
    {
        $this->voidSaleReasonQueries->addNew($voidSaleReasonData, session('admin_company_id'));

        return to_route('admin.void_sale_reasons.index')->with('success', 'Void sale reason added successfully.');
    }

    public function edit(int $voidSaleReasonId): Response
    {
        $voidSaleReason = $this->voidSaleReasonQueries->getById($voidSaleReasonId, session('admin_company_id'));

        $voidSaleReason['types'] = $voidSaleReason->voidSaleReasonTypes->map(
            fn ($voidSaleReasonType): array => [
                'id' => $voidSaleReasonType->type_id,
                'name' => SaleReturnOrVoidSaleReasonTypes::getFormattedCaseName($voidSaleReasonType->type_id),
            ]
        );

        return Inertia::render('void_sale_reasons/Manage', [
            'voidSaleReason' => $voidSaleReason,
            'types' => SaleReturnOrVoidSaleReasonTypes::formattedForSelection(),
            'staticTypes' => SaleReturnOrVoidSaleReasonTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(VoidSaleReasonData $voidSaleReasonData, int $voidSaleReasonId): RedirectResponse
    {
        $this->voidSaleReasonQueries->update($voidSaleReasonData, $voidSaleReasonId, session('admin_company_id'));

        return to_route('admin.void_sale_reasons.index')->with('success', 'Void sale reason updated successfully.');
    }

    public function exportVoidSaleReasons(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $voidSaleReasons = $this->voidSaleReasonQueries->getVoidSaleReasonsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new VoidSaleReasonExport($voidSaleReasons), $filename);
    }
}
