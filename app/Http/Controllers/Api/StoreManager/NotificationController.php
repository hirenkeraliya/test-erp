<?php

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Notification\Resources\ArchivedNotificationResource;
use App\Domains\Notification\Resources\UnReadNotificationResource;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function getUnReadNotificationList(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filteredData = [
            'per_page' => $request->get('per_page'),
        ];

        $notificationQueries = resolve(NotificationQueries::class);
        $lengthAwarePaginator = $notificationQueries->getUnReadNotifications(
            $filteredData,
            $storeManager->getKey(),
            ModelMapping::STORE_MANAGER->name
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
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filteredData = [
            'per_page' => $request->get('per_page'),
        ];

        $notificationQueries = resolve(NotificationQueries::class);
        $lengthAwarePaginator = $notificationQueries->getArchivedNotifications(
            $filteredData,
            $storeManager->getKey(),
            ModelMapping::STORE_MANAGER->name
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
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $request->validate([
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => [
                Rule::exists('notifications', 'id')->where(
                    'to_user_id',
                    $storeManager->getKey()
                )->where('to_user_type', ModelMapping::STORE_MANAGER->name),
            ],
        ]);

        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->markAsReadByIds(
            (array) $request->notification_ids,
            $storeManager->getKey(),
            ModelMapping::STORE_MANAGER->name
        );
    }

    public function markAsUnRead(Request $request): void
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $request->validate([
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => [
                Rule::exists('notifications', 'id')->where(
                    'to_user_id',
                    $storeManager->getKey()
                )->where('to_user_type', ModelMapping::STORE_MANAGER->name),
            ],
        ]);

        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->markAsUnReadByIds(
            (array) $request->notification_ids,
            $storeManager->getKey(),
            ModelMapping::STORE_MANAGER->name
        );
    }
}
