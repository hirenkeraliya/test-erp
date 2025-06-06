<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\ChangePasswordData;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __construct(
        protected AdminQueries $adminQueries
    ) {
    }

    public function updatePassword(ChangePasswordData $changePasswordData): RedirectResponse
    {
        /** @var Admin $authenticatable */
        $authenticatable = Auth::guard('admin')->user();

        if (Hash::check($changePasswordData->current_password, $authenticatable->getPassword())) {
            $this->adminQueries->changePassword($authenticatable, $changePasswordData);

            return to_route('admin.dashboard')->with('success', 'Password updated successfully.');
        }

        throw new RedirectBackWithErrorException('Current password incorrect.');
    }
}
