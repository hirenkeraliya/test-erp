<?php

declare(strict_types=1);

namespace App\Domains\Permission\Services;

use App\CommonFunctions;
use App\Domains\Permission\Enums\PermissionList;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserPermissionModuleService
{
    public static function preparedPermissionModules(): Collection
    {
        $defaultPermissions = collect([]);

        foreach (self::getModuleSubLists() as $key => $permissionModule) {
            $defaultPermissions->push([
                'id' => Str::lower($key),
                'name' => CommonFunctions::stringTitleLowerCase($key),
                'action' => false,
                'children' => collect([]),
            ]);

            foreach ($permissionModule as $moduleName) {
                $defaultPermission = $defaultPermissions->firstWhere(
                    'name',
                    CommonFunctions::stringTitleLowerCase($key)
                );

                $defaultPermission['children']->push([
                    'id' => Str::lower($key) . '_' . $moduleName,
                    'name' => CommonFunctions::stringTitleLowerCase($moduleName),
                    'action' => false,
                ]);
            }
        }

        return $defaultPermissions;
    }

    public static function getModuleSubLists(): array
    {
        return [
            'Dashboard' => [
                PermissionList::DASHBOARD_OPERATIONAL->value,
                PermissionList::DASHBOARD_STORE_REVENUE->value,
                PermissionList::DASHBOARD_BUSINESS->value,
                PermissionList::DASHBOARD_STOCK_OVERVIEW->value,
                PermissionList::DASHBOARD_SALE_TARGET->value,
                PermissionList::DASHBOARD_SEASONAL->value,
            ],
        ];
    }
}
