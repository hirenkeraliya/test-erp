<?php

declare(strict_types=1);

use App\Domains\Notification\NotificationQueries;
use App\Http\Controllers\Admin\NotificationController;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

test(
    'It calls the fetchMessages query method of the Notification queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 2,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Admin => $admin);

        $notification = Notification::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'from_user_id' => 1,
            'from_user_type' => 'STORE_MANAGER',
            'to_user_id' => $admin->id,
            'to_user_type' => 'ADMIN',
            'message' => 'Stock Transfer Number 0001TO000000001',
            'created_at' => Carbon::now(),
        ]);

        $storeManager->employee = $employee;

        $notification->fromUser = $storeManager;

        $notificationQueries = $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
            $mock->shouldReceive('fetchMessages')
                ->once()
                ->andReturn(collect([$notification]));
        });

        $notificationController = new NotificationController($notificationQueries);

        $response = $notificationController->fetchNotifications($request);

        expect($response['notifications']->first()->toArray())
            ->toHaveKeys(['id', 'from_user_id', 'from_user_type', 'message', 'created_at']);
    }
);

test(
    'It calls the markAllAsRead query method of the Notification queries class and returns proper response',
    function (): void {
        setCompanyIdInSession(1);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Admin => $admin);

        $notificationQueries = $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAllAsRead')
                ->once();
        });

        $notificationController = new NotificationController($notificationQueries);

        $notificationController->markAllAsRead($request);
        $this->assertTrue(true);
    }
);

test(
    'It calls the fetchReadNotifications query method of the Notification queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 2,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Admin => $admin);

        $notification = Notification::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'from_user_id' => 1,
            'from_user_type' => 'STORE_MANAGER',
            'to_user_id' => $admin->id,
            'to_user_type' => 'ADMIN',
            'message' => 'Stock Transfer Number 0001TO000000001',
            'created_at' => Carbon::now(),
        ]);

        $storeManager->employee = $employee;

        $notification->fromUser = $storeManager;

        $notificationQueries = $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
            $mock->shouldReceive('fetchReadMessages')
                ->once()
                ->andReturn(collect([$notification]));
        });

        $notificationController = new NotificationController($notificationQueries);

        $response = $notificationController->fetchReadNotifications($request);

        expect($response['read_notifications']->first()->toArray())
            ->toHaveKeys(['id', 'from_user_id', 'from_user_type', 'message', 'created_at']);
    }
);
