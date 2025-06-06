<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DreamPrice\DataObjects\DreamPriceData;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\Product;
use App\Models\SaleChannel;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->dreamPriceA = DreamPrice::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'ABC',
        'status' => true,
        'end_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $this->dreamPriceB = DreamPrice::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'XYZ',
        'status' => true,
    ]);

    $this->dreamPriceQueries = new DreamPriceQueries();
});

test('dream prices can be searched', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $admin = Admin::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $admin->id,
        'created_by_type' => ModelMapping::class::ADMIN->name,
        'module_id' => $this->dreamPriceA->id,
        'module_type' => ModelMapping::class::DREAM_PRICE->name,
    ]);

    $response = $this->dreamPriceQueries->listQuery([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->dreamPriceA->name)
        ->toHaveKeys(['import_record', 'import_record.media']);
});

test('A dream price can be added', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $admin = Admin::factory()->create();

    $requestParameter = [
        'name' => 'XYZW',
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'is_available_in_ecommerce' => true,
        'is_available_in_pos' => false,
        'member_group_ids' => null,
        'employee_group_ids' => null,
    ];

    $requestParameter['location_ids'] = [$location->id];

    $this->dreamPriceQueries->addNew(new DreamPriceData(...$requestParameter), $this->company->id, $admin);

    $this->assertDatabaseHas('dream_prices', [
        'name' => 'XYZW',
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    $this->assertDatabaseHas('dream_price_location', [
        'location_id' => $location->id,
    ]);
});

test('A dream price can be fetched with locations', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->dreamPriceA->locations()->sync($location->id);

    $response = $this->dreamPriceQueries->getByIdWithLocations($this->dreamPriceA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->dreamPriceA->name)
        ->toHaveKey('company_id', $this->dreamPriceA->company_id)
        ->toHaveKey('locations.0.id', $location->id);
});

test('A dream price can be fetched', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->dreamPriceA->locations()->sync($location->id);

    $response = $this->dreamPriceQueries->getById($this->dreamPriceA->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->dreamPriceA->name);
});

test('A dream price can be updated', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $requestParameter = [
        'name' => 'ABCD',
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'is_available_in_ecommerce' => false,
        'is_available_in_pos' => true,
        'member_group_ids' => null,
        'employee_group_ids' => null,
    ];

    $requestParameter['location_ids'] = [$location->id];

    $this->dreamPriceQueries->update(
        new DreamPriceData(...$requestParameter),
        $this->dreamPriceA->id,
        $this->company->id
    );

    $this->assertDatabaseHas('dream_prices', [
        'name' => 'ABCD',
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
    ]);

    $this->assertDatabaseHas('dream_price_location', [
        'location_id' => $location->id,
        'dream_price_id' => $this->dreamPriceA->id,
    ]);
});

test(
    'getListWithProducts method returns the dream prices list with products',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $dreamPriceProduct = DreamPriceProduct::factory()->create([
            'dream_price_id' => $this->dreamPriceA->id,
            'product_id' => $product->id,
            'price' => 99,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->dreamPriceA->locations()->sync($location->id);

        $response = $this->dreamPriceQueries->getListWithProducts($this->company->id, $location->id);

        $dreamPriceList = $response->first()->toArray();
        $dreamPriceProduct = $dreamPriceList['dream_price_products'][0];

        expect($dreamPriceList)
            ->toHaveKey('id', $this->dreamPriceA->id)
            ->toHaveKey('name', $this->dreamPriceA->name)
            ->toHaveKey('start_date', $this->dreamPriceA->start_date)
            ->toHaveKey('end_date', $this->dreamPriceA->end_date);

        expect($dreamPriceProduct)
            ->toHaveKey('product_id', $product->id)
            ->toHaveKey('dream_price_id', $this->dreamPriceA->id)
            ->toHaveKey('price', $dreamPriceProduct['price'])
            ->toHaveKey('product');
    }
);

test(
    'getByIdsWithProductsAndStores method returns the dream price with products',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->dreamPriceA->locations()->sync($location->id);

        $dreamPriceProduct = DreamPriceProduct::factory()->create([
            'dream_price_id' => $this->dreamPriceA->id,
            'product_id' => $product->id,
            'price' => 99,
        ]);

        $response = $this->dreamPriceQueries->getByIdsWithProductsAndLocations(
            [$this->dreamPriceA->id],
            $this->company->id
        );

        $dreamPrice = $response->first()->toArray();
        $dreamPriceProduct = $dreamPrice['dream_price_products'][0];

        expect($dreamPrice)
            ->toHaveKey('id', $this->dreamPriceA->id)
            ->toHaveKey('name', $this->dreamPriceA->name)
            ->toHaveKey('start_date', $this->dreamPriceA->start_date)
            ->toHaveKey('end_date', $this->dreamPriceA->end_date)
            ->toHaveKey('locations.0.id', $location->id)
            ->toHaveKey('locations.0.name', $location->name);

        expect($dreamPriceProduct)
            ->toHaveKey('product_id', $product->id)
            ->toHaveKey('dream_price_id', $this->dreamPriceA->id)
            ->toHaveKey('price', $dreamPriceProduct['price']);
    }
);

test('getDreamPricesExport method returns dream prices as expected', function (): void {
    $response = $this->dreamPriceQueries->getDreamPricesExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->dreamPriceA->name);
});

test('getDreamPricesForApplication method returns paginated results as expected', function (): void {
    $dreamPrice = DreamPrice::factory()->create([
        'company_id' => $this->company->id,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
    ]);

    $response = $this->dreamPriceQueries->getDreamPricesForApplication([
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'location_id' => null,
        'dream_price_ids' => null,
        'per_page' => 1,
        'selected_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
    ], $this->company->id);
    expect($response->toArray()['data'][0])
        ->toHaveKey('name', $dreamPrice->name);
});

test('updateStatus method update the status.', function (): void {
    $this->dreamPriceQueries->updateStatus($this->dreamPriceA->id, $this->dreamPriceA->company_id, false);

    $this->assertDatabaseHas('dream_prices', [
        'id' => $this->dreamPriceA->id,
        'company_id' => $this->dreamPriceA->company_id,
        'status' => false,
    ]);
});

test(
    'getListWithProductsInEcommerce method returns the dream prices list with products',
    function (): void {
        $filterData = [
            'search_text' => 'ABC',
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'per_page' => 10,
            'after_updated_at' => null,
        ];

        $dreamPrice = DreamPrice::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'ABCDEF',
            'status' => true,
            'is_available_in_pos' => false,
            'is_available_in_ecommerce' => true,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $dreamPrice->locations()->sync($location->id);

        $response = $this->dreamPriceQueries->getListWithProductsInEcommerce(
            $this->company->id,
            $location->id,
            $filterData
        );

        $dreamPriceList = $response->first()->toArray();

        expect($dreamPriceList)
            ->toHaveKey('id', $dreamPrice->id)
            ->toHaveKey('name', $dreamPrice->name)
            ->toHaveKey('start_date', $dreamPrice->start_date)
            ->toHaveKey('end_date', $dreamPrice->end_date);
    }
);

test('getAllActiveDreamPrice method gives active dream prices.', function (): void {
    $this->dreamPriceA->end_date = now()->tomorrow();
    $this->dreamPriceA->save();
    $this->dreamPriceB->end_date = now()->tomorrow();
    $this->dreamPriceB->save();

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->dreamPriceA->locations()->sync($location->id);
    $this->dreamPriceB->locations()->sync($location->id);

    DreamPriceProduct::factory(3)->create([
        'dream_price_id' => $this->dreamPriceA->id,
        'product_id' => $product->id,
        'price' => 99,
    ]);

    DreamPriceProduct::factory(3)->create([
        'dream_price_id' => $this->dreamPriceB->id,
        'product_id' => $product->id,
        'price' => 99,
    ]);

    $response = $this->dreamPriceQueries->getAllActiveDreamPrice();

    expect($response->first())
        ->toHaveKey('dream_price_id_1', $this->dreamPriceA->id)
        ->toHaveKey('dream_price_id_2', $this->dreamPriceB->id)
        ->toHaveKey('dream_price_name_1', $this->dreamPriceA->name)
        ->toHaveKey('dream_price_name_2', $this->dreamPriceB->name)
        ->toHaveKey('dream_price_company_id_1', $this->dreamPriceA->company_id)
        ->toHaveKey('dream_price_company_id_2', $this->dreamPriceB->company_id);
});

test(
    'validateLocationAndSaleChannelMatch method validates location and sale channel matches between dream price and sale channel',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $saleChannel = SaleChannel::factory()->create([
            'company_id' => $this->company->id,
            'default_location_id' => $location->id,
        ]);

        $this->dreamPriceA->locations()->sync($location->id);
        $this->dreamPriceA->saleChannels()->sync($saleChannel->id);

        expect(
            $this->dreamPriceQueries->validateLocationAndSaleChannelMatch($this->dreamPriceA, $saleChannel)
        )->toBeTrue();

        $differentLocation = Location::factory()->create([
            'company_id' => $this->company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->dreamPriceA->locations()->sync($differentLocation->id);

        expect(
            $this->dreamPriceQueries->validateLocationAndSaleChannelMatch($this->dreamPriceA, $saleChannel)
        )->toBeFalse();
    }
);

test('getDreamPriceByIdForEcommerce method returns dream price with basic fields', function (): void {
    $dreamPrice = DreamPrice::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $response = $this->dreamPriceQueries->getDreamPriceByIdForEcommerce($dreamPrice->id);

    expect($response->toArray())
        ->toHaveKey('id', $dreamPrice->id)
        ->toHaveKey('company_id', $dreamPrice->company_id);
});
