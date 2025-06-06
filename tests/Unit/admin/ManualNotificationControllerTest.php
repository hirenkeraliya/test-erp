<?php

declare(strict_types=1);

use App\Domains\ManualNotification\DataObjects\ManualNotificationData;
use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\Jobs\ManualNotificationSendJob;
use App\Domains\ManualNotification\ManualNotificationQueries;
use App\Domains\ManualNotification\Resources\ManualNotificationListResource;
use App\Http\Controllers\Admin\ManualNotificationController;
use App\Models\Admin;
use App\Models\ManualNotification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;

test(
    'It calls the list query method of the manual notification queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $manualNotificationQueries = $this->mock(ManualNotificationQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $styleController = new ManualNotificationController($manualNotificationQueries);

        $response = $styleController->fetchManualNotifications(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(ManualNotificationListResource::collection(collect([])), $response['data']);
    }
);

test('It calls addNew method of the manual notification queries class', function (): void {
    Queue::fake();
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $manualNotificationDetails = [
        'title' => 'test',
        'message' => 'message',
        'notification_type' => ManualNotificationTypes::PROMOTERS->value,
        'promoter_filter_type' => PromotersFilter::PROMOTERS->value,
        'member_filter_type' => null,
        'promoter_ids' => [1],
    ];

    $manualNotificationRecord = new ManualNotificationData(...$manualNotificationDetails);

    $request = new Request();

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $manualNotification = ManualNotification::factory()->make([
        'id' => 1,
    ]);

    $manualNotificationQueries = $this->mock(ManualNotificationQueries::class, function ($mock) use (
        $manualNotificationRecord,
        $companyId,
        $manualNotification
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($manualNotificationRecord, $companyId)
            ->andReturn($manualNotification);
    });

    $manualNotificationController = new ManualNotificationController($manualNotificationQueries);
    $redirectResponse = $manualNotificationController->store($manualNotificationRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Notification added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/manual-notifications', $redirectResponse->getTargetUrl());
    Queue::assertPushed(ManualNotificationSendJob::class);
});

test(
    'It calls the fetchManualNotificationDetailsByManualNotificationId method of the manualNotificationQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $manualNotification = ManualNotification::factory()->make([
            'notification_type' => ManualNotificationTypes::PROMOTERS->value,
            'company_id' => $companyId,
        ]);
        $saleQueries = $this->mock(ManualNotificationQueries::class, function ($mock) use (
            $companyId,
            $manualNotification
        ): void {
            $mock->shouldReceive('getWithById')
                ->once()
                ->with(1, $companyId)
                ->andReturn($manualNotification);
        });

        $manualNotificationController = new ManualNotificationController($saleQueries);
        $response = $manualNotificationController->fetchDetailsByManualNotificationId(1);

        expect($response)
            ->toHaveKey('manual_notification_details')
            ->toHaveKey('manual_notification_type');
    }
);
