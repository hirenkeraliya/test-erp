<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\LoyaltyCampaign\DataObjects\LoyaltyCampaignData;
use App\Domains\LoyaltyCampaign\Exports\LoyaltyCampaignExport;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyCampaign\Resources\AdminLoyaltyCampaignListResource;
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

class LoyaltyCampaignController extends Controller
{
    public function __construct(
        protected LoyaltyCampaignQueries $loyaltyCampaignQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('loyalty_campaigns/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('loyalty_campaign'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchLoyaltyCampaigns(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $lengthAwarePaginator = $this->loyaltyCampaignQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminLoyaltyCampaignListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getCompanyBrands(session('admin_company_id'));

        return Inertia::render('loyalty_campaigns/Manage', [
            'brands' => $brands,
        ]);
    }

    public function store(LoyaltyCampaignData $loyaltyCampaignData, Request $request): RedirectResponse
    {
        if ($loyaltyCampaignData->excluded_brand_ids) {
            $brandQueries = resolve(BrandQueries::class);

            $allBrandsExist = $brandQueries->doExistsById(
                session('admin_company_id'),
                $loyaltyCampaignData->excluded_brand_ids
            );

            if (! $allBrandsExist) {
                throw new RedirectBackWithErrorException(
                    'Some of the brands selected could not be found in our records'
                );
            }
        }

        /** @var User $user */
        $user = $request->user();

        $this->loyaltyCampaignQueries->addNew($loyaltyCampaignData, session('admin_company_id'), $user);

        return to_route('admin.loyalty_campaigns.index')->with(
            'success',
            'The loyalty campaign was added successfully.'
        );
    }

    public function edit(int $loyaltyCampaignId): Response
    {
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getCompanyBrands(session('admin_company_id'));

        return Inertia::render('loyalty_campaigns/Manage', [
            'loyaltyCampaign' => $this->loyaltyCampaignQueries->getById(
                $loyaltyCampaignId,
                session('admin_company_id')
            ),
            'brands' => $brands,
        ]);
    }

    public function update(LoyaltyCampaignData $loyaltyCampaignData, int $loyaltyCampaignId): RedirectResponse
    {
        $this->loyaltyCampaignQueries->update(
            $loyaltyCampaignData,
            $loyaltyCampaignId,
            session('admin_company_id')
        );

        return to_route('admin.loyalty_campaigns.index')->with('success', 'Loyalty campaign updated successfully.');
    }

    public function exportLoyaltyCampaigns(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $loyaltyCampaigns = $this->loyaltyCampaignQueries->getLoyaltyCampaignsExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new LoyaltyCampaignExport($loyaltyCampaigns), $filename);
    }
}
