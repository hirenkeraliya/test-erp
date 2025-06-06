<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Reward\DataObjects\RewardData;
use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Domains\Reward\Exports\RewardExport;
use App\Domains\Reward\Resources\RewardResource;
use App\Domains\Reward\RewardQueries;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RewardController extends Controller
{
    public function __construct(
        protected RewardQueries $rewardQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('rewards/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('reward'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchRewards(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->rewardQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => RewardResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $categories = $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id'));
        $departments = $departmentQueries->getWithBasicColumns(session('admin_company_id'));
        $brands = $brandQueries->getWithBasicColumns();
        $locations = $locationQueries->getStoreWithBasicColumns(session('admin_company_id'));

        return Inertia::render('rewards/Manage', [
            'rewardTypes' => RewardTypes::formattedForSelection(),
            'staticRewardTypes' => RewardTypes::getFormattedArrayForStaticUse(),
            'rewardTargetTypes' => RewardTargetTypes::formattedForSelection(),
            'staticRewardTargetTypes' => RewardTargetTypes::getFormattedArrayForStaticUse(),
            'discountTypes' => DiscountTypes::getList(),
            'staticDiscountTypes' => DiscountTypes::getFormattedArrayForStaticUse(),
            'categories' => $categories,
            'brands' => $brands,
            'departments' => $departments,
            'locations' => $locations,
        ]);
    }

    public function store(RewardData $rewardData, Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->rewardQueries->addNew($rewardData, session('admin_company_id'), $user);

        return to_route('admin.rewards.index')->with('success', 'The Reward was added successfully.');
    }

    public function edit(int $rewardId): Response
    {
        $categoryQueries = resolve(CategoryQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $categories = $categoryQueries->getMainCategoriesWithBasicColumns(session('admin_company_id'));
        $departments = $departmentQueries->getWithBasicColumns(session('admin_company_id'));
        $brands = $brandQueries->getWithBasicColumns();
        $locations = $locationQueries->getStoreWithBasicColumns(session('admin_company_id'));

        return Inertia::render('rewards/Manage', [
            'reward' => $this->rewardQueries->getById($rewardId, session('admin_company_id')),
            'rewardTypes' => RewardTypes::formattedForSelection(),
            'staticRewardTypes' => RewardTypes::getFormattedArrayForStaticUse(),
            'rewardTargetTypes' => RewardTargetTypes::formattedForSelection(),
            'staticRewardTargetTypes' => RewardTargetTypes::getFormattedArrayForStaticUse(),
            'discountTypes' => DiscountTypes::getList(),
            'staticDiscountTypes' => DiscountTypes::getFormattedArrayForStaticUse(),
            'categories' => $categories,
            'brands' => $brands,
            'departments' => $departments,
            'locations' => $locations,
        ]);
    }

    public function update(RewardData $rewardData, int $rewardId): RedirectResponse
    {
        $this->rewardQueries->update($rewardData, $rewardId, session('admin_company_id'));

        return to_route('admin.rewards.index')->with('success', 'Reward updated successfully.');
    }

    public function exportRewards(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $loyaltyCampaignConfigurations = $this->rewardQueries->getExport($filterData, session('admin_company_id'));

        return Excel::download(new RewardExport($loyaltyCampaignConfigurations), $filename);
    }

    public function setStatus(int $rewardId, bool $status): RedirectResponse
    {
        $this->rewardQueries->setStatus($rewardId, session('admin_company_id'), $status);

        return to_route('admin.rewards.index')->with('success', 'Status changed successfully.');
    }
}
