<?php

declare(strict_types=1);

use App\Domains\ShippingZone\DataObjects\ShippingZoneData;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Models\Country;
use App\Models\ShippingZone;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the list query method of the shipping zone queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $shippingZoneQueries = $this->mock(ShippingZoneQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $shippingZoneController = new ShippingZoneController($shippingZoneQueries);

        $response = $shippingZoneController->fetchShippingZones(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls addNew method of the color queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $country = Country::factory()->make([
        'id' => 1,
    ]);
    $state = State::factory()->make([
        'country_id' => $country->id,
    ]);

    $shippingZone = ShippingZone::factory()->make([
        'company_id' => $companyId,
        'country_id' => $country->id,
        'state_ids' => [$state->id],
    ])->toArray();
    unset($shippingZone['company_id']);

    $shippingZoneRecord = new ShippingZoneData(...$shippingZone);

    $shippingZoneQueries = $this->mock(ShippingZoneQueries::class, function ($mock) use (
        $shippingZoneRecord,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($shippingZoneRecord, $companyId);
    });

    $shippingZoneController = new ShippingZoneController($shippingZoneQueries);
    $redirectResponse = $shippingZoneController->store($shippingZoneRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Shipping Zone added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/shipping-zones', $redirectResponse->getTargetUrl());
});

test('It calls update method of the color queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $country = Country::factory()->make([
        'id' => 1,
    ]);
    $state = State::factory()->make([
        'country_id' => $country->id,
    ]);

    $shippingZone = ShippingZone::factory()->make([
        'company_id' => $companyId,
        'country_id' => $country->id,
        'state_ids' => [$state->id],
    ])->toArray();
    unset($shippingZone['company_id']);

    $shippingZoneRecord = new ShippingZoneData(...$shippingZone);

    $shippingZoneQueries = $this->mock(ShippingZoneQueries::class, function ($mock) use (
        $shippingZoneRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($shippingZoneRecord, 1, $companyId);
    });

    $shippingZoneController = new ShippingZoneController($shippingZoneQueries);
    $redirectResponse = $shippingZoneController->update($shippingZoneRecord, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Shipping zone updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/shipping-zones', $redirectResponse->getTargetUrl());
});
