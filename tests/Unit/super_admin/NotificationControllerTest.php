<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Notification\NotificationQueries;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Models\Notification;
use App\Models\SuperAdmin;
use Carbon\Carbon;

test(
    'It calls the fetchMessages query method of the Notification queries class and returns proper response',
    function (): void {
        $superAdmin = SuperAdmin::factory()->make([
            'id' => 1,
        ]);
        loginSuperAdmin($superAdmin);

        $notification = Notification::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'from_user_id' => 1,
            'from_user_type' => null,
            'to_user_id' => 1,
            'to_user_type' => ModelMapping::SUPER_ADMIN->value,
            'message' => 'Test Message',
            'created_at' => Carbon::now(),
        ]);

        $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
            $mock->shouldReceive('fetchMessagesByUserIdAndType')
                ->once()
                ->andReturn(collect([$notification]));
        });

        $notificationController = new NotificationController();

        $response = $notificationController->fetchNotifications();

        expect($response['notifications']->first()->toArray())
            ->toHaveKeys(['id', 'from_user_id', 'from_user_type', 'message', 'created_at']);
    }
);

test(
    'It calls the markAllAsRead query method of the Notification queries class and returns proper response',
    function (): void {
        $superAdmin = SuperAdmin::factory()->make([
            'id' => 1,
        ]);
        loginSuperAdmin($superAdmin);

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAllAsReadByUserIdAndType')
                ->once();
        });

        $notificationController = new NotificationController();
        $notificationController->markAllAsRead();
    }
);

test(
    'It calls the fetchReadNotifications query method of the Notification queries class and returns proper response',
    function (): void {
        $superAdmin = SuperAdmin::factory()->make([
            'id' => 1,
        ]);
        loginSuperAdmin($superAdmin);

        $notification = Notification::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'from_user_id' => 1,
            'from_user_type' => null,
            'to_user_id' => 1,
            'to_user_type' => ModelMapping::SUPER_ADMIN->value,
            'message' => 'Test Message',
            'created_at' => Carbon::now(),
        ]);

        $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
            $mock->shouldReceive('fetchReadMessagesByUserIdAndType')
                ->once()
                ->andReturn(collect([$notification]));
        });

        $notificationController = new NotificationController();

        $response = $notificationController->fetchReadNotifications();

        expect($response['read_notifications']->first()->toArray())
            ->toHaveKeys(['id', 'from_user_id', 'from_user_type', 'message', 'created_at']);
    }
);
