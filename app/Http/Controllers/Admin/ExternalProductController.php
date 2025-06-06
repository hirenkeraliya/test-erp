<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\ExternalProduct\Resources\AdminExternalProductListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class ExternalProductController extends Controller
{
    public function __construct(
        protected ExternalProductQueries $externalProductQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('external_products/Index', [
            'externalProductStatuses' => ExternalProductStatuses::getList(),
            'staticExternalProductStatuses' => ExternalProductStatuses::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('product'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchExternalProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range'),
        ];

        $lengthAwarePaginator = $this->externalProductQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminExternalProductListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function approved(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $externalProductIds = $request->selectedRecords;

        $this->externalProductQueries->markAsApproved($externalProductIds, $user, session('admin_company_id'));

        return to_route('admin.external_products.index')->with('success', 'Product approved successfully.');
    }

    public function rejected(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $externalProductIds = $request->selectedRecords;

        $this->externalProductQueries->markAsRejected($externalProductIds, $user, session('admin_company_id'));

        return to_route('admin.external_products.index')->with('success', 'Product rejected successfully.');
    }
}
