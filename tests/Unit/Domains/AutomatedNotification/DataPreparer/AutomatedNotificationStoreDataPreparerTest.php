<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotificationStore\DataPreparer\AutomatedNotificationStoreDataPreparer;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationStore;
use App\Models\Location;

beforeEach(function (): void {
    $this->companyId = 1;

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->automatedNotification = AutomatedNotification::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
    ]);

    $this->automatedNotificationStore = AutomatedNotificationStore::factory()->make([
        'id' => 1,
        'automated_notification_id' => $this->automatedNotification->id,
        'location_id' => $this->location->id,
        'low_stock_alert_threshold' => 10,
    ]);

    $this->automatedNotificationStore->location = $this->location;
    $this->automatedNotification->automatedNotificationStores = collect([$this->automatedNotificationStore]);
});

test('prepareDataForAutomatedNotification method return array', function (): void {
    $response = AutomatedNotificationStoreDataPreparer::prepareDataForAutomatedNotification(
        $this->automatedNotification->automatedNotificationStores
    );

    expect($response)->toBeArray();
    $this->assertEquals($this->automatedNotificationStore->id, $response[0]['id']);
    $this->assertEquals(
        $this->automatedNotificationStore->low_stock_alert_threshold,
        $response[0]['low_stock_alert_threshold']
    );
});
