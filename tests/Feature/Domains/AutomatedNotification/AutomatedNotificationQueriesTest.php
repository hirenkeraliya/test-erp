<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\DataObjects\AutomatedNotificationData;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationProduct;
use App\Models\AutomatedNotificationStore;
use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\Location;
use App\Models\Product;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->automatedNotificationA = AutomatedNotification::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
    ]);

    $this->automatedNotificationB = AutomatedNotification::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::NO_STOCK->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
    ]);

    $this->automatedNotificationQueries = new AutomatedNotificationQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('Automated Notification can be searched', function (): void {
    $response = $this->automatedNotificationQueries->listQuery([
        'search_text' => AutomatedNotificationTimeframeTypes::getFormattedCaseName(
            $this->automatedNotificationA->timeframe_type_id
        ),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('timeframe_type_id', $this->automatedNotificationA->timeframe_type_id);
});

test('New automated notification can be added', function (): void {
    $this->automatedNotificationQueries->addNew(
        new AutomatedNotificationData(
            'name',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            null,
            [],
            [],
            []
        ),
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => null,
    ]);
});

test('New automated notification can be added with automated email recipients.', function (): void {
    $emailRecipient = EmailRecipient::factory()->create([
        'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        'company_id' => $this->companyId,
    ]);

    $this->automatedNotificationQueries->addNew(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            20,
            [$emailRecipient->id]
        ),
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 20,
    ]);

    $this->assertDatabaseHas('automated_notification_email_recipient', [
        'email_recipient_id' => $emailRecipient->id,
    ]);
});

test('An automated notification can be fetched', function (): void {
    $response = $this->automatedNotificationQueries->getById($this->automatedNotificationA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('timeframe_type_id', $this->automatedNotificationA->timeframe_type_id);
});

test('An automated notification can be updated', function (): void {
    $this->automatedNotificationQueries->update(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            10,
            []
        ),
        $this->automatedNotificationA,
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 10,
    ]);
});

test('An automated notification can be updated with automated email recipients', function (): void {
    $emailRecipient = EmailRecipient::factory()->create([
        'email_type_id' => EmailTypes::AUTOMATED_NOTIFICATION->value,
        'company_id' => $this->companyId,
    ]);

    $this->automatedNotificationQueries->update(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            20,
            [$emailRecipient->id]
        ),
        $this->automatedNotificationA,
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 20,
    ]);

    $this->assertDatabaseHas('automated_notification_email_recipient', [
        'automated_notification_id' => $this->automatedNotificationA->id,
        'email_recipient_id' => $emailRecipient->id,
    ]);
});

test('getAutomatedNotificationExport method returns automated notification as expected', function (): void {
    $response = $this->automatedNotificationQueries->getAutomatedNotificationExport([
        'search_text' => AutomatedNotificationTimeframeTypes::getFormattedCaseName(
            $this->automatedNotificationA->timeframe_type_id
        ),
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('timeframe_type_id', $this->automatedNotificationA->timeframe_type_id);
});

test('A automated notification can be fetched with all related models', function (): void {
    $response = $this->automatedNotificationQueries->getByIdWithRelations(
        $this->automatedNotificationA->id,
        $this->companyId
    );
    expect($response->toArray())
        ->toHaveKey('type_id', $this->automatedNotificationA->type_id)
        ->toHaveKey('timeframe_type_id', $this->automatedNotificationA->timeframe_type_id)
        ->toHaveKeys(['monthly', 'weekly', 'automated_email_recipients', 'automated_notification_stores']);
});

test('Automated Notification Retrieval by Type ID with Related Models', function (): void {
    $response = $this->automatedNotificationQueries->getByTypeIdWithRelations(
        $this->automatedNotificationA->type_id,
    );
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->automatedNotificationA->id)
        ->toHaveKey('type_id', $this->automatedNotificationA->type_id)
        ->toHaveKey('company_id', $this->automatedNotificationA->company_id)
        ->toHaveKeys(['monthly', 'weekly']);
});

test('New automated notification can be added with stores.', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $automatedNotificationStore = AutomatedNotificationStore::factory()->create([
        'location_id' => $location->id,
    ]);

    $automatedNotificationStore->location = $location;

    $this->automatedNotificationQueries->addNew(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            20,
            [],
            [
                [
                    'id' => $location->id,
                    'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
                ],
            ]
        ),
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 20,
    ]);

    $this->assertDatabaseHas('automated_notification_stores', [
        'id' => $automatedNotificationStore->id,
        'automated_notification_id' => $automatedNotificationStore->automated_notification_id,
        'location_id' => $automatedNotificationStore->location_id,
        'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
    ]);
});

test('An automated notification can be updated with stores', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $automatedNotificationStore = AutomatedNotificationStore::factory()->create([
        'location_id' => $location->id,
    ]);

    $automatedNotificationStore->location = $location;

    $this->automatedNotificationQueries->update(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            20,
            [],
            [
                [
                    'id' => $location->id,
                    'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
                ],
            ]
        ),
        $this->automatedNotificationA,
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'id' => $this->automatedNotificationA->id,
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 20,
    ]);

    $this->assertDatabaseHas('automated_notification_stores', [
        'id' => $automatedNotificationStore->id,
        'automated_notification_id' => $automatedNotificationStore->automated_notification_id,
        'location_id' => $automatedNotificationStore->location_id,
        'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
    ]);
});

test('the removeSelectedStores method can delete attach stores of automated notification ', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $automatedNotificationStore = AutomatedNotificationStore::factory()->create([
        'automated_notification_id' => $this->automatedNotificationA->id,
        'location_id' => $location->id,
        'low_stock_alert_threshold' => 10,
    ]);

    $this->assertDatabaseHas('automated_notification_stores', [
        'id' => $automatedNotificationStore->id,
        'automated_notification_id' => $this->automatedNotificationA->id,
        'location_id' => $automatedNotificationStore->location_id,
        'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
    ]);

    $automatedNotificationStore->location = $location;

    $this->automatedNotificationA->automatedNotificationStores = $automatedNotificationStore;

    $this->automatedNotificationQueries->removeSelectedStores($this->automatedNotificationA->id, $this->companyId);

    $this->assertDatabaseMissing('automated_notification_stores', [
        'id' => $automatedNotificationStore->id,
        'automated_notification_id' => $this->automatedNotificationA->id,
        'location_id' => $automatedNotificationStore->location_id,
        'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
    ]);
});

test('the getByIdWithAutomatedNotificationStores method can get automated notification with stores', function (): void {
    $response = $this->automatedNotificationQueries->getByIdWithAutomatedNotificationStores(
        $this->automatedNotificationA->id,
        $this->companyId
    );

    expect($response->toArray())
        ->toHaveKey('id', $this->automatedNotificationA->id)
        ->toHaveKeys(['automated_notification_stores']);
});

test('the getByIdWithRelationsForJob method can get automated notification', function (): void {
    $response = $this->automatedNotificationQueries->getByIdWithRelationsForJob($this->automatedNotificationA->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->automatedNotificationA->id)
        ->toHaveKey('company_id', $this->automatedNotificationA->company_id)
        ->toHaveKeys(
            [
                'automated_notification_stores',
                'automated_email_recipients',
                'automated_notification_products',
                'products',
            ]
        );
});

test('New automated notification can be added with products.', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $automatedNotificationProduct = AutomatedNotificationProduct::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    $automatedNotificationProduct->location = $location;
    $automatedNotificationProduct->product = $product;

    $this->automatedNotificationQueries->addNew(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            20,
            [],
            [],
            [
                [
                    'id' => $product->id,
                    'location_id' => $location->id,
                    'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
                ],
            ]
        ),
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 20,
    ]);

    $this->assertDatabaseHas('automated_notification_products', [
        'id' => $automatedNotificationProduct->id,
        'automated_notification_id' => $automatedNotificationProduct->automated_notification_id,
        'product_id' => $automatedNotificationProduct->product_id,
        'location_id' => $automatedNotificationProduct->location_id,
        'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
    ]);
});

test('An automated notification can be updated with products', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $automatedNotificationProduct = AutomatedNotificationProduct::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    $automatedNotificationProduct->location = $location;
    $automatedNotificationProduct->product = $product;

    $this->automatedNotificationQueries->update(
        new AutomatedNotificationData(
            'abcd',
            '',
            AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            true,
            AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            [],
            [],
            20,
            [],
            [],
            [
                [
                    'id' => $product->id,
                    'location_id' => $location->id,
                    'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
                ],
            ]
        ),
        $this->automatedNotificationA,
        $this->companyId
    );

    $this->assertDatabaseHas('automated_notifications', [
        'id' => $this->automatedNotificationA->id,
        'company_id' => $this->companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'sent_notification' => true,
        'low_stock_alert_threshold' => 20,
    ]);

    $this->assertDatabaseHas('automated_notification_products', [
        'id' => $automatedNotificationProduct->id,
        'automated_notification_id' => $automatedNotificationProduct->automated_notification_id,
        'product_id' => $automatedNotificationProduct->product_id,
        'location_id' => $automatedNotificationProduct->location_id,
        'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
    ]);
});

test('the removeSelectedProducts method can delete attach products of automated notification ', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $automatedNotificationProduct = AutomatedNotificationProduct::factory()->create([
        'automated_notification_id' => $this->automatedNotificationA->id,
        'product_id' => $product->id,
        'location_id' => $location->id,
    ]);

    $this->assertDatabaseHas('automated_notification_products', [
        'id' => $automatedNotificationProduct->id,
        'automated_notification_id' => $this->automatedNotificationA->id,
        'location_id' => $automatedNotificationProduct->location_id,
        'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
    ]);

    $automatedNotificationProduct->location = $location;
    $automatedNotificationProduct->product = $product;

    $this->automatedNotificationA->automatedNotificationProducts = $automatedNotificationProduct;

    $this->automatedNotificationQueries->removeSelectedProducts($this->automatedNotificationA->id, $this->companyId);

    $this->assertDatabaseMissing('automated_notification_products', [
        'id' => $automatedNotificationProduct->id,
        'automated_notification_id' => $this->automatedNotificationA->id,
        'product_id' => $automatedNotificationProduct->product_id,
        'location_id' => $automatedNotificationProduct->location_id,
        'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
    ]);
});

test(
    'the getByIdWithAutomatedNotificationProducts method can get automated notification with products',
    function (): void {
        $response = $this->automatedNotificationQueries->getByIdWithAutomatedNotificationProducts(
            $this->automatedNotificationA->id,
            $this->companyId
        );

        expect($response->toArray())
            ->toHaveKey('id', $this->automatedNotificationA->id)
            ->toHaveKeys(['automated_notification_products']);
    }
);

test(
    'the updateProductIdsInAutomatedNotificationProductPivot method can update pivot AutomatedNotificationProduct table ',
    function (): void {
        $product = Product::factory()->create([
            'id' => 1,
            'company_id' => $this->companyId,
        ]);

        $productA = Product::factory()->create([
            'id' => 2,
            'company_id' => $this->companyId,
        ]);

        $this->automatedNotificationA->products()->sync([$product->id]);

        $this->automatedNotificationQueries->updateProductIdsInAutomatedNotificationProductPivot(
            $product->id,
            $productA->id
        );

        $this->assertDatabaseHas('automated_notification_product', [
            'automated_notification_id' => $this->automatedNotificationA->id,
            'product_id' => $productA->id,
        ]);
    }
);

test(
    'the getLowStockNotificationByCompanyIdAndType method can get the low stock notification of type low stock company',
    function (): void {
        $response = $this->automatedNotificationQueries->getLowStockNotificationByCompanyIdAndType($this->companyId);
        expect($response->toArray())->toHaveKey('id', $this->automatedNotificationA->id);
    }
);

test(
    'the getByIdWithStores method can get the notification',
    function (): void {
        $location = Location::factory()->create();
        $this->automatedNotificationA->locations()->sync([$location->id]);
        $response = $this->automatedNotificationQueries->getByIdWithStores(
            $this->automatedNotificationA->id,
            $this->companyId
        );
        expect($response->toArray())->toHaveKey('id', $this->automatedNotificationA->id)
            ->toHaveKey('locations');
    }
);
