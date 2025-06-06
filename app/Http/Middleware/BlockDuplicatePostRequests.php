<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Panel\Service\PanelManagementService;
use App\Exceptions\RedirectBackWithErrorException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BlockDuplicatePostRequests
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->method() === 'GET') {
            return $next($request);
        }

        $context = $this->generateContext($request);

        if (Cache::has($context)) {
            if (PanelManagementService::requestForApi($request)) {
                abort(412, 'Duplicate request detected.');
            }

            throw new RedirectBackWithErrorException('Duplicate request detected.');
        }

        Cache::put($context, true, now()->addMinutes(1));

        $response = $next($request);

        Cache::forget($context);

        return $response;
    }

    protected function generateContext(Request $request): string
    {
        if ($request->method() === 'POST') {
            return md5($request->url() . serialize($request->input()));
        }

        if ($request->method() === 'PUT') {
            return md5($request->url());
        }

        return '';
    }
}
