<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PosAdminController extends Controller
{
    public function index(): Response
    {
        /* @phpstan-ignore-next-line */
        $appReleases = collect(Cache::get('pos_admin_app_releases'));

        return Inertia::render('pos_admin/Index', [
            'appReleases' => $appReleases->sortByDesc('released_at')->values()->toArray(),
        ]);
    }
}
