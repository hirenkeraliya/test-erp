<?php

declare(strict_types=1);

use App\Domains\ManualNotification\DataObjects\ManualNotificationData;
use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\Enums\Statuses;
use App\Domains\ManualNotification\ManualNotificationQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ManualNotification;
use App\Models\Promoter;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->manualNotificationA = ManualNotification::factory()->create([
        'title' => 'test',
        'company_id' => $this->companyId,
        'type_id' => ManualNotificationTypes::PROMOTERS->value,
    ]);

    $this->manualNotificationQueries = new ManualNotificationQueries();
});

test('manual notification can be searched', function (): void {
    $response = $this->manualNotificationQueries->listQuery([
        'search_text' => 'test',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('title', $this->manualNotificationA->title);
});

test('new manual notification can be added', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $promoter = Promoter::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $data = [
        'title' => 'test',
        'message' => 'message',
        'notification_type' => ManualNotificationTypes::PROMOTERS->value,
        'promoter_filter_type' => PromotersFilter::PROMOTERS->value,
        'promoter_ids' => [$promoter->id],
    ];

    $notification = $this->manualNotificationQueries->addNew(
        new ManualNotificationData(...$data),
        $this->companyId
    );

    $this->assertDatabaseHas('manual_notifications', [
        'title' => 'test',
        'message' => 'message',
        'type_id' => ManualNotificationTypes::PROMOTERS->value,
        'promoter_filter_type_id' => PromotersFilter::PROMOTERS->value,
        'company_id' => $this->companyId,
    ]);

    $this->assertDatabaseHas('manual_notification_promoter', [
        'manual_notification_id' => $notification->id,
        'promoter_id' => $promoter->id,
    ]);
});

test(
    'It calls the getWithById method of the manualNotificationQueries class and returns proper response',
    function (): void {
        $response = $this->manualNotificationQueries->getWithById($this->manualNotificationA->id, $this->companyId);
        expect($response->toArray())
        ->toHaveKey('id', $this->manualNotificationA->id)
        ->toHaveKey('type_id', ManualNotificationTypes::PROMOTERS->value);
    }
);

test('manual notification mark As Completed', function (): void {
    $this->manualNotificationQueries->markAsCompleted($this->manualNotificationA);

    $this->assertDatabaseHas('manual_notifications', [
        'company_id' => $this->companyId,
        'status' => Statuses::COMPLETED->value,
    ]);
});

test('manual notification mark As In Progress', function (): void {
    $this->manualNotificationQueries->markAsInProgress($this->manualNotificationA);

    $this->assertDatabaseHas('manual_notifications', [
        'company_id' => $this->companyId,
        'status' => Statuses::IN_PROGRESS->value,
    ]);
});
