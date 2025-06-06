<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalConnection\Services\ExternalConnectionService;
use App\Domains\Inventory\DataObjects\ExternalInventoryReportListData;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\SellingTypes;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExternalInventoryReportController extends Controller
{
    public function index(): Response
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        return Inertia::render('reports/external_inventory/Index', [
            'externalCompanies' => $externalCompanies,
            'stockTypes' => Types::formattedForSelection(),
            'productStatuses' => ProductStatuses::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('external_inventory'),
            'helpCenterMessages' => 'External inventory reports display product current stock, reserved stock, available stock, inventory value and offering advanced filters, search options, and seamless export capabilities.',
            'sellingTypes' => SellingTypes::formattedForSelection(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    public function fetchExternalInventories(ExternalInventoryReportListData $externalInventoryReportListData): array
    {
        $filterData = $this->getExternalCompanyIdAndUrl($externalInventoryReportListData->toArray());

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getExternalInventories($filterData);
    }

    public function getStoresWarehousesAndRegions(Request $request): array
    {
        $filterData = [
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getStoresWarehousesAndRegions($filterData);
    }

    public function exportExternalInventories(
        string $filename,
        ExternalInventoryReportListData $externalInventoryReportListData
    ): BinaryFileResponse {
        $externalInventoryReportListData = $externalInventoryReportListData->toArray();
        $externalInventoryReportListData['filename'] = $filename;

        $filterData = $this->getExternalCompanyIdAndUrl($externalInventoryReportListData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->exportExternalInventories($filterData);
    }

    private function getExternalCompanyIdAndUrl(array $filterData): array
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getById((int) $filterData['external_company_main_id']);
        $filterData['external_company_id'] = $externalCompany->external_company_id;

        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnection = $externalConnectionQueries->getById($externalCompany->external_connection_id);
        $filterData['url'] = $externalConnection->url;

        return $filterData;
    }

    public function getFilteredExternalInventoryProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryProducts($filterData);
    }

    public function getFilteredExternalInventoryCategories(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryCategories($filterData);
    }

    public function getFilteredExternalInventoryBrands(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryBrands($filterData);
    }

    public function getFilteredExternalInventorySizes(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventorySizes($filterData);
    }

    public function getFilteredExternalInventoryAttributes(Request $request): array
    {
        $filterData = [
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryAttributes($filterData);
    }

    public function getFilteredExternalInventoryColors(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryColors($filterData);
    }

    public function getFilteredExternalInventoryDepartments(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryDepartments($filterData);
    }

    public function getFilteredExternalInventoryArticleNumbers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryArticleNumbers($filterData);
    }

    public function getFilteredExternalInventoryTags(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryTags($filterData);
    }

    public function getFilteredExternalInventoryStyles(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'external_company_main_id' => $request->get('external_company_main_id'),
        ];

        $filterData = $this->getExternalCompanyIdAndUrl($filterData);

        $externalConnectionService = resolve(ExternalConnectionService::class);

        return $externalConnectionService->getFilteredExternalInventoryStyles($filterData);
    }
}
