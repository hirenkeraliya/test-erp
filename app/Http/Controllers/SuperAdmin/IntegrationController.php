<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Domains\Company\CompanyQueries;
use App\Domains\Integration\DataObjects\IntegrationData;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\Integration\Resources\IntegrationListResource;
use App\Domains\Integration\Resources\IntegrationResource;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function __construct(
        protected IntegrationQueries $integrationQueries,
    ) {
    }

    public function fetchIntegration(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->integrationQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => IntegrationListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $companyQueries = new CompanyQueries();

        return Inertia::render('integrations/Manage', [
            'webhookUrls' => IntegrationWebhookUrls::getList(),
            'connectionTypes' => IntegrationConnections::getList(),
            'staticConnectionTypes' => IntegrationConnections::getFormattedArrayForStaticUse(),
            'companies' => $companyQueries->getWithBasicColumns(),
        ]);
    }

    public function store(IntegrationData $integrationData): array
    {
        $token = $this->integrationQueries->addNew($integrationData);

        return [
            'token' => $token,
        ];
    }

    public function edit(int $integrationId): Response
    {
        $integration = $this->integrationQueries->getById($integrationId);
        $companyQueries = new CompanyQueries();

        return Inertia::render('integrations/Manage', [
            'integrations' => new IntegrationResource($integration),
            'connectionTypes' => IntegrationConnections::getList(),
            'companies' => $companyQueries->getWithBasicColumns(),
            'webhookUrls' => IntegrationWebhookUrls::getList(),
            'staticConnectionTypes' => IntegrationConnections::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(IntegrationData $integrationData, int $integrationId): RedirectResponse
    {
        $integration = $this->integrationQueries->getById($integrationId);

        $this->integrationQueries->update($integrationData, $integration);

        return to_route('super_admin.integrations.index')->with('success', 'Integration updated successfully.');
    }

    public function refreshAccessToken(Request $request, int $salesChannelId): array
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $superAdminQueries = resolve(SuperAdminQueries::class);
        $superAdmin = $superAdminQueries->getByUsername($credentials['username']);
        if (! $superAdmin) {
            abort(412, 'Username or password is incorrect.');
        }

        if (! Hash::check($credentials['password'], $superAdmin->password)) {
            abort(412, 'Username or password is incorrect.');
        }

        $accessToken = $this->integrationQueries->refreshToken($salesChannelId);

        return [
            'access_token' => $accessToken,
        ];
    }

    public function setStatus(int $saleChannelId, bool $status): RedirectResponse
    {
        $this->integrationQueries->updateStatus($saleChannelId, $status);

        return to_route('super_admin.integrations.index')->with('success', 'Status changed successfully.');
    }
}
