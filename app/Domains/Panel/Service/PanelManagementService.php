<?php

declare(strict_types=1);

namespace App\Domains\Panel\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PanelManagementService
{
    public static function requestForSuperAdmin(Request $request): bool
    {
        return Str::startsWith($request->getRequestUri(), '/super-admin');
    }

    public static function requestForAdmin(Request $request): bool
    {
        return Str::startsWith($request->getRequestUri(), '/admin');
    }

    public static function requestForStoreManager(Request $request): bool
    {
        return Str::startsWith($request->getRequestUri(), '/store-manager');
    }

    public static function requestForWarehouseManager(Request $request): bool
    {
        return Str::startsWith($request->getRequestUri(), '/warehouse-manager');
    }

    public static function requestForApi(Request $request): bool
    {
        return Str::startsWith($request->getRequestUri(), '/api');
    }
}
