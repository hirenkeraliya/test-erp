<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\City\CityQueries;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\DataObjects\LocationData;
use App\Domains\Location\DataObjects\LocationListData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\Exports\LocationExport;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Resources\LocationListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Region\RegionQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\State\StateQueries;
use App\Domains\Store\Enums\StoreTimings;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class LocationController extends Controller
{
    public function __construct(
        protected LocationQueries $locationQueries
    ) {
    }

    public function index(): Response
    {
        $companyQueries = resolve(CompanyQueries::class);
        $companyIOICityAndTRXMallConfiguration = $companyQueries->getIOICityAndTRXMallConfiguration(
            (int) session('admin_company_id')
        );

        return Inertia::render('locations/Index', [
            'companyIOICityMallConfiguration' => $companyIOICityAndTRXMallConfiguration?->enable_ioi_city_mall_integration ?? null,
            'companyTRXMallConfiguration' => $companyIOICityAndTRXMallConfiguration?->enable_trx_mall_integration ?? null,
            'exportPermission' => PermissionList::getExportPermissionName('location'),
            'locationTypes' => LocationTypes::getList(),
            'staticLocationTypes' => LocationTypes::generateStaticCasesArray(),
        ]);
    }

    public function fetchLocations(LocationListData $locationListData): array
    {
        $companyId = (int) session('admin_company_id');
        $filterData = [
            'type_id' => $locationListData->type_id ?? null,
            'search_text' => $locationListData->search_text ?? null,
            'sort_by' => $locationListData->sort_by ?? null,
            'sort_direction' => $locationListData->sort_direction ?? null,
            'per_page' => $locationListData->per_page ?? null,
        ];

        $lengthAwarePaginator = $this->locationQueries->listQuery($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => LocationListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $regionQueries = resolve(RegionQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $companyId = (int) session('admin_company_id');
        $company = $companyQueries->getCountries($companyId);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId((int) session('admin_company_id'));

        return Inertia::render('locations/Manage', [
            'brands' => $brandQueries->getCompanyBrands($companyId),
            'regions' => $regionQueries->getRegionByCompanyId($companyId),
            'countries' => $company->countries,
            'storeTimings' => [
                'openTime' => StoreTimings::OPEN_TIME->value,
                'closeTime' => StoreTimings::CLOSE_TIME->value,
            ],
            'locationTypes' => LocationTypes::getList(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'saleChannels' => $saleChannels,
            'allowSmartTransfer' => config('app.allow_smart_transfer'),
        ]);
    }

    public function store(LocationData $locationData): RedirectResponse
    {
        $this->checkRequestDetails($locationData);

        DB::beginTransaction();

        try {
            $this->locationQueries->addNew($locationData, session('admin_company_id'));

            DB::commit();

            return to_route('admin.locations.index')
                ->with('success', 'The location added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Location', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $locationId): Response
    {
        $companyId = (int) session('admin_company_id');
        $brandQueries = resolve(BrandQueries::class);

        $location = $this->locationQueries->getByIdWithBrands($locationId, $companyId, $brandQueries);

        $regionQueries = resolve(RegionQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $stateQueries = resolve(StateQueries::class);
        $cityQueries = resolve(CityQueries::class);

        $company = $companyQueries->getCountries($companyId);
        $states = $location->country ? $stateQueries->getByCountryId($location->country->id) : collect([]);
        $cities = $location->state ? $cityQueries->getByStateId($location->state->id) : collect([]);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId((int) session('admin_company_id'));

        return Inertia::render('locations/Manage', [
            'location' => $location,
            'countries' => $company->countries,
            'states' => $states->map(fn ($state): array => [
                'id' => $state->id,
                'name' => $state->name,
            ]),
            'cities' => $cities->map(fn ($city): array => [
                'id' => $city->id,
                'name' => $city->name,
            ]),
            'brands' => $brandQueries->getCompanyBrands($companyId),
            'regions' => $regionQueries->getRegionByCompanyId($companyId),
            'storeTimings' => [
                'openTime' => StoreTimings::OPEN_TIME->value,
                'closeTime' => StoreTimings::CLOSE_TIME->value,
            ],
            'locationTypes' => LocationTypes::getList(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'saleChannels' => $saleChannels,
            'allowSmartTransfer' => config('app.allow_smart_transfer'),
        ]);
    }

    public function update(LocationData $locationData, int $locationId): RedirectResponse
    {
        $this->checkRequestDetails($locationData);

        DB::beginTransaction();

        try {
            $this->locationQueries->update($locationData, $locationId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.locations.index')
                ->with('success', 'The Location updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Location', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function generateQrCode(int $locationId): ?HtmlString
    {
        $location = $this->validateLocationIsStore($locationId);
        if (! $location instanceof Location) {
            abort(412, 'The selected location is not a store.');
        }

        $qrCode = QrCode::style('round')->format('png')->size(2000)->margin(10)->generate(
            route('front.member.member_add_view', $location->uuid)
        );

        return $qrCode instanceof HtmlString ? $qrCode : null;
    }

    public function exportLocations(string $filename, LocationListData $locationListData): BinaryFileResponse
    {
        $filterData = [
            'type_id' => $locationListData->type_id ?? null,
            'search_text' => $locationListData->search_text ?? null,
            'sort_by' => $locationListData->sort_by ?? null,
            'sort_direction' => $locationListData->sort_direction ?? null,
            'per_page' => $locationListData->per_page ?? null,
        ];
        $locations = $this->locationQueries->getLocationsExport($filterData, session('admin_company_id'));

        return Excel::download(new LocationExport($locations), $filename);
    }

    public function fetchStoreIOICityMallConfiguration(int $locationId): array
    {
        $location = $this->validateLocationIsStore($locationId);
        if (! $location instanceof Location) {
            abort(412, 'The selected location is not a store.');
        }

        $storeIOIConfiguration = $this->locationQueries->getStoreIOICityMallConfiguration(
            $locationId,
            session('admin_company_id')
        );

        return [
            'locationIOIConfiguration' => $storeIOIConfiguration,
        ];
    }

    public function fetchStoreTRXMallConfiguration(int $locationId): array
    {
        $location = $this->validateLocationIsStore($locationId);
        if (! $location instanceof Location) {
            abort(412, 'The selected location is not a store.');
        }

        $storeTRXConfiguration = $this->locationQueries->getStoreTRXMallConfiguration(
            $locationId,
            session('admin_company_id')
        );

        return [
            'locationTRXConfiguration' => $storeTRXConfiguration,
        ];
    }

    public function getLocationSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $locations = $this->locationQueries->getLocationSalesSummary($filterData, session('admin_company_id'));

        return [
            'locations' => $locations,
            'total_sales' => $locations->sum('total_sales'),
            'total_units_sold' => $locations->sum('total_units_sold'),
        ];
    }

    public function getLocationsOfRegions(Request $request): array
    {
        $regionId = (int) $request->get('region_id');

        $locations = $this->locationQueries->getLocationsOfRegions(
            $regionId,
            session('admin_company_id'),
            LocationTypes::STORE->value
        );

        return [
            'locations' => $locations,
        ];
    }

    public function getLocationsOfLocationsName(Request $request): array
    {
        $names = $request->get('names');

        $locations = $this->locationQueries->getLocationsOfLocationsName(
            $names,
            session('admin_company_id'),
            LocationTypes::STORE->value
        );

        return [
            'locations' => $locations,
        ];
    }

    public function updateIOICityMallConfiguration(Request $request, int $locationId): void
    {
        $location = $this->validateLocationIsStore($locationId);
        if (! $location instanceof Location) {
            abort(417, 'The selected location is not a store.');
        }

        $companyId = session('admin_company_id');
        $requestData = $request->validate([
            'enable_ioi_city_mall_data_sharing' => ['required', 'boolean'],
            'ioi_city_mall_machine_id' => [
                'required_if:enable_ioi_city_mall_data_sharing,true',
                'nullable',
                'unique:locations,ioi_city_mall_machine_id,' . $locationId . 'id',
            ],
        ]);

        $this->locationQueries->updateIOICityMallConfiguration($requestData, $locationId, $companyId);
    }

    public function updateTRXMallConfiguration(Request $request, int $locationId): void
    {
        $location = $this->validateLocationIsStore($locationId);
        if (! $location instanceof Location) {
            abort(417, 'The selected location is not a store.');
        }

        $companyId = session('admin_company_id');
        $requestData = $request->validate([
            'enable_trx_mall_data_sharing' => ['required', 'boolean'],
            'trx_mall_machine_id' => [
                'required_if:enable_trx_mall_data_sharing,true',
                'nullable',
                'unique:locations,trx_mall_machine_id,' . $locationId . 'id',
            ],
        ]);

        $this->locationQueries->updateTRXMallConfiguration($requestData, $locationId, $companyId);
    }

    public function getMatchingCodeLocations(Request $request): array
    {
        $validatedData = $request->validate([
            'import_store_codes' => ['required', 'array'],
            'import_store_codes.*' => ['required', 'string'],
        ]);

        $locations = $this->locationQueries->getByCodes(
            $validatedData['import_store_codes'],
            session('admin_company_id'),
        );

        return [
            'locations' => $locations,
            'locations_count' => $locations->count(),
        ];
    }

    public function resendVerificationEmail(int $locationId): RedirectResponse
    {
        $location = $this->locationQueries->getByIdForEmailVerification($locationId, session('admin_company_id'));
        EmailVerificationJob::dispatch($location)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('admin.locations.index')
            ->with('success', 'The verification mail sent successfully.');
    }

    private function checkRequestDetails(LocationData $locationData): void
    {
        $companyQueries = resolve(CompanyQueries::class);

        if (! $locationData->brand_ids) {
            return;
        }

        $hasAllBrandsAttachedInCompany = $companyQueries->hasAllBrandsAttached(
            session('admin_company_id'),
            $locationData->brand_ids
        );

        if (! $hasAllBrandsAttachedInCompany) {
            throw new RedirectWithErrorException(
                'admin.locations.index',
                'Some of the selected brands are not offered by the parent company, so they cannot be selected.'
            );
        }
    }

    private function validateLocationIsStore(int $locationId): ?Location
    {
        $locationQueries = resolve(LocationQueries::class);

        return $locationQueries->getLocationTypeStoreById($locationId);
    }
}
