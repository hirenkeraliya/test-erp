<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyCampaignConfiguration\DataObjects\LoyaltyCampaignConfigurationData;
use App\Domains\LoyaltyCampaignConfiguration\Enums\ExpirationTypes;
use App\Domains\LoyaltyCampaignConfiguration\Enums\LoyaltyCampaignTypes;
use App\Domains\LoyaltyCampaignConfiguration\Exports\LoyaltyCampaignConfigurationExport;
use App\Domains\LoyaltyCampaignConfiguration\LoyaltyCampaignConfigurationQueries;
use App\Domains\LoyaltyCampaignConfiguration\Resources\LoyaltyCampaignConfigurationListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoyaltyCampaignConfigurationController extends Controller
{
    public function __construct(
        protected LoyaltyCampaignConfigurationQueries $loyaltyCampaignConfigurationQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('loyalty_campaign_configurations/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('loyalty_campaign_configuration'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchLoyaltyCampaignConfigurations(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->loyaltyCampaignConfigurationQueries->listQuery(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => LoyaltyCampaignConfigurationListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getCompanyBrands(session('admin_company_id'));

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns(session('admin_company_id'));

        $categoryQueries = resolve(CategoryQueries::class);

        return Inertia::render('loyalty_campaign_configurations/Manage', [
            'brands' => $brands,
            'locations' => $locations,
            'loyaltyCampaignTypes' => LoyaltyCampaignTypes::formattedForSelection(),
            'expirationTypes' => ExpirationTypes::formattedForSelection(),
            'staticLoyaltyCampaignTypes' => LoyaltyCampaignTypes::getFormattedArrayForStaticUse(),
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id')),
        ]);
    }

    public function store(
        LoyaltyCampaignConfigurationData $loyaltyCampaignConfigurationData,
        Request $request
    ): RedirectResponse {
        if ($loyaltyCampaignConfigurationData->brand_ids) {
            $brandQueries = resolve(BrandQueries::class);

            $allBrandsExist = $brandQueries->doExistsById(
                session('admin_company_id'),
                $loyaltyCampaignConfigurationData->brand_ids
            );

            if (! $allBrandsExist) {
                throw new RedirectBackWithErrorException(
                    'Some of the brands selected could not be found in our records'
                );
            }
        }

        /** @var User $user */
        $user = $request->user();

        $this->loyaltyCampaignConfigurationQueries->addNew(
            $loyaltyCampaignConfigurationData,
            session('admin_company_id'),
            $user
        );

        return to_route('admin.loyalty_campaign_configurations.index')->with(
            'success',
            'The loyalty campaign configuration was added successfully.'
        );
    }

    public function edit(int $loyaltyCampaignConfigurationId): Response
    {
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getCompanyBrands(session('admin_company_id'));

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns(session('admin_company_id'));

        $categoryQueries = resolve(CategoryQueries::class);

        return Inertia::render('loyalty_campaign_configurations/Manage', [
            'loyaltyCampaignConfiguration' => $this->loyaltyCampaignConfigurationQueries->getById(
                $loyaltyCampaignConfigurationId,
                session('admin_company_id')
            ),
            'brands' => $brands,
            'locations' => $locations,
            'loyaltyCampaignTypes' => LoyaltyCampaignTypes::formattedForSelection(),
            'staticLoyaltyCampaignTypes' => LoyaltyCampaignTypes::getFormattedArrayForStaticUse(),
            'expirationTypes' => ExpirationTypes::formattedForSelection(),
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id')),
        ]);
    }

    public function update(
        LoyaltyCampaignConfigurationData $loyaltyCampaignConfigurationData,
        int $loyaltyCampaignConfigurationId
    ): RedirectResponse {
        $this->loyaltyCampaignConfigurationQueries->update(
            $loyaltyCampaignConfigurationData,
            $loyaltyCampaignConfigurationId,
            session('admin_company_id')
        );

        return to_route('admin.loyalty_campaign_configurations.index')->with(
            'success',
            'Loyalty campaign configuration updated successfully.'
        );
    }

    public function exportLoyaltyCampaignConfigurations(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $loyaltyCampaignConfigurations = $this->loyaltyCampaignConfigurationQueries->getLoyaltyCampaignsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new LoyaltyCampaignConfigurationExport($loyaltyCampaignConfigurations), $filename);
    }
}
