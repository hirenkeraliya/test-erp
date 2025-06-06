<?php

declare(strict_types=1);

namespace App\Services;

use App\Domains\Admin\AdminQueries;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\UrlSigner\Laravel\Facades\UrlSigner;

class SsoLoginService
{
    public function ssoRequested(Request $request): bool
    {
        $queryParams = (array) $request->query();

        return [] !== $queryParams && 'sso' === $queryParams['intent'];
    }

    public function ssoRequestedWithValidUrl(Request $request): bool
    {
        $queryParams = (array) $request->query();
        $allowedUrls = explode(',', config('services.retail_planning.sso_urls_whitelist'));

        if (! isset($queryParams['redirectBackTo'])) {
            return false;
        }

        return in_array($queryParams['redirectBackTo'], $allowedUrls);
    }

    public function recordEventAndGetRedirectUrl(Request $request, Admin $admin): string
    {
        $queryParams = (array) $request->query();

        $adminDetails = [
            'name' => AdminQueries::getEmployeeFullName($admin),
            'ulid' => $admin->ulid,
        ];

        Log::channel('retail_planning')->info('SSO Login from Retail Planning', [
            'tag' => 'sso_with_retail_planning',
            'admin_id' => $admin->id,
            'admin_ulid' => $admin->ulid,
            'admin_username' => $admin->username,
            'redirect_url' => $queryParams['redirectBackTo'],
        ]);

        // TODO: Temporary Ignoring due to affecting development
        // /* @phpstan-ignore-next-line */
        return UrlSigner::sign(
            $queryParams['redirectBackTo'] . '?' . http_build_query($adminDetails),
            15
        ); // Valid for 15 seconds only
    }
}
