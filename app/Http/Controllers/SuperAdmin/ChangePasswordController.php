<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\SuperAdmin\DataObjects\ChangePasswordData;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __construct(
        protected SuperAdminQueries $superAdminQueries
    ) {
    }

    public function update(ChangePasswordData $changePasswordData): RedirectResponse
    {
        /** @var SuperAdmin $authenticatable */
        $authenticatable = Auth::guard('super_admin')->user();

        if (Hash::check($changePasswordData->current_password, $authenticatable->getPassword())) {
            $this->superAdminQueries->changePassword($authenticatable, $changePasswordData);

            return to_route('super_admin.dashboard')->with('success', 'Password updated successfully.');
        }

        throw new RedirectBackWithErrorException('Current password incorrect.');
    }
}
