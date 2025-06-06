<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\ExternalCompany\Jobs\ExternalCompanyUpdateJob;
use App\Domains\ExternalConnection\DataObjects\ExternalConnectionData;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalConnection\Resource\ExternalConnectionListResource;
use App\Domains\ExternalConnection\Services\ExternalConnectionService;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExternalConnectionController extends Controller
{
    public function __construct(
        protected ExternalConnectionQueries $externalConnectionQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('external_connections/Index');
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchExternalConnections(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->externalConnectionQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ExternalConnectionListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('external_connections/Manage');
    }

    public function store(ExternalConnectionData $externalConnectionData): RedirectResponse
    {
        $externalConnectionService = resolve(ExternalConnectionService::class);
        $externalConnectionService->checkExternalConnectionAvailable($externalConnectionData);

        /** @var SuperAdmin $superAdmin */
        $superAdmin = Auth::guard('super_admin')->user();
        $externalConnectionData->create_by_super_admin_id = $superAdmin->id;
        $externalConnection = $this->externalConnectionQueries->addNew($externalConnectionData);
        $externalConnectionService->sendNotification($externalConnection);

        return to_route('super_admin.external_connections.index')->with(
            'success',
            'External Connection added successfully.'
        );
    }

    public function edit(int $externalConnectionId): Response
    {
        $externalConnection = $this->externalConnectionQueries->getById($externalConnectionId);

        return Inertia::render('external_connections/Manage', [
            'externalConnection' => $externalConnection,
        ]);
    }

    public function update(
        ExternalConnectionData $externalConnectionData,
        int $externalConnectionId
    ): RedirectResponse {
        $this->externalConnectionQueries->update($externalConnectionData, $externalConnectionId);

        return to_route('super_admin.external_connections.index')->with(
            'success',
            'External Connection updated successfully.'
        );
    }

    public function reject(Request $request): RedirectResponse
    {
        /** @var SuperAdmin $superAdmin */
        $superAdmin = Auth::guard('super_admin')->user();

        $notificationId = $request->notification_id ? (int) $request->notification_id : null;
        $externalConnectionService = resolve(ExternalConnectionService::class);
        $externalConnectionService->rejectExternalConnection(
            $superAdmin,
            $request->url,
            (int) $request->id,
            $notificationId,
        );

        return to_route('super_admin.external_connections.index')->with(
            'success',
            'External Connection rejected successfully.'
        );
    }

    public function approve(Request $request): RedirectResponse
    {
        /** @var SuperAdmin $superAdmin */
        $superAdmin = Auth::guard('super_admin')->user();

        $notificationId = $request->notification_id ? (int) $request->notification_id : null;
        $externalConnectionService = resolve(ExternalConnectionService::class);
        $externalConnectionService->approveExternalConnection(
            $superAdmin,
            $request->url,
            (int) $request->id,
            $notificationId,
        );

        return to_route('super_admin.external_connections.index')->with(
            'success',
            'External Connection approved successfully.'
        );
    }

    public function syncData(int $externalConnectionId): void
    {
        ExternalCompanyUpdateJob::dispatch($externalConnectionId)->onQueue('medium');

        $externalConnection = $this->externalConnectionQueries->getById($externalConnectionId);

        $externalConnectionService = resolve(ExternalConnectionService::class);
        $externalConnectionService->syncDataExternalConnection($externalConnection);
    }
}
