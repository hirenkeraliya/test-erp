<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleReturnReason\DataObjects\SaleReturnReasonData;
use App\Domains\SaleReturnReason\Exports\SaleReturnReasonExport;
use App\Domains\SaleReturnReason\Resources\SaleReturnReasonListResource;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReturnReasonController extends Controller
{
    public function __construct(
        protected SaleReturnReasonQueries $saleReturnReasonQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('sale_return_reasons/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('sale_return_reason'),
        ]);
    }

    public function fetchSaleReturnReasons(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->saleReturnReasonQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleReturnReasonListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $companyId = session('admin_company_id');
        [$stores, $warehouses] = $this->getStoresAndWarehouses($companyId);

        return Inertia::render('sale_return_reasons/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'types' => SaleReturnOrVoidSaleReasonTypes::formattedForSelection(),
            'staticTypes' => SaleReturnOrVoidSaleReasonTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::formattedForSelection(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(SaleReturnReasonData $saleReturnReasonData): RedirectResponse
    {
        $this->checkRequestDetails($saleReturnReasonData);

        $this->saleReturnReasonQueries->addNew($saleReturnReasonData, session('admin_company_id'));

        return to_route('admin.sale_return_reasons.index')->with(
            'success',
            'The sale return reason has been added successfully.'
        );
    }

    public function edit(int $saleReturnReasonId): Response
    {
        $companyId = session('admin_company_id');
        $saleReturnReason = $this->saleReturnReasonQueries->getById($saleReturnReasonId, $companyId);
        [$stores, $warehouses] = $this->getStoresAndWarehouses($companyId);

        $saleReturnReason['types'] = $saleReturnReason->saleReturnReasonTypes->map(
            fn ($saleReturnReasonType): array => [
                'id' => $saleReturnReasonType->type_id,
                'name' => SaleReturnOrVoidSaleReasonTypes::getFormattedCaseName($saleReturnReasonType->type_id),
            ]
        );

        return Inertia::render('sale_return_reasons/Manage', [
            'saleReturnReason' => $saleReturnReason,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'types' => SaleReturnOrVoidSaleReasonTypes::formattedForSelection(),
            'staticTypes' => SaleReturnOrVoidSaleReasonTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::formattedForSelection(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(SaleReturnReasonData $saleReturnReasonData, int $saleReturnReasonId): RedirectResponse
    {
        $this->checkRequestDetails($saleReturnReasonData);

        $this->saleReturnReasonQueries->update(
            $saleReturnReasonData,
            $saleReturnReasonId,
            session('admin_company_id')
        );

        return to_route('admin.sale_return_reasons.index')->with('success', 'Sale return reason updated successfully.');
    }

    public function checkRequestDetails(SaleReturnReasonData $saleReturnReasonData): void
    {
        if (! $saleReturnReasonData->location_id) {
            return;
        }

        $locationQueries = resolve(LocationQueries::class);
        if (! $locationQueries->doesLocationExist(
            session('admin_company_id'),
            $saleReturnReasonData->location_id
        )) {
            throw new RedirectBackWithErrorException(
                'The specified store or warehouse is not available in our records.'
            );
        }
    }

    public function exportSaleReturnReasons(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $saleReturnReasons = $this->saleReturnReasonQueries->getSaleReturnReasonsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new SaleReturnReasonExport($saleReturnReasons), $filename);
    }

    /**
     * @return mixed[]
     */
    private function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $stores = $locationQueries->getStoreWithBasicColumns($companyId);
        $warehouses = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        return [$stores, $warehouses];
    }
}
