<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationQueries $notificationQueries
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchNotifications(Request $request): array
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $notifications = $this->notificationQueries->fetchMessages(
            session('admin_company_id'),
            $admin->getKey(),
            ModelMapping::ADMIN->name
        );

        $notifications->transform(function ($notification) {
            $notification->time = $notification->created_at->format('d-m-Y h:i:s A');
            $notification->human_time = $notification->created_at->diffForHumans();
            $notification->is_read = false;

            return $notification;
        });

        return [
            'notifications' => $notifications,
        ];
    }

    public function markAllAsRead(Request $request): void
    {
        DB::beginTransaction();

        try {
            /** @var Admin $admin */
            $admin = $request->user();

            $this->notificationQueries->markAllAsRead(
                session('admin_company_id'),
                $admin->getKey(),
                ModelMapping::ADMIN->name
            );

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Mark all as read', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchReadNotifications(Request $request): array
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $notifications = $this->notificationQueries->fetchReadMessages(
            session('admin_company_id'),
            $admin->getKey(),
            ModelMapping::ADMIN->name
        );

        $notifications->transform(function ($notification) {
            $notification->time = $notification->created_at->format('d-m-Y h:i:s A');
            $notification->human_time = $notification->created_at->diffForHumans();
            $notification->is_unread = false;

            return $notification;
        });

        return [
            'read_notifications' => $notifications,
        ];
    }

    public function markAsRead(Request $request): void
    {
        $request->validate([
            'notification_ids' => ['required', 'array'],
        ]);

        DB::beginTransaction();

        try {
            /** @var Admin $admin */
            $admin = $request->user();

            $this->notificationQueries->markAsReadByIds(
                (array) $request->notification_ids,
                $admin->getKey(),
                ModelMapping::ADMIN->name
            );
            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Mark as read', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();
        }
    }

    public function markAsUnRead(Request $request): void
    {
        $request->validate([
            'notification_ids' => ['required', 'array'],
        ]);

        DB::beginTransaction();

        try {
            /** @var Admin $admin */
            $admin = $request->user();

            $this->notificationQueries->markAsUnReadByIds(
                (array) $request->notification_ids,
                $admin->getKey(),
                ModelMapping::ADMIN->name
            );
            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Mark as unread', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();
        }
    }
}
