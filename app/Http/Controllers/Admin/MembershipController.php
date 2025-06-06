<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Membership\DataObjects\MembershipData;
use App\Domains\Membership\Exports\MembershipExport;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MembershipController extends Controller
{
    public function __construct(
        protected MembershipQueries $membershipQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('memberships/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('membership'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchMemberships(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->membershipQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function store(MembershipData $membershipData, Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->membershipQueries->addNew($membershipData, session('admin_company_id'), $user);

        return to_route('admin.memberships.index')->with('success', 'Membership added successfully.');
    }

    public function edit(int $membershipId): Response
    {
        return Inertia::render('memberships/Manage', [
            'membership' => $this->membershipQueries->getById($membershipId, session('admin_company_id')),
        ]);
    }

    public function update(MembershipData $membershipData, int $membershipId): RedirectResponse
    {
        $this->membershipQueries->update($membershipData, $membershipId, session('admin_company_id'));

        return to_route('admin.memberships.index')->with('success', 'Membership updated successfully.');
    }

    public function exportMemberships(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $memberships = $this->membershipQueries->getMembershipsExport($filterData, session('admin_company_id'));

        return Excel::download(new MembershipExport($memberships), $filename);
    }
}
