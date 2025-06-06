<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Common\Enums\WebhookUrls;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\SaleChannel\DataObjects\SaleChannelData;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\Resources\SaleChannelResource;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SaleChannelController extends Controller
{
    public function __construct(
        protected SaleChannelQueries $saleChannelQueries
    ) {
    }

    public function fetchSalesChannel(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->saleChannelQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $companyQueries = new CompanyQueries();
        $roundOffConfiguration = resolve(RoundOffConfiguration::class);

        return Inertia::render('sales_channel/Manage', [
            'webhookUrls' => WebhookUrls::getList(),
            'orderStatuses' => OrderStatus::getList(),
            'saleChannelTypes' => SaleChannelTypes::getList(),
            'companies' => $companyQueries->getWithBasicColumns(),
            'saleChannelTypesEcommerce' => SaleChannelTypes::ECOMMERCE->value,
            'roundOffData' => $roundOffConfiguration->getList(),
        ]);
    }

    public function store(SaleChannelData $saleChannelData): array
    {
        $token = $this->saleChannelQueries->addNew($saleChannelData);

        return [
            'token' => $token,
        ];
    }

    public function edit(int $salesChannelId): Response
    {
        $salesChannel = $this->saleChannelQueries->getById($salesChannelId);
        $companyQueries = new CompanyQueries();
        $locationQueries = new LocationQueries();

        $roundOffConfiguration = resolve(RoundOffConfiguration::class);

        return Inertia::render('sales_channel/Manage', [
            'salesChannel' => new SaleChannelResource($salesChannel),
            'webhookUrls' => WebhookUrls::getList(),
            'orderStatuses' => OrderStatus::getList(),
            'saleChannelTypes' => SaleChannelTypes::getList(),
            'companies' => $companyQueries->getWithBasicColumns(),
            'locations' => $locationQueries->getByCompanyIdAndTypeId(
                $salesChannel->company_id,
                LocationTypes::STORE->value
            ),
            'saleChannelTypesEcommerce' => SaleChannelTypes::ECOMMERCE->value,
            'roundOffData' => $salesChannel->round_off_configuration ?? $roundOffConfiguration->getList(),
        ]);
    }

    public function update(SaleChannelData $saleChannelData, int $salesChannelId): RedirectResponse
    {
        $salesChannel = $this->saleChannelQueries->getById($salesChannelId);

        $this->saleChannelQueries->update($saleChannelData, $salesChannel);

        return to_route('super_admin.sales_channel.index')->with('success', 'Sales Channel updated successfully.');
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

        $accessToken = $this->saleChannelQueries->refreshToken($salesChannelId);

        return [
            'access_token' => $accessToken,
        ];
    }

    public function setStatus(int $saleChannelId, bool $status): RedirectResponse
    {
        $this->saleChannelQueries->updateStatus($saleChannelId, $status);

        return to_route('super_admin.sales_channel.index')->with('success', 'Status changed successfully.');
    }
}
