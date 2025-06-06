<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\SuperAdmin\DataObjects\SuperAdminChangePasswordData;
use App\Domains\SuperAdmin\DataObjects\SuperAdminData;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    public function __construct(
        protected SuperAdminQueries $superAdminQueries
    ) {
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchSuperAdmins(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->superAdminQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('super_admins/Manage');
    }

    public function store(SuperAdminData $superAdminData): RedirectResponse
    {
        $this->superAdminQueries->addNew($superAdminData);

        return to_route('super_admin.super_admins.index')->with('success', 'Super Admin successfully added.');
    }

    public function edit(int $superAdminId): Response
    {
        $superAdmin = $this->superAdminQueries->getById($superAdminId);

        return Inertia::render('super_admins/Manage', [
            'superAdmin' => $superAdmin,
        ]);
    }

    public function update(SuperAdminData $superAdminData, int $superAdminId): RedirectResponse
    {
        $superAdmin = $this->superAdminQueries->getById($superAdminId);

        $this->superAdminQueries->update($superAdminData, $superAdmin);

        return to_route('super_admin.super_admins.index')->with('success', 'Super Admin updated successfully.');
    }

    public function changePassword(int $superAdminId): Response
    {
        return Inertia::render('super_admins/ChangePassword', [
            'superAdminId' => $superAdminId,
        ]);
    }

    public function editProfile(): Response
    {
        $superAdminId = (int) Auth::id();
        $superAdmin = $this->superAdminQueries->getById($superAdminId);

        return Inertia::render('super_admins/Profile', [
            'superAdmin' => $superAdmin,
        ]);
    }

    public function updatePassword(
        SuperAdminChangePasswordData $superAdminChangePasswordData,
        int $superAdminId
    ): RedirectResponse {
        $superAdmin = $this->superAdminQueries->getById($superAdminId);

        $this->superAdminQueries->superAdminChangePassword($superAdmin, $superAdminChangePasswordData);

        return to_route('super_admin.super_admins.index')
            ->with('success', 'Password updated successfully.');
    }

    public function resendVerificationEmail(int $superAdminId): RedirectResponse
    {
        $superAdmin = $this->superAdminQueries->getByIdForEmailVerification($superAdminId);
        EmailVerificationJob::dispatch($superAdmin)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('super_admin.super_admins.index')
            ->with('success', 'The verification mail sent successfully.');
    }
}
