<?php

declare(strict_types=1);

namespace App\Domains\Notification;

use App\CommonFunctions;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationQueries
{
    public function fetchMessages(int $companyId, int $userId, string $user): Collection
    {
        return Notification::query()
            ->select('id', 'message', 'created_at')
            ->where('company_id', $companyId)
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $user)
            ->whereNull('mark_as_read_at')
            ->orderby('id', 'desc')
            ->limit(5)
            ->get();
    }

    public function markAllAsRead(int $companyId, int $userId, string $user): void
    {
        $notifications = Notification::query()
            ->where('company_id', $companyId)
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $user)->get();

        foreach ($notifications as $notification) {
            $notification->mark_as_read_at = Carbon::now()->toDateTimeString();
            $notification->mark_as_read_by_id = $userId;
            $notification->mark_as_read_by_type = $user;
            $notification->save();
        }
    }

    public function addNew(
        ?int $companyId,
        ?string $sourceUser,
        ?int $fromUserId,
        string $destinationUser,
        int $toUserId,
        string $message,
        ?string $title = null,
        ?string $textMessage = null,
        ?array $payload = [],
    ): void {
        Notification::create([
            'company_id' => $companyId,
            'from_user_id' => $fromUserId,
            'from_user_type' => $sourceUser,
            'to_user_id' => $toUserId,
            'to_user_type' => $destinationUser,
            'message' => $message,
            'title' => $title,
            'text_message' => $textMessage,
            'payload' => $payload,
        ]);
    }

    public function deleteNotifications(string $dateToDelete): int
    {
        return Notification::where('created_at', '<', CommonFunctions::addEndTime($dateToDelete))->delete();
    }

    public function fetchMessagesByUserIdAndType(int $userId, string $user): Collection
    {
        return Notification::query()
            ->select('id', 'message', 'created_at')
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $user)
            ->whereNull('mark_as_read_at')
            ->orderby('id', 'desc')
            ->limit(5)
            ->get();
    }

    public function markAllAsReadByUserIdAndType(int $userId, string $user): void
    {
        Notification::query()
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $user)
            ->update([
                'mark_as_read_at' => Carbon::now(),
                'mark_as_read_by_id' => $userId,
                'mark_as_read_by_type' => $user,
            ]);
    }

    public function addNewWithNullValue(
        string $destinationUser,
        int $toUserId,
        string $message,
        ?int $companyId = null,
        ?string $sourceUser = null,
        ?int $fromUserId = null,
        ?string $textMessage = null,
        ?string $payload = null,
    ): Notification {
        return Notification::create([
            'company_id' => $companyId,
            'from_user_id' => $fromUserId,
            'from_user_type' => $sourceUser,
            'to_user_id' => $toUserId,
            'to_user_type' => $destinationUser,
            'message' => $message,
            'text_message' => $textMessage,
            'payload' => $payload,
        ]);
    }

    public function updateMessage(Notification $notification, string $message): void
    {
        $notification->message = $message;
        $notification->save();
    }

    public function getById(int $notificationId): Notification
    {
        return Notification::findOrFail($notificationId);
    }

    public function markAsReadById(int $notificationId, int $userId, string $userType): void
    {
        $notification = $this->getById($notificationId);
        $notification->mark_as_read_at = Carbon::now()->format('Y-m-d H:i:s');
        $notification->mark_as_read_by_id = $userId;
        $notification->mark_as_read_by_type = $userType;
        $notification->save();
    }

    public function fetchReadMessages(int $companyId, int $userId, string $user): Collection
    {
        return Notification::query()
            ->select('id', 'message', 'created_at')
            ->where('company_id', $companyId)
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $user)
            ->whereNotNull('mark_as_read_at')
            ->orderby('id', 'desc')
            ->limit(5)
            ->get();
    }

    public function fetchReadMessagesByUserIdAndType(int $userId, string $user): Collection
    {
        return Notification::query()
            ->select('id', 'message', 'created_at')
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $user)
            ->whereNotNull('mark_as_read_at')
            ->orderby('id', 'desc')
            ->limit(5)
            ->get();
    }

    public function markAsReadByIds(array $notificationIds, int $userId, string $userType): void
    {
        $notifications = Notification::query()
            ->whereIntegerInRaw('id', $notificationIds)
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $userType)->get();

        foreach ($notifications as $notification) {
            $notification->mark_as_read_at = Carbon::now()->toDateTimeString();
            $notification->mark_as_read_by_id = $userId;
            $notification->mark_as_read_by_type = $userType;
            $notification->save();
        }
    }

    public function markAsUnReadByIds(array $notificationIds, int $userId, string $userType): void
    {
        $notifications = Notification::query()
            ->whereIntegerInRaw('id', $notificationIds)
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $userType)->get();

        foreach ($notifications as $notification) {
            $notification->mark_as_read_at = null;
            $notification->mark_as_read_by_id = null;
            $notification->mark_as_read_by_type = null;
            $notification->save();
        }
    }

    public function getUnReadNotifications(array $filterData, int $userId, string $userType): LengthAwarePaginator
    {
        return Notification::select('id', 'title', 'message', 'text_message', 'payload', 'created_at')
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $userType)
            ->whereNull('mark_as_read_at')
            ->orderBy('created_at', 'desc')
            ->paginate($filterData['per_page']);
    }

    public function getArchivedNotifications(array $filterData, int $userId, string $userType): LengthAwarePaginator
    {
        return Notification::select(
            'id',
            'title',
            'message',
            'text_message',
            'payload',
            'mark_as_read_at',
            'created_at'
        )
            ->where('to_user_id', $userId)
            ->whereCaseSensitive('to_user_type', $userType)
            ->whereNotNull('mark_as_read_at')
            ->orderBy('mark_as_read_at', 'desc')
            ->paginate($filterData['per_page']);
    }
}
