<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\CaseSensitiveConditionals;
use App\Models\Cashier;
use App\Models\CreditNoteExpiration;
use App\Models\Integration;
use App\Models\Member;
use App\Models\Model;
use App\Models\Promoter;
use App\Models\SaleChannel;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;

test('should not directly use global environment variable')
    ->expect('env')
    ->not
    ->toBeUsed();

test('models should extend base model')
    ->expect('App\Models')
    ->toExtend(Model::class)
    ->ignoring([
        SuperAdmin::class,
        Promoter::class,
        CreditNoteExpiration::class,
        Cashier::class,
        Admin::class,
        StoreManager::class,
        Member::class,
        CaseSensitiveConditionals::class,
        WarehouseManager::class,
        SaleChannel::class,
        Integration::class,
    ]);

test('should not use dd and dump functions')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not
    ->toBeUsed();

test('should have suffix for controllers')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');
