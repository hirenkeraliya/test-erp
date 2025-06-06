<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Region\DataObjects\RegionData;
use App\Domains\Region\Exports\RegionExport;
use App\Domains\Region\RegionQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegionController extends Controller
{
    public function __construct(
        protected RegionQueries $regionQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('regions/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('region'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchRegions(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->regionQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(RegionData $regionData): RedirectResponse
    {
        $this->regionQueries->addNew($regionData, session('admin_company_id'));

        return to_route('admin.regions.index')->with('success', 'The region has been added successfully.');
    }

    public function edit(int $colorId): Response
    {
        return Inertia::render('regions/Manage', [
            'region' => $this->regionQueries->getById($colorId, session('admin_company_id')),
        ]);
    }

    public function update(RegionData $regionData, int $regionId): RedirectResponse
    {
        $this->regionQueries->update($regionData, $regionId, session('admin_company_id'));

        return to_route('admin.regions.index')->with('success', 'The region has been updated successfully.');
    }

    public function exportRegions(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $regions = $this->regionQueries->getRegionsExport($filterData, session('admin_company_id'));

        return Excel::download(new RegionExport($regions), $filename);
    }

    public function addNewFromLocation(RegionData $regionData): array
    {
        $region = $this->regionQueries->addNew($regionData, session('admin_company_id'));

        return [
            'region' => $region,
        ];
    }

    public function resendVerificationEmail(int $regionId): RedirectResponse
    {
        $region = $this->regionQueries->getByIdForEmailVerification($regionId, session('admin_company_id'));
        EmailVerificationJob::dispatch($region)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('admin.regions.index')
            ->with('success', 'The verification mail sent successfully.');
    }
}
