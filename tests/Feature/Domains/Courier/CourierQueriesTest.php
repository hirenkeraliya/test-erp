<?php

declare(strict_types=1);

use App\Domains\Common\Enums\CourierWebhookUrls;
use App\Domains\Courier\CourierQueries;
use App\Domains\Courier\DataObjects\CourierData;
use App\Domains\Courier\Enums\CourierTypes;
use App\Models\Courier;

beforeEach(function (): void {
    $this->courier = Courier::factory()->create();
    $this->courierQueries = new CourierQueries();
});

test('courier  can be fetch', function (): void {
    $response = $this->courierQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->courier->name);
});

test('New courier  can be added', function (): void {
    $this->courierQueries->addNew(new CourierData(
        'Test',
        '007',
        CourierTypes::NINJA_VAN->value,
        'test',
        '1',
        'add',
        [[
            'webhook_url_type_id' => CourierWebhookUrls::ACCESS_TOKEN->value,
            'url' => 'test/abc/create',
            'variance_url' => null,
        ]]
    ));

    $this->assertDatabaseHas(Courier::class, [
        'name' => 'Test',
        'code' => '007',
    ]);
});

test('A courier  can be updated', function (): void {
    $this->courierQueries->update(
        new CourierData(
            'Test',
            '007',
            CourierTypes::NINJA_VAN->value,
            'test',
            '1',
            'add',
            [[
                'webhook_url_type_id' => CourierWebhookUrls::ACCESS_TOKEN->value,
                'url' => 'test/abc/create',
                'variance_url' => null,
            ]]
        ),
        $this->courier
    );

    $this->assertDatabaseHas(Courier::class, [
        'name' => 'Test',
        'code' => '007',
    ]);

    $this->courier->refresh();
});
