<?php

declare(strict_types=1);

namespace App\Domains\Notification\Listeners;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Firestore\Services\FirestoreService;
use App\Domains\Member\MemberQueries;
use App\Domains\Notification\Events\NotificationFirebaseEvent;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Models\Member;
use App\Models\Promoter;
use App\Models\StoreManager;
use App\Models\WarehouseManager;

class NotificationFirebaseListener
{
    public function handle(NotificationFirebaseEvent $event): void
    {
        $notification = $event->notification;

        $fireStoreService = resolve(FirestoreService::class);

        $token = null;

        if (! $fireStoreService->isFirebaseEnabled()) {
            return;
        }

        if ($notification->to_user_type === ModelMapping::ADMIN->name) {
            return;
        }

        if ($notification->to_user_type === ModelMapping::MEMBER->name) {
            $memberQueries = resolve(MemberQueries::class);
            $member = $memberQueries->getTokenById($notification->to_user_id);

            if (! $member instanceof Member) {
                return;
            }

            $token = $member->fcm_token;
        }

        if ($notification->to_user_type === ModelMapping::STORE_MANAGER->name) {
            $storeManagerQueries = resolve(StoreManagerQueries::class);
            $storeManager = $storeManagerQueries->getTokenById($notification->to_user_id);

            if (! $storeManager instanceof StoreManager) {
                return;
            }

            $token = $storeManager->fcm_token;
        }

        if ($notification->to_user_type === ModelMapping::WAREHOUSE_MANAGER->name) {
            $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
            $warehouseManager = $warehouseManagerQueries->getTokenById($notification->to_user_id);

            if (! $warehouseManager instanceof WarehouseManager) {
                return;
            }

            $token = $warehouseManager->fcm_token;
        }

        if ($notification->to_user_type === ModelMapping::PROMOTER->name) {
            $promoterQueries = resolve(PromoterQueries::class);
            $promoter = $promoterQueries->getTokenById($notification->to_user_id);

            if (! $promoter instanceof Promoter) {
                return;
            }

            $token = $promoter->fcm_token;
        }

        if (null === $token) {
            return;
        }

        $fireStoreService->push(
            (string) $notification->title,
            $notification->text_message,
            $token,
            $notification->payload
        );
    }
}
