<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\AutomatedNotificationProduct\DataPreparer\AutomatedNotificationProductDataPreparer;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationProduct;
use App\Models\Location;
use App\Models\Product;

beforeEach(function (): void {
    $this->companyId = 1;

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $this->automatedNotification = AutomatedNotification::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
    ]);

    $this->automatedNotificationProduct = AutomatedNotificationProduct::factory()->make([
        'id' => 1,
        'automated_notification_id' => $this->automatedNotification->id,
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'low_stock_alert_threshold' => 10,
    ]);

    $this->automatedNotificationProduct->product = $this->product;
    $this->automatedNotificationProduct->location = $this->location;
    $this->automatedNotification->automatedNotificationProducts = collect([$this->automatedNotificationProduct]);
});

test('prepareDataForAutomatedNotification method return array', function (): void {
    $response = AutomatedNotificationProductDataPreparer::prepareDataForAutomatedNotification(
        $this->automatedNotification->automatedNotificationProducts
    );

    expect($response)->toBeArray();
    $this->assertEquals($this->automatedNotificationProduct->id, $response[0]['id']);
    $this->assertEquals(
        $this->automatedNotificationProduct->low_stock_alert_threshold,
        $response[0]['low_stock_alert_threshold']
    );
});
