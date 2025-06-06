<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Permission\PermissionQueries;
use App\Domains\Role\RoleQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Models\Admin;
use App\Models\Role;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, mixed $permission, ?string $guard = null): mixed
    {
        $authGuard = Auth::guard($guard);
        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roleQueries = resolve(RoleQueries::class);
        $permissionQueries = resolve(PermissionQueries::class);

        /** @var Admin|StoreManager|WarehouseManager $user */
        $user = $authGuard->user();
        $user = $user->load([
            'roles:' . $roleQueries->getBasicColumns(),
            'roles.permissions:' . $permissionQueries->getBasicColumns(),
        ]);

        $roles = $user->roles->pluck('name')->toArray();

        // Allow user that doesn't have any roles.
        if ([] === $roles) {
            return $next($request);
        }

        if (! $user->hasRole($roles) || ! $this->rolesHasPermission($user->roles, $permission)) {
            $message = 'User does not have any of the necessary access rights.';

            if ($user instanceof StoreManager) {
                throw new RedirectWithErrorException('store_manager.dashboard', $message);
            }

            if ($user instanceof WarehouseManager) {
                throw new RedirectWithErrorException('warehouse_manager.dashboard', $message);
            }

            throw new RedirectWithErrorException('admin.dashboard', $message);
        }

        return $next($request);
    }

    private function rolesHasPermission(Collection $roles, string $permission): bool
    {
        if (config('app.env') === 'local') {
            return true;
        }

        foreach ($roles as $role) {
            /** @var Role $role */
            if ($role->permissions->contains('name', $permission)) {
                return true;
            }
        }

        return false;
    }
}
