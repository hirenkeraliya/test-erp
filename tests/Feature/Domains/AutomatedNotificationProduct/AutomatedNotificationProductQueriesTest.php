<?php

declare(strict_types=1);

use App\Domains\AutomatedNotificationProduct\AutomatedNotificationProductQueries;
use App\Models\AutomatedNotificationProduct;
use App\Models\Company;
use App\Models\Product;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);
    session()->put('admin_company_id', $this->companyId);

    $this->automatedNotificationProduct = AutomatedNotificationProduct::factory()->create([
        'product_id' => $this->product->id,
    ]);

    $this->automatedNotificationProductQueries = resolve(AutomatedNotificationProductQueries::class);
});

test('addNewOrUpdate method update or create new automated notification', function (): void {
    $data = [
        'product_id' => $this->product->id,
        'location_id' => $this->automatedNotificationProduct->location_id,
        'low_stock_alert_threshold' => 10,
        'automated_notification_id' => $this->automatedNotificationProduct->automated_notification_id,
    ];
    $this->automatedNotificationProductQueries->addNewOrUpdate($data);

    $this->assertDatabaseHas('automated_notification_products', [
        'id' => $this->automatedNotificationProduct->id,
        'product_id' => $this->automatedNotificationProduct->product_id,
        'location_id' => $this->automatedNotificationProduct->location_id,
        'low_stock_alert_threshold' => 10,
        'automated_notification_id' => $this->automatedNotificationProduct->automated_notification_id,
    ]);
});

test('getListWithProductAndInventoryByProductId method get list by product Id', function (): void {
    $response = $this->automatedNotificationProductQueries->getListWithProductAndInventoryByProductId(
        $this->product->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->automatedNotificationProduct->id)
        ->toHaveKey('product_id', $this->automatedNotificationProduct->product_id)
        ->toHaveKey('location_id', $this->automatedNotificationProduct->location_id);
});
