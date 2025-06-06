<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\User\DataObjects\UserChangePasswordData;
use App\Domains\User\DataObjects\UserData;
use App\Domains\User\Enums\UserTypes;
use App\Domains\User\Exports\UserExport;
use App\Domains\User\Resources\UserListResource;
use App\Domains\User\UserQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserQueries $userQueries
    ) {
    }

    public function index(): Response
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employees = $employeeQueries->getFormattedEmployeesOf(session('admin_company_id'));

        return Inertia::render('users/Index', [
            'employees' => $employees,
            'exportPermission' => PermissionList::getExportPermissionName('user'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchUsers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'employee_ids' => $request->get('employee_ids'),
        ];

        $lengthAwarePaginator = $this->userQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => UserListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employees = $employeeQueries->getFormattedEmployeesOf(session('admin_company_id'));

        return Inertia::render('users/Manage', [
            'employees' => $employees,
            'userTypes' => UserTypes::formattedForSelection(),
        ]);
    }

    public function store(UserData $userData): RedirectResponse
    {
        $this->userQueries->addNew($userData);

        return to_route('admin.users.index')->with('success', 'User added successfully.');
    }

    public function edit(int $userId): Response
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employees = $employeeQueries->getFormattedEmployeesOf(session('admin_company_id'));

        return Inertia::render('users/Manage', [
            'user' => $this->userQueries->getById($userId, session('admin_company_id')),
            'employees' => $employees,
            'userTypes' => UserTypes::formattedForSelection(),
        ]);
    }

    public function update(UserData $userData, int $userId): RedirectResponse
    {
        $user = $this->userQueries->getById($userId, session('admin_company_id'));

        $this->userQueries->update($userData, $user);

        return to_route('admin.users.index')->with('success', 'The user updated successfully.');
    }

    public function changePassword(int $userId): Response
    {
        return Inertia::render('users/ChangePassword', [
            'userId' => $userId,
        ]);
    }

    public function updatePassword(
        UserChangePasswordData $adminChangePasswordData,
        int $userId
    ): RedirectResponse {
        $superAdmin = $this->userQueries->getById($userId, session('admin_company_id'));

        $this->userQueries->userChangePassword($superAdmin, $adminChangePasswordData);

        return to_route('admin.users.index')
            ->with('success', 'Password updated successfully.');
    }

    public function exportUsers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'employee_ids' => $request->get('employee_ids'),
        ];

        $colors = $this->userQueries->getUsersExport($filterData, session('admin_company_id'));

        return Excel::download(new UserExport($colors), $filename);
    }
}
