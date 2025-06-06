<?php

namespace App\Http\Controllers\Api\Member;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Notification\Resources\ArchivedNotificationResource;
use App\Domains\Notification\Resources\UnReadNotificationResource;
use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function getUnReadNotificationList(Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $filteredData = [
            'per_page' => $request->get('per_page'),
        ];

        $notificationQueries = resolve(NotificationQueries::class);
        $lengthAwarePaginator = $notificationQueries->getUnReadNotifications(
            $filteredData,
            $member->getKey(),
            ModelMapping::MEMBER->name
        );

        return [
            'unread_notifications' => UnReadNotificationResource::collection($lengthAwarePaginator),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function getArchivedNotificationList(Request $request): array
    {
        /** @var Member $member */
        $member = $request->user();

        $filteredData = [
            'per_page' => $request->get('per_page'),
        ];

        $notificationQueries = resolve(NotificationQueries::class);
        $lengthAwarePaginator = $notificationQueries->getArchivedNotifications(
            $filteredData,
            $member->getKey(),
            ModelMapping::MEMBER->name
        );

        return [
            'archived_notifications' => ArchivedNotificationResource::collection($lengthAwarePaginator),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function markAsRead(Request $request): void
    {
        /** @var Member $member */
        $member = $request->user();

        $request->validate([
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => [
                Rule::exists('notifications', 'id')->where(
                    'to_user_id',
                    $member->getKey()
                )->where('to_user_type', ModelMapping::MEMBER->name),
            ],
        ]);

        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->markAsReadByIds(
            (array) $request->notification_ids,
            $member->getKey(),
            ModelMapping::MEMBER->name
        );
    }

    public function markAsUnRead(Request $request): void
    {
        /** @var Member $member */
        $member = $request->user();

        $request->validate([
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => [
                Rule::exists('notifications', 'id')->where(
                    'to_user_id',
                    $member->getKey()
                )->where('to_user_type', ModelMapping::MEMBER->name),
            ],
        ]);

        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->markAsUnReadByIds(
            (array) $request->notification_ids,
            $member->getKey(),
            ModelMapping::MEMBER->name
        );
    }
}
