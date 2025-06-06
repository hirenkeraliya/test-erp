<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Role\RoleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AdminProfileController extends Controller
{
    public function editProfile(): Response
    {
        $adminId = (int) Auth::id();
        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->getAdminData($adminId);
        $roleQueries = resolve(RoleQueries::class);

        return Inertia::render('admin/Profile', [
            'admin' => $admin,
            'roles' => $roleQueries->getRoles('admin'),
        ]);
    }

    public function update(AdminData $adminData, int $adminId): ?RedirectResponse
    {
        try {
            $adminQueries = resolve(AdminQueries::class);
            $adminQueries->update($adminData, $adminId);

            return to_route('admin.dashboard')->with('success', 'Admin updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Company', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            return null;
        }
    }
}
