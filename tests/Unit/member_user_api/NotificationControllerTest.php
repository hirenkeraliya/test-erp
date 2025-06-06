<?php

declare(strict_types=1);

use App\Domains\Notification\NotificationQueries;
use App\Http\Controllers\Api\Member\NotificationController;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the getUnReadNotificationList query method of the un read Notification list with proper response',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Member => $member);

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('getUnReadNotifications')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 10, 10));
        });

        $notificationController = resolve(NotificationController::class);
        $response = $notificationController->getUnReadNotificationList($request);

        expect($response)->toHaveKeys(
            ['unread_notifications', 'total_records', 'last_page', 'current_page', 'per_page']
        );
    }
);

test(
    'It calls the getArchivedNotificationList query method of the archived Notification list with proper response',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Member => $member);

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('getArchivedNotifications')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 10, 10));
        });

        $notificationController = resolve(NotificationController::class);
        $response = $notificationController->getArchivedNotificationList($request);

        expect($response)->toHaveKeys(
            ['archived_notifications', 'total_records', 'last_page', 'current_page', 'per_page']
        );
    }
);

test(
    'It calls the markAsReadByIds query method of the return the proper response',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'employee_id' => 1,
        ]);

        $data = [
            'notification_ids' => [1],
        ];

        $request = $this->mock(Request::class, function ($mock) use ($member, $data): void {
            $mock->shouldReceive('validate')
                ->once()
                ->andReturn($data);
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($member);
            $mock->shouldReceive('all')
                ->andReturn($data);
        });
        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsReadByIds')
                ->once();
        });

        $notificationController = resolve(NotificationController::class);
        $response = $notificationController->markAsRead($request);
        expect($response)->toBeNull();
    }
);

test(
    'It calls the markAsUnReadByIds query method of the return the proper response',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'employee_id' => 1,
        ]);

        $data = [
            'notification_ids' => [1],
        ];

        $request = $this->mock(Request::class, function ($mock) use ($member, $data): void {
            $mock->shouldReceive('validate')
                ->once()
                ->andReturn($data);
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($member);
            $mock->shouldReceive('all')
                ->andReturn($data);
        });
        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsUnReadByIds')
                ->once();
        });

        $notificationController = resolve(NotificationController::class);
        $response = $notificationController->markAsUnRead($request);
        expect($response)->toBeNull();
    }
);
