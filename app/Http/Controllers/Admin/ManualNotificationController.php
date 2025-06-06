<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Location\LocationQueries;
use App\Domains\ManualNotification\DataObjects\ManualNotificationData;
use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\MembersFilter;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\Jobs\ManualNotificationSendJob;
use App\Domains\ManualNotification\ManualNotificationQueries;
use App\Domains\ManualNotification\Resources\ManualNotificationDetailsResource;
use App\Domains\ManualNotification\Resources\ManualNotificationListResource;
use App\Domains\Member\Enums\Types;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManualNotificationController extends Controller
{
    public function __construct(
        protected ManualNotificationQueries $manualNotificationQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('manual_notifications/Index');
    }

    public function fetchManualNotifications(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->manualNotificationQueries->listQuery(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ManualNotificationListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        [$locations, $promoters, $promoterGroups, $memberGroups, $memberTypes] = $this->fetchCommonRecords(
            session('admin_company_id')
        );

        return Inertia::render('manual_notifications/Manage', [
            'locations' => $locations,
            'promoters' => $promoters,
            'promoterGroups' => $promoterGroups,
            'memberGroups' => $memberGroups,
            'memberTypes' => $memberTypes,
            'manualNotificationTypes' => ManualNotificationTypes::formattedForSelection(),
            'promoterFilterTypes' => PromotersFilter::formattedForSelection(),
            'memberFilterTypes' => MembersFilter::formattedForSelection(),
            'staticManualNotificationTypes' => ManualNotificationTypes::getFormattedArrayForStaticUse(),
            'staticPromoterFilterTypes' => PromotersFilter::getFormattedArrayForStaticUse(),
            'staticMemberFilterTypes' => MembersFilter::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(ManualNotificationData $manualNotificationData, Request $request): RedirectResponse
    {
        $manualNotification = $this->manualNotificationQueries->addNew(
            $manualNotificationData,
            session('admin_company_id')
        );

        /** @var Admin $user */
        $user = $request->user();
        ManualNotificationSendJob::dispatch($manualNotification->id, session('admin_company_id'), $user->id)->onQueue(
            'medium'
        );

        return to_route('admin.manual_notifications.index')->with('success', 'Notification added successfully.');
    }

    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $promoters = $promoterQueries->getAllPromoterByCompany($companyId);

        $promoterGroups = $promoterGroupQueries->getPromoterGroupByCompanyId($companyId);
        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        $memberGroups = $memberGroupQueries->getByCompanyId($companyId);
        $memberTypes = Types::formattedForSelection();

        return [$locations, $promoters, $promoterGroups, $memberGroups, $memberTypes];
    }

    public function fetchDetailsByManualNotificationId(int $manualNotificationId): array
    {
        $manualNotificationDetails = $this->manualNotificationQueries->getWithById(
            $manualNotificationId,
            session('admin_company_id')
        );

        return [
            'manual_notification_details' => new ManualNotificationDetailsResource($manualNotificationDetails),
            'manual_notification_type' => ManualNotificationTypes::getFormattedCaseName(
                $manualNotificationDetails->type_id
            ),
        ];
    }
}
