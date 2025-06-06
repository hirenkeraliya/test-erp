<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\CashierGroup\Enums\PermissionChecks;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\Panel\Service\PanelManagementService;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\SuperAdmin;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected int $secondsInADay = 60 * 60 * 24;

    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(Request $request): void
    {
        Gate::define('viewPulse', fn ($user): bool => SuperAdmin::class === $user::class);

        Gate::define(
            PermissionChecks::CASHIER->name,
            function (Cashier $user, PermissionTypes $permission): bool {
                $permissionIds = Cache::remember(
                    'cashier_group_permission_' . $user->cashier_group_id,
                    $this->secondsInADay,
                    function () use ($user) {
                        /** @var CashierGroup $cashierGroup */
                        $cashierGroup = $user->cashierGroup;

                        return $cashierGroup
                            ->permissions
                            ->pluck('permission_id')
                            ->toArray();
                    }
                );

                return in_array($permission->value, $permissionIds, true);
            }
        );

        ResetPassword::createUrlUsing(function ($user, string $token) use ($request): string {
            if (PanelManagementService::requestForWarehouseManager($request)) {
                return route('warehouse_manager.reset_password', [
                    'token' => $token,
                ]);
            }

            if (PanelManagementService::requestForStoreManager($request)) {
                return route('store_manager.reset_password', [
                    'token' => $token,
                ]);
            }

            if (PanelManagementService::requestForAdmin($request)) {
                return route('admin.reset_password', [
                    'token' => $token,
                ]);
            }

            return route('super_admin.reset_password', [
                'token' => $token,
            ]);
        });

        Gate::define('viewLogViewer', fn (SuperAdmin $user): SuperAdmin => $user);
    }
}
