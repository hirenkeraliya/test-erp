<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\Brand\BrandQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Location\DataObjects\LocationData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationProduct;
use App\Models\AutomatedNotificationStore;
use App\Models\Brand;
use App\Models\City;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Size;
use App\Models\State;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nnjeim\World\Models\Country;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->companyB = Company::factory()->create();

    $this->region = Region::factory()->create();

    $this->locationStore = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'region_id' => $this->region->id,
        'name' => 'locationStore',
        'type_id' => LocationTypes::STORE->value,
        'code' => 'EFGH',
        'is_automatic_day_close' => true,
        'automatic_day_close_time' => '10:10:10',
    ]);

    $this->locationWarehouse = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'locationWarehouse',
        'type_id' => LocationTypes::WAREHOUSE->value,
        'code' => 'AAAA',
    ]);

    $this->locationQueries = new LocationQueries();
});

test('Locations can be searched', function (): void {
    $response = $this->locationQueries->listQuery([
        'type_id' => null,
        'search_text' => 'location',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(2, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->locationWarehouse->name)
        ->toHaveKey('code', $this->locationWarehouse->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->locationStore->name)
        ->toHaveKey('code', $this->locationStore->code);
});

test('Locations can be sorted by name', function (): void {
    $response = $this->locationQueries->listQuery([
        'type_id' => null,
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->locationStore->name)
        ->toHaveKey('code', $this->locationStore->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->locationWarehouse->name)
        ->toHaveKey('code', $this->locationWarehouse->code);
});

test('Locations are returned as per page', function (): void {
    $response = $this->locationQueries->listQuery([
        'type_id' => null,
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->locationWarehouse->name)
        ->toHaveKey('code', $this->locationWarehouse->code);
});

test('New location can be added', function (): void {
    $brand = Brand::factory()->create();

    $countriesData = [
        [
            'name' => 'Country 1',
        ],
    ];

    foreach ($countriesData as $countryData) {
        $countryId = Country::create($countryData)->id;
    }

    $location = Location::factory()->make([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyA->id,
    ])->toArray();

    DB::table('states')->insert([
        'country_id' => $countryId,
        'name' => 'state',
    ]);

    $stateId = State::first()->id;

    DB::table('cities')->insert([
        'country_id' => $countryId,
        'state_id' => $stateId,
        'name' => 'city',
        'country_code' => 'ABC',
    ]);

    $cityId = City::first()->id;

    $newLocationRecord = $location;
    unset($newLocationRecord['id']);
    unset($newLocationRecord['company_id']);
    unset($newLocationRecord['city']);

    $newLocationRecord['brand_ids'] = [$brand->id];
    $newLocationRecord['country_id'] = $countryId;
    $newLocationRecord['state_id'] = $stateId;
    $newLocationRecord['city_id'] = $cityId;
    $newLocationRecord['price_fall_down_percentage'] = 10;

    $this->locationQueries->addNew(new LocationData(...$newLocationRecord), $this->companyA->id);

    unset($newLocationRecord['brand_ids']);
    unset($newLocationRecord['location_type']);

    $this->assertDatabaseHas('locations', $newLocationRecord);
});

test('A location can be fetched', function (): void {
    $brandQueries = new BrandQueries();
    $response = $this->locationQueries->getByIdWithBrands($this->locationStore->id, $this->companyA->id, $brandQueries);

    expect($response->toArray())
        ->toHaveKey('name', $this->locationStore->name)
        ->toHaveKey('code', $this->locationStore->code);
});

test('A location can be updated', function (): void {
    $brand = Brand::factory()->create();

    $countriesData = [
        [
            'name' => 'Country 1',
        ],
    ];

    foreach ($countriesData as $countryData) {
        $countryId = Country::create($countryData)->id;
    }

    DB::table('states')->insert([
        'country_id' => $countryId,
        'name' => 'state',
    ]);

    $stateId = State::first()->id;

    DB::table('cities')->insert([
        'country_id' => $countryId,
        'state_id' => $stateId,
        'name' => 'city',
        'country_code' => 'ABC',
    ]);

    $cityId = City::first()->id;

    $location = Location::factory()->make([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyA->id,
    ])->toArray();

    $newLocationRecord = $location;
    unset($newLocationRecord['id']);
    unset($newLocationRecord['company_id']);
    unset($newLocationRecord['city']);

    $newLocationRecord['brand_ids'] = [$brand->id];
    $newLocationRecord['country_id'] = $countryId;
    $newLocationRecord['state_id'] = $stateId;
    $newLocationRecord['city_id'] = $cityId;
    $newLocationRecord['price_fall_down_percentage'] = 10;

    $this->locationQueries->update(
        new LocationData(...$newLocationRecord),
        $this->locationStore->id,
        $this->companyA->id
    );

    unset($newLocationRecord['brand_ids']);
    unset($newLocationRecord['location_type']);

    $this->assertDatabaseHas('locations', $newLocationRecord);
});

test('getLocationsExport method returns locations as expected', function (): void {
    $response = $this->locationQueries->listQuery([
        'type_id' => LocationTypes::STORE->value,
        'search_text' => 'location',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationStore->id)
        ->toHaveKey('name', $this->locationStore->name);
});

test('updateIOICityMallConfiguration method updates the stores ioi configuration', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'ioi_city_mall_machine_id' => '',
        'enable_ioi_city_mall_data_sharing' => false,
    ]);

    $this->locationQueries->updateIOICityMallConfiguration(
        [
            'ioi_city_mall_machine_id' => '123465',
            'enable_ioi_city_mall_data_sharing' => true,
        ],
        $location->id,
        $location->company_id,
    );

    $this->assertDatabaseHas(Location::class, [
        'id' => $location->id,
        'ioi_city_mall_machine_id' => '123465',
        'enable_ioi_city_mall_data_sharing' => 1,
    ]);
});

test('updateTRXMallConfiguration method updates the stores trx configuration', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'trx_mall_machine_id' => '',
        'enable_trx_mall_data_sharing' => false,
    ]);

    $this->locationQueries->updateTRXMallConfiguration(
        [
            'trx_mall_machine_id' => '123465',
            'enable_trx_mall_data_sharing' => true,
        ],
        $location->id,
        $location->company_id
    );

    $this->assertDatabaseHas(Location::class, [
        'id' => $location->id,
        'trx_mall_machine_id' => '123465',
        'enable_trx_mall_data_sharing' => 1,
    ]);
});

test('getStoreIOICityMallConfiguration method returns the stores ioi configuration', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'ioi_city_mall_machine_id' => '',
        'enable_ioi_city_mall_data_sharing' => false,
    ]);

    $response = $this->locationQueries->getStoreIOICityMallConfiguration($location->id, $location->company_id);

    expect($response->toArray())
        ->toHaveKey('ioi_city_mall_machine_id', $location->ioi_city_mall_machine_id)
        ->toHaveKey('enable_ioi_city_mall_data_sharing', $location->enable_ioi_city_mall_data_sharing);
});

test('getStoreTRXMallConfiguration method returns the stores ioi configuration', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'trx_mall_machine_id' => '',
        'enable_trx_mall_data_sharing' => false,
    ]);

    $response = $this->locationQueries->getStoreTRXMallConfiguration($location->id, $location->company_id);

    expect($response->toArray())
        ->toHaveKey('trx_mall_machine_id', $location->trx_mall_machine_id)
        ->toHaveKey('enable_trx_mall_data_sharing', $location->enable_trx_mall_data_sharing);
});

test('getLocationTypeStoreById method returns location by id with type is store', function (): void {
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);

    $response = $this->locationQueries->getLocationTypeStoreById($location->id);

    expect($response->toArray())
        ->toHaveKey('id', $location->id);
});

test('getStoreWithBasicColumns method returns basic columns by companyId', function (): void {
    $response = $this->locationQueries->getStoreWithBasicColumns($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationStore->id);
});

test('doAllStoresExist method returns check all record exists', function (): void {
    $response = $this->locationQueries->doAllStoresExist($this->companyA->id, [$this->locationStore->id]);

    expect($response)->toBe(true);
});

test('getLocationByCountersCounterUpdateId method returns the location details', function (): void {
    $counter = Counter::factory()->create([]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $counter->counter_update_id = $counterUpdate->id;
    $counter->save();

    $response = $this->locationQueries->getLocationByCountersCounterUpdateId($counterUpdate->id);

    $this->assertEquals($counter->location_id, $response->id);
});

test('doStoreNamesExists method check stores exact match with db records', function (): void {
    $storeNames = [$this->locationStore->name];

    $response = $this->locationQueries->doStoreNamesExists($storeNames, $this->companyA->id);

    $this->assertEquals($response, true);
});

test('getIdAndNameByNames method return records by name and company id', function (): void {
    $storeNames = [$this->locationStore->name];

    $response = $this->locationQueries->getIdAndNameByNames($storeNames, $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationStore->id)
        ->toHaveKey('name', $this->locationStore->name);
});

test('getById method return records by company id', function (): void {
    $response = $this->locationQueries->getById(
        $this->locationStore->id,
        $this->companyA->id,
        LocationTypes::STORE->value,
        ['id', 'name', 'code'],
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationStore->id)
        ->toHaveKey('name', $this->locationStore->name)
        ->toHaveKey('code', $this->locationStore->code);
});

test('getByIdsWithNameAndCode method return records of location type store', function (): void {
    $response = $this->locationQueries->getByIdsWithNameAndCode($this->companyA->id, [$this->locationStore->id]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationStore->id)
        ->toHaveKey('name', $this->locationStore->name)
        ->toHaveKey('code', $this->locationStore->code);
});

test('getWarehouseWithBasicColumns method return records of location type warehouse', function (): void {
    $response = $this->locationQueries->getWarehouseWithBasicColumns(
        $this->companyA->id,
        [$this->locationWarehouse->id],
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationWarehouse->id)
        ->toHaveKey('name', $this->locationWarehouse->name);
});

test('getYesterdayCreatedLocationIds return array', function (): void {
    $yesterdayDate = Carbon::yesterday()->format('Y-m-d H:i:s');

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'test',
        'share_inventory_to_external_companies' => true,
        'created_at' => $yesterdayDate,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $response = $this->locationQueries->getYesterdayCreatedLocationsIds(LocationTypes::STORE->value);

    expect(current($response))
        ->toBe($location->id);
});

test('getAllLocationsIds return all the location ids in array', function (): void {
    $response = $this->locationQueries->getAllLocationsIds(LocationTypes::STORE->value);

    expect(current($response))
        ->toBe($this->locationStore->getKey());
});

test('getCompanyIdOfStore return all the company id of location', function (): void {
    $response = $this->locationQueries->getCompanyIdOfStore($this->locationStore->id);

    expect($response)
        ->toBe($this->locationStore->company_id);
});

test('findByIdWithReceiptFooterDisclaimerAndCreatedAt return all the company id of location', function (): void {
    $response = $this->locationQueries->findByIdWithReceiptFooterDisclaimerAndCreatedAt($this->locationStore->id);

    expect($response)
        ->toHaveKey('receipt_footer', $this->locationStore->receipt_footer)
        ->toHaveKey('disclaimer', $this->locationStore->disclaimer);
});

test('getByIdWithReceiptFooterDisclaimerAndCreatedAt return all the company id of location', function (): void {
    $response = $this->locationQueries->getByIdWithReceiptFooterDisclaimerAndCreatedAt($this->locationStore->id);

    expect($response)
        ->toHaveKey('receipt_footer', $this->locationStore->receipt_footer)
        ->toHaveKey('disclaimer', $this->locationStore->disclaimer);
});

test('existsByName method return the locations name and code by name', function (): void {
    $response = $this->locationQueries->existsByName((string) $this->locationStore->name, $this->companyA->id);
    $this->assertTrue($response);

    $response = $this->locationQueries->existsByName('abc', $this->companyA->id);
    $this->assertFalse($response);
});

test('existsByPhone method return the locations name and code by phone', function (): void {
    $response = $this->locationQueries->existsByPhone($this->locationStore->phone, $this->companyA->id);
    $this->assertTrue($response);

    $response = $this->locationQueries->existsByPhone('454545', $this->companyA->id);
    $this->assertFalse($response);
});

test('getByCodesAndCompanyId method return the locations ids array', function (): void {
    $response = $this->locationQueries->getByCodesAndCompanyId(
        [$this->locationStore->code],
        $this->companyA->id,
        LocationTypes::STORE->value
    );

    expect($response->first())
        ->toHaveKey('id', $this->locationStore->id)
        ->toHaveKey('name', $this->locationStore->name)
        ->toHaveKey('code', $this->locationStore->code);
});

test('getLocationsOfRegions method call and returns the locations by region id', function (): void {
    $response = $this->locationQueries->getLocationsOfRegions(
        $this->locationStore->region_id,
        $this->companyA->id,
        LocationTypes::STORE->value
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->locationStore->id)
        ->toHaveKey('name', $this->locationStore->name);
});

test('A locations can be fetched by names', function (): void {
    $response = $this->locationQueries->getLocationsOfLocationsName(
        [$this->locationStore->name],
        $this->companyA->id,
        LocationTypes::STORE->value
    );
    expect($response->first()->toArray())
        ->toHaveKey('name', $this->locationStore->name);
});

test(
    'it returns the sales summary for a store within a specific color and date',
    function (): void {
        $colorId = Color::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'color_id' => $colorId,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $this->locationStore->id,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'id' => $colorId,
            'type' => StoreRevenueDashboardTableFilterTypes::COLORS->value,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->locationQueries->getLocationSalesSummary($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->locationStore->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a store within a specific brand and date',
    function (): void {
        $brandId = Brand::factory()->create([
            'name' => 'test',
        ])->id;

        $this->companyA->brands()->attach($brandId);

        $product = Product::factory()->create([
            'brand_id' => $brandId,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $this->locationStore->id,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'id' => $brandId,
            'type' => StoreRevenueDashboardTableFilterTypes::BRANDS->value,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->locationQueries->getLocationSalesSummary($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->locationStore->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a store within a specific department and date',
    function (): void {
        $departmentId = Department::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'department_id' => $departmentId,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $this->locationStore->id,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'id' => $departmentId,
            'type' => StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->locationQueries->getLocationSalesSummary($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->locationStore->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a store within a specific color group and date',
    function (): void {
        $colorGroupId = ColorGroup::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'test',
        ])->id;

        $colorId = Color::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'test',
            'group_id' => $colorGroupId,
        ])->id;

        $product = Product::factory()->create([
            'color_id' => $colorId,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $this->locationStore->id,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'id' => $colorGroupId,
            'type' => StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->locationQueries->getLocationSalesSummary($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->locationStore->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a store within a specific size and date',
    function (): void {
        $sizeId = Size::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'size_id' => $sizeId,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $this->locationStore->id,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'id' => $sizeId,
            'type' => StoreRevenueDashboardTableFilterTypes::SIZES->value,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->locationQueries->getLocationSalesSummary($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->locationStore->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a store within a specific style and date',
    function (): void {
        $styleId = Style::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'style_id' => $styleId,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $this->locationStore->id,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'id' => $styleId,
            'type' => StoreRevenueDashboardTableFilterTypes::STYLES->value,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->locationQueries->getLocationSalesSummary($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->locationStore->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test('it call the getCompanyOfStore method return company', function (): void {
    $response = $this->locationQueries->getCompanyOfStore($this->locationStore->id);
    expect($response->toArray())
        ->toHaveKey('company.enable_e_invoice', true);
});

test('A location can be updated by phone', function (): void {
    $brand = Brand::factory()->create();
    $this->locationQueries->updateByPhone(
        [
            'name' => 'test',
            'code' => '123456',
            'brand_ids' => [$brand->id],
        ],
        $this->locationStore->phone,
        $this->locationStore->type_id,
        $this->companyA->id,
    );

    $this->assertDatabaseHas('locations', [
        'name' => 'test',
        'code' => '123456',
    ]);
});

test('isLocationNameTakenByAnother method return proper response', function (): void {
    $response = $this->locationQueries->isLocationNameTakenByAnother(
        (string) $this->locationStore->name,
        (string) $this->locationStore->phone,
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertFalse($response);

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => $this->locationStore->type_id,
    ]);

    $response = $this->locationQueries->isLocationNameTakenByAnother(
        (string) $location->name,
        (string) $this->locationStore->phone,
        $location->type_id,
        $this->companyA->id
    );

    $this->assertTrue($response);
});

test('isLocationCodeTakenByAnother method return proper response', function (): void {
    $response = $this->locationQueries->isLocationCodeTakenByAnother(
        (string) $this->locationStore->code,
        (string) $this->locationStore->phone,
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertFalse($response);

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => $this->locationStore->type_id,
    ]);

    $response = $this->locationQueries->isLocationCodeTakenByAnother(
        (string) $location->code,
        (string) $this->locationStore->phone,
        $location->type_id,
        $this->companyA->id
    );

    $this->assertTrue($response);
});

test('existsByNameAndTypeId method return proper response', function (): void {
    $response = $this->locationQueries->existsByNameAndTypeId(
        $this->locationStore->name,
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertTrue($response);

    $response = $this->locationQueries->existsByNameAndTypeId(
        'test',
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertFalse($response);
});

test('existsByCodeAndTypeId method return proper response', function (): void {
    $response = $this->locationQueries->existsByCodeAndTypeId(
        $this->locationStore->code,
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertTrue($response);

    $response = $this->locationQueries->existsByCodeAndTypeId(
        '1234',
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertFalse($response);
});

test('existsByPhoneAndTypeId method return proper response', function (): void {
    $response = $this->locationQueries->existsByPhoneAndTypeId(
        $this->locationStore->phone,
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertTrue($response);

    $response = $this->locationQueries->existsByPhoneAndTypeId(
        '1234567890',
        $this->locationStore->type_id,
        $this->companyA->id
    );
    $this->assertFalse($response);
});

test(
    'It call getInventoryForLowStockNotificationProduct method return collection by product low stock',
    function (): void {
        $company = Company::factory()->create([
            'name' => 'test',
        ]);

        $location = Location::factory()->create([
            'name' => 'test',
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'name' => 'test',
            'company_id' => $company->id,
            'is_non_inventory' => false,
            'status' => Statuses::ACTIVE->value,
        ]);

        Inventory::factory()->create([
            'stock' => 5,
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        $automatedNotification = AutomatedNotification::factory()->create([
            'company_id' => $company->id,
            'low_stock_alert_threshold' => 0,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value,
        ]);

        AutomatedNotificationProduct::factory()->create([
            'automated_notification_id' => $automatedNotification->id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'low_stock_alert_threshold' => 10,
        ]);

        $response = $this->locationQueries->getInventoryForLowStockNotificationProduct($automatedNotification, []);
        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $location->name)
            ->toHaveKeys(['inventories', 'inventories.0.product', 'store_managers']);
    }
);

test(
    'It call getInventoryForLowStockNotificationLocation method return collection by location low stock',
    function (): void {
        $company = Company::factory()->create([
            'name' => 'test',
        ]);

        $location = Location::factory()->create([
            'name' => 'test',
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'name' => 'test',
            'company_id' => $company->id,
            'is_non_inventory' => false,
            'status' => Statuses::ACTIVE->value,
        ]);

        Inventory::factory()->create([
            'stock' => 5,
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        $automatedNotification = AutomatedNotification::factory()->create([
            'company_id' => $company->id,
            'low_stock_alert_threshold' => 0,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_LOCATION->value,
        ]);

        AutomatedNotificationStore::factory()->create([
            'automated_notification_id' => $automatedNotification->id,
            'location_id' => $location->id,
            'low_stock_alert_threshold' => 10,
        ]);

        $response = $this->locationQueries->getInventoryForLowStockNotificationLocation($automatedNotification, []);
        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $location->name)
            ->toHaveKeys(['inventories', 'inventories.0.product', 'store_managers']);
    }
);

test(
    'It call getInventoryForLowStockNotificationCompany method return collection by company low stock',
    function (): void {
        $company = Company::factory()->create([
            'name' => 'test',
        ]);

        $location = Location::factory()->create([
            'name' => 'test',
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'name' => 'test',
            'company_id' => $company->id,
            'is_non_inventory' => false,
            'status' => Statuses::ACTIVE->value,
        ]);

        Inventory::factory()->create([
            'stock' => 5,
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        $automatedNotification = AutomatedNotification::factory()->create([
            'company_id' => $company->id,
            'low_stock_alert_threshold' => 10,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        ]);

        $response = $this->locationQueries->getInventoryForLowStockNotificationCompany($automatedNotification, []);
        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $location->name)
            ->toHaveKeys(['inventories', 'inventories.0.product', 'store_managers']);
    }
);

test('Get Location name for export PDF headers', function (): void {
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $location = Location::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $response = $this->locationQueries->getLocationForFilter([$location->id]);

    $this->assertIsString($response);
});

test('Get topTenSellingLocation data', function (): void {
    $date = Carbon::now();
    $dateRange = [
        $date->startOfYear()->subYear()->format('Y-m-d H:i:s'),
        $date->endOfYear()->subYear()->format('Y-m-d H:i:s'),
    ];

    $targetId = 0;
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $response = $this->locationQueries->topTenSellingLocation($dateRange, $targetId, $company->id, []);

    expect($response)->toBeCollection();
});

test('Get worstTenSellingLocation data', function (): void {
    $date = Carbon::now();
    $dateRange = [
        $date->startOfYear()->subYear()->format('Y-m-d H:i:s'),
        $date->endOfYear()->subYear()->format('Y-m-d H:i:s'),
    ];

    $targetId = 0;
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $response = $this->locationQueries->worstTenSellingLocation($dateRange, $targetId, $company->id, []);

    expect($response)->toBeCollection();
});

test('Get locations using getByIds', function (): void {
    $locationA = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'region_id' => $this->region->id,
        'type_id' => LocationTypes::STORE->value,
        'is_automatic_day_close' => true,
        'automatic_day_close_time' => '10:10:10',
    ]);

    $locationB = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'region_id' => $this->region->id,
        'type_id' => LocationTypes::STORE->value,
        'is_automatic_day_close' => true,
        'automatic_day_close_time' => '10:10:10',
    ]);

    $locationIds = [$locationA->id, $locationB->id];

    $response = $this->locationQueries->getByIds($locationIds, LocationTypes::STORE->value);

    expect($response->first()->toArray())
    ->toHaveKeys(['id', 'name', 'code']);
});

test('Get getNameByIds data', function (): void {
    $company = Company::factory()->create([
        'name' => 'test',
    ]);

    $location = Location::factory()->create([
        'name' => 'test',
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $response = $this->locationQueries->getNameByIds([$location->id]);

    expect($response)
    ->toBe($location->name);
});
