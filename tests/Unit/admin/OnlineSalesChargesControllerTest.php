<?php

declare(strict_types=1);

use App\Domains\OnlineSalesCharges\DataObjects\OnlineSalesChargesData;
use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use App\Domains\OnlineSalesCharges\OnlineSalesChargesQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Http\Controllers\Admin\OnlineSalesChargesController;
use App\Models\OnlineSalesCharges;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('It calls the list query method of the queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 10,
    ];

    $onlineSalesChargesQueries = $this->mock(OnlineSalesChargesQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 10));
    });

    $onlineSalesChargesController = new OnlineSalesChargesController($onlineSalesChargesQueries);

    $response = $onlineSalesChargesController->fetchOnlineSalesCharges(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
});

test('It calls addNew method of the queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $shippingZone = ShippingZone::factory()->make([
        'id' => 1,
        'country_id' => 1,
        'company_id' => $companyId,
    ]);

    $onlineSalesChargesData = OnlineSalesCharges::factory()->make([
        'shipping_charge_type_id' => ShippingChargeTypes::NUMBER_OF_ITEMS->value,
        'company_id' => $companyId,
        'shipping_zone_id' => $shippingZone->id,
        'is_available_in_ecommerce' => true,
        'sale_channel_ids' => [],
    ])->toArray();
    unset($onlineSalesChargesData['company_id']);
    unset($onlineSalesChargesData['status']);

    $onlineSalesChargesData['online_sales_charge_tiers'] = [];

    $onlineSalesChargeRecords = new OnlineSalesChargesData(...$onlineSalesChargesData);

    $onlineSalesChargesQueries = $this->mock(OnlineSalesChargesQueries::class, function ($mock) use (
        $onlineSalesChargeRecords,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($onlineSalesChargeRecords, $companyId);
    });

    $onlineSalesChargesController = new OnlineSalesChargesController($onlineSalesChargesQueries);
    $redirectResponse = $onlineSalesChargesController->store($onlineSalesChargeRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The online sales charge has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
});

test('It calls get by id method of the query class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $shippingZone = ShippingZone::factory()->make([
        'id' => 1,
        'country_id' => 1,
        'company_id' => $companyId,
    ]);

    $onlineSalesChargesData = OnlineSalesCharges::factory()->make([
        'shipping_charge_type_id' => ShippingChargeTypes::NUMBER_OF_ITEMS->value,
        'company_id' => $companyId,
        'minimum_value' => 90.10,
        'maximum_value' => 100.10,
        'shipping_zone_id' => $shippingZone->id,
    ])->toArray();

    $onlineSalesChargesQueries = $this->mock(OnlineSalesChargesQueries::class, function ($mock) use (
        $onlineSalesChargesData,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new OnlineSalesCharges($onlineSalesChargesData));
    });

    $this->mock(SaleChannelQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->with($companyId)
            ->andReturn(collect());
    });

    $this->mock(ShippingZoneQueries::class, function ($mock): void {
        $mock->shouldReceive('getAll')
            ->once()
            ->andReturn(collect());
    });

    $onlineSalesChargesController = new OnlineSalesChargesController($onlineSalesChargesQueries);
    $response = $onlineSalesChargesController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'onlineSalesCharge',
                fn (Assert $onlineSalesCharge): Assert => $onlineSalesCharge
                    ->where('name', $onlineSalesChargesData['name'])
                    ->where('minimum_value', (float) $onlineSalesChargesData['minimum_value'])
                    ->where('maximum_value', (float) $onlineSalesChargesData['maximum_value'])
                    ->etc()
            )
    );
});

test('It calls update method of the query class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $shippingZone = ShippingZone::factory()->make([
        'id' => 1,
        'country_id' => 1,
        'company_id' => $companyId,
    ]);

    $onlineSalesChargesData = OnlineSalesCharges::factory()->make([
        'shipping_charge_type_id' => ShippingChargeTypes::WEIGHT->value,
        'company_id' => $companyId,
        'shipping_zone_id' => $shippingZone->id,
        'is_available_in_ecommerce' => true,
        'sale_channel_ids' => [],
    ])->toArray();
    unset($onlineSalesChargesData['company_id']);
    unset($onlineSalesChargesData['status']);

    $onlineSalesChargesData['online_sales_charge_tiers'] = [[
        'min_weight' => 1,
        'max_weight' => 5,
        'amount' => 50,
    ]];

    $onlineSalesChargeRecords = new OnlineSalesChargesData(...$onlineSalesChargesData);

    $onlineSalesChargesQueries = $this->mock(OnlineSalesChargesQueries::class, function ($mock) use (
        $onlineSalesChargeRecords,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($onlineSalesChargeRecords, 1, $companyId);
    });

    $onlineSalesChargesController = new OnlineSalesChargesController($onlineSalesChargesQueries);
    $redirectResponse = $onlineSalesChargesController->update($onlineSalesChargeRecords, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The online sales charge has been updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
});

test('It calls delete method of the query class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $onlineSalesChargesQueries = $this->mock(OnlineSalesChargesQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('delete')
            ->once()
            ->with(1, $companyId);
    });

    $onlineSalesChargesController = new OnlineSalesChargesController($onlineSalesChargesQueries);
    $redirectResponse = $onlineSalesChargesController->delete(1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Online sales charge deleted successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls toggleStatus method of the query class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $onlineSalesChargesQueries = $this->mock(OnlineSalesChargesQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('toggleStatus')
            ->once()
            ->with(1, $companyId);
    });

    $onlineSalesChargesController = new OnlineSalesChargesController($onlineSalesChargesQueries);
    $redirectResponse = $onlineSalesChargesController->toggleStatus(new Request([
        'onlineSalesChargeId' => 1,
    ]));

    expect($redirectResponse)->toBeNull();
});
