<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\DataObjects\LocationData;
use App\Domains\Location\DataObjects\LocationListData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\LocationController;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->companyId = 1;
    $this->company = Company::factory()->make([
        'id' => $this->companyId,
        'default_country_id' => 1,
    ]);

    setCompanyIdInSession();

    $this->location = Location::factory()->make([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $this->companyId,
    ]);
});

test('It calls the List query method of the location queries class and returns proper response', function (): void {
    $requestParameter = [
        'type_id' => null,
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $locationListData = new LocationListData(...$requestParameter);

    $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $this->companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $locationController = new LocationController($locationQueries);

    $response = $locationController->fetchLocations($locationListData);

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(new Collection([]), $response['data']->resource);
});

test('It calls the addNew method of the location queries class and returns proper response', function (): void {
    $this->company->brands = collect([
        [
            'brand_id' => 1,
        ],
    ]);

    $locationRecord = $this->location->toArray();
    unset($locationRecord['id']);
    unset($locationRecord['company_id']);
    $locationRecord['brand_ids'] = [1];
    $locationRecord['country_id'] = 1;
    $locationRecord['city_id'] = 1;
    $locationRecord['state_id'] = 1;
    $locationRecord['price_fall_down_percentage'] = 10;

    $locationData = new LocationData(...$locationRecord);

    $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($locationData, $this->companyId);
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with($this->companyId, [1])
            ->andReturn(true);
    });

    $locationController = new LocationController($locationQueries);
    $redirectResponse = $locationController->store($locationData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The location added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/locations', $redirectResponse->getTargetUrl());
});

test('It calls the addNew method throw exception if brand_id does not match the company_id', function (): void {
    $locationRecord = $this->location->toArray();
    unset($locationRecord['id']);
    unset($locationRecord['company_id']);

    $locationRecord['brand_ids'] = [1];
    $locationRecord['country_id'] = 1;
    $locationRecord['city_id'] = 1;
    $locationRecord['state_id'] = 1;
    $locationRecord['price_fall_down_percentage'] = 10;

    $locationData = new LocationData(...$locationRecord);

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with($this->companyId, [1])
            ->andReturn(false);
    });

    $locationController = new LocationController(new LocationQueries());
    $locationController->store($locationData);
})->throws(RedirectWithErrorException::class);

test('It calls the get by id method of the location queries class and returns proper response', function (): void {
    $locationRecord = $this->location->toArray();
    $brand = Brand::factory()->make();

    $brand->company = collect([
        [
            'id' => $this->companyId,
        ],
    ]);

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getCountries')
            ->once()
            ->with($this->companyId)
            ->andReturn(new Company());
    });

    $brandQueries = $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyBrands')
            ->once()
            ->with($this->companyId)
            ->andReturn(new EloquentCollection([]));
    });

    $this->mock(RegionQueries::class, function ($mock): void {
        $mock->shouldReceive('getRegionByCompanyId')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect([]));
    });

    $locationQueries = $this->mock(LocationQueries::class, function ($mock) use (
        $locationRecord,
        $brandQueries
    ): void {
        $mock->shouldReceive('getByIdWithBrands')
            ->once()
            ->with(1, $this->companyId, $brandQueries)
            ->andReturn(new Location($locationRecord));
    });

    $locationController = new LocationController($locationQueries);
    $response = $locationController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'location',
                fn (Assert $employee): Assert => $employee
                    ->where('company_id', $locationRecord['company_id'])
                    ->where('name', $locationRecord['name'])
                    ->where('code', $locationRecord['code'])
                    ->where('email', $locationRecord['email'])
                    ->where('credit_note_expiration_days', $locationRecord['credit_note_expiration_days'])
                    ->where('credit_note_expiration_days', $locationRecord['credit_note_expiration_days'])
                    ->etc()
            )
    );
});

test('It calls the update method of the store queries class and returns proper response', function (): void {
    $locationRecord = $this->location->toArray();

    unset($locationRecord['id']);
    unset($locationRecord['company_id']);

    $locationRecord['brand_ids'] = [1];
    $locationRecord['country_id'] = 1;
    $locationRecord['city_id'] = 1;
    $locationRecord['state_id'] = 1;
    $locationRecord['price_fall_down_percentage'] = 10;

    $locationData = new LocationData(...$locationRecord);

    $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($locationData, 1, $this->companyId);
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('hasAllBrandsAttached')
            ->once()
            ->with($this->companyId, [1])
            ->andReturn(true);
    });

    $locationController = new LocationController($locationQueries);
    $redirectResponse = $locationController->update($locationData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The Location updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/locations', $redirectResponse->getTargetUrl());
});

test('the generateQrCode method and returns the QrCode', function (): void {
    $locationRecord = $this->location;
    $locationRecord->id = 1;
    $locationRecord->uuid = 'ABCDEF';

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationTypeStoreById')
            ->once()
            ->andReturn($this->location);
    });

    $locationController = new LocationController(new LocationQueries());
    $response = $locationController->generateQrCode($locationRecord->id);

    expect($response)->toBeInstanceOf(HtmlString::class);
});

test('the generateQrCode method throw error when selected location is not store', function (): void {
    $locationRecord = $this->location;
    $locationRecord->id = 1;

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationTypeStoreById')
            ->once()
            ->andReturn(null);
    });

    $locationController = new LocationController(new LocationQueries());
    $locationController->generateQrCode($locationRecord->id);
})->throws(HttpException::class, 'The selected location is not a store.');

test('It calls the exportStores method and returns a proper response', function (): void {
    $requestParameter = [
        'type_id' => null,
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $locationListData = new LocationListData(...$requestParameter);

    $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getLocationsExport')
            ->once()
            ->with($requestParameter, $this->companyId)
            ->andReturn(collect(new Location()));
    });

    $locationController = new LocationController($locationQueries);

    $response = $locationController->exportLocations('filename.csv', $locationListData);

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the getStoreTRXMallConfiguration queries method of store queries and get stores list',
    function (): void {
        $locationRecord = $this->location;
        $locationRecord->id = 1;

        $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($locationRecord): void {
            $mock->shouldReceive('getLocationTypeStoreById')
                ->once()
                ->andReturn($this->location);

            $mock->shouldReceive('getStoreTRXMallConfiguration')
                ->once()
                ->andReturn($locationRecord);
        });

        $locationController = new LocationController($locationQueries);
        $response = $locationController->fetchStoreTRXMallConfiguration($locationRecord->id);
        $this->assertEquals($locationRecord, $response['locationTRXConfiguration']);
    }
);

test(
    'It calls the getStoreTRXMallConfiguration method throw exception when location type is not store',
    function (): void {
        $locationRecord = $this->location;
        $locationRecord->id = 1;

        $locationQueries = $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getLocationTypeStoreById')
                ->once()
                ->andReturn(null);
        });

        $locationController = new LocationController($locationQueries);
        $locationController->fetchStoreTRXMallConfiguration($locationRecord->id);
    }
)->throws(HttpException::class, 'The selected location is not a store.');

test(
    'It calls the getStoreIOICityMallConfiguration queries method of store queries and get stores list',
    function (): void {
        $locationRecord = $this->location;
        $locationRecord->id = 1;

        $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($locationRecord): void {
            $mock->shouldReceive('getLocationTypeStoreById')
                ->once()
                ->andReturn($this->location);

            $mock->shouldReceive('getStoreIOICityMallConfiguration')
                ->once()
                ->andReturn($locationRecord);
        });

        $locationController = new LocationController($locationQueries);
        $response = $locationController->fetchStoreIOICityMallConfiguration($locationRecord->id);
        $this->assertEquals($locationRecord, $response['locationIOIConfiguration']);
    }
);

test(
    'It calls the getStoreIOICityMallConfiguration method throw exception when location type is not store',
    function (): void {
        $locationRecord = $this->location;
        $locationRecord->id = 1;

        $locationQueries = $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getLocationTypeStoreById')
                ->once()
                ->andReturn(null);
        });

        $locationController = new LocationController($locationQueries);
        $response = $locationController->fetchStoreIOICityMallConfiguration($locationRecord->id);
        $this->assertEquals($locationRecord, $response['locationIOIConfiguration']);
    }
)->throws(HttpException::class, 'The selected location is not a store.');
