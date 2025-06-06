<?php

declare(strict_types=1);

use App\Domains\Notification\NotificationQueries;
use App\Http\Controllers\WarehouseManager\NotificationController;
use App\Models\Notification;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

test(
    'It calls the fetchMessages query method of the Notification queries class and returns proper response',
    function (): void {
        $companyId = 1;
        $storeManagerOne = WarehouseManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $storeManagerTwo = WarehouseManager::factory()->make([
            'id' => 2,
            'employee_id' => 1,
        ]);

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);
        setStoreIdInSession($storeManagerOne->id);

        $notification = Notification::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'from_user_id' => $storeManagerOne->id,
            'from_user_type' => 'STORE_MANAGER',
            'to_user_id' => $storeManagerTwo->id,
            'to_user_type' => 'STORE_MANAGER',
            'message' => 'Stock Transfer Number 0001TO000000001',
            'created_at' => Carbon::now(),
        ]);

        $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
            $mock->shouldReceive('fetchMessages')
                ->once()
                ->andReturn(collect([$notification]));
        });

        $request = new Request();

        $request->setUserResolver(fn (): WarehouseManager => $storeManagerOne);

        $notificationController = new NotificationController();

        $response = $notificationController->fetchNotifications($request);

        expect($response['notifications']->first()->toArray())
            ->toHaveKeys(['id', 'from_user_id', 'from_user_type', 'message', 'created_at']);
    }
);

test(
    'It calls the markAllAsRead query method of the Notification queries class and returns proper response',
    function (): void {
        setWarehouseManagerWarehouseCompanyIdInSession(1);
        setStoreIdInSession(1);

        $storeManagerOne = WarehouseManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAllAsRead')
                ->once();
        });

        $request = new Request();

        $notificationController = new NotificationController();

        $request->setUserResolver(fn (): WarehouseManager => $storeManagerOne);

        $notificationController->markAllAsRead($request);
        $this->assertTrue(true);
    }
);

test(
    'It calls the fetchReadNotifications query method of the Notification queries class and returns proper response',
    function (): void {
        $companyId = 1;
        $wareManagerOne = WarehouseManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $storeManagerTwo = WarehouseManager::factory()->make([
            'id' => 2,
            'employee_id' => 1,
        ]);

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);
        setWarehouseManagerWarehouseIdInSession($wareManagerOne->id);

        $notification = Notification::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'from_user_id' => $wareManagerOne->id,
            'from_user_type' => 'WAREHOUSE_MANAGER',
            'to_user_id' => $storeManagerTwo->id,
            'to_user_type' => 'STORE_MANAGER',
            'message' => 'Stock Transfer Number 0001TO000000001',
            'created_at' => Carbon::now(),
        ]);

        $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
            $mock->shouldReceive('fetchReadMessages')
                ->once()
                ->andReturn(collect([$notification]));
        });

        $request = new Request();

        $request->setUserResolver(fn (): WarehouseManager => $wareManagerOne);

        $notificationController = new NotificationController();

        $response = $notificationController->fetchReadNotifications($request);

        expect($response['read_notifications']->first()->toArray())
            ->toHaveKeys(['id', 'from_user_id', 'from_user_type', 'message', 'created_at']);
    }
);
