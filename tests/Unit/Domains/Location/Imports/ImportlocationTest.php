<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\City\CityQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Country\CountryQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationImportColumns;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\Imports\ImportLocation;
use App\Domains\Location\LocationQueries;
use App\Domains\State\StateQueries;
use App\Models\Brand;
use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Collection;

test('validate method returns blank array', function (): void {
    $companyId = 1;
    $locationData = getLocationData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'name' => 'test',
    ]);

    $locationType = LocationTypes::STORE->value;

    $this->mock(LocationQueries::class, function ($mock) use ($locationData, $companyId, $locationType): void {
        $mock->shouldReceive('existsByCodeAndTypeId')
            ->once()
            ->with($locationData['code'], $locationType, $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByNameAndTypeId')
            ->once()
            ->with($locationData['name'], $locationType, $companyId)
            ->andReturn(false);
        $mock->shouldReceive('existsByPhoneAndTypeId')
            ->once()
            ->with($locationData['phone'], $locationType, $companyId)
            ->andReturn(false);
    });

    $this->mock(CountryQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($locationData['country'])
            ->andReturn(true);
    });

    $this->mock(StateQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('existsByName')
            ->times()
            ->with($locationData['state'])
            ->andReturn(true);
    });

    $this->mock(CityQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('existsByName')
            ->times()
            ->with($locationData['city'])
            ->andReturn(true);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('existsByNames')
            ->once()
            ->andReturn(new Collection([$brand]));
    });

    $importLocation = new ImportLocation();
    $redirectResponse = $importLocation->validate($locationData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns issues list of the given data', function (): void {
    $companyId = 1;
    $locationData = getLocationData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $brand = Brand::factory()->make();

    $locationType = LocationTypes::STORE->value;

    $this->mock(LocationQueries::class, function ($mock) use ($locationData, $companyId, $locationType): void {
        $mock->shouldReceive('existsByCodeAndTypeId')
            ->once()
            ->with($locationData['code'], $locationType, $companyId)
            ->andReturn(true);
        $mock->shouldReceive('existsByNameAndTypeId')
            ->once()
            ->with($locationData['name'], $locationType, $companyId)
            ->andReturn(true);
        $mock->shouldReceive('existsByPhoneAndTypeId')
            ->once()
            ->with($locationData['phone'], $locationType, $companyId)
            ->andReturn(true);
    });

    $this->mock(CountryQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($locationData['country'])
            ->andReturn(false);
    });

    $this->mock(StateQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($locationData['state'])
            ->andReturn(false);
    });

    $this->mock(CityQueries::class, function ($mock) use ($locationData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($locationData['city'])
            ->andReturn(false);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('existsByNames')
            ->once()
            ->andReturn(new Collection($brand));
    });

    $importLocation = new ImportLocation();
    $redirectResponse = $importLocation->validate($locationData, $importRecord);
    $this->assertEquals(7, count($redirectResponse));
});

test('name, brand, code and phone are require while import record', function (): void {
    $companyId = 1;
    $locationData = [
        'type' => 'Store',
        'name' => '',
        'code' => '',
        'registration_number' => '',
        'sst_number' => '',
        'email' => '',
        'phone' => '',
        'mobile' => '',
        'fax' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'area_code' => '',
        'website' => '',
        'sales_tax_percentage' => '',
        'sales_return_days_limit' => '',
        'credit_note_expiration_days' => '',
        'loyalty_point_expiration_days' => '',
        'receipt_footer' => '',
        'disclaimer' => '',
        'brands' => '',
        'price_fall_down_percentage' => '',
        'country' => '',
        'state' => '',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $importLocation = new ImportLocation();
    $redirectResponse = $importLocation->validate($locationData, $importRecord);
    $this->assertEquals(16, count($redirectResponse));
});

test('save method saves the data', function (): void {
    $companyId = 1;

    $locationData = getLocationData();

    $importRecord = getImportRecordsForLocation($companyId);

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdsByNames')
            ->times(1)
            ->andReturn([1]);
    });

    $this->mock(CountryQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByName')
            ->times(1)
            ->andReturn(1);
    });

    $this->mock(StateQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByName')
            ->times(1)
            ->andReturn(1);
    });

    $this->mock(CityQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByName')
            ->times(1)
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importLocation = new ImportLocation();
    $importLocation->save($locationData, $importRecord);
});

test(
    'validate method returns issues if price fall down percentage is less than 0 or greater than 100',
    function (): void {
        $companyId = 1;
        $locationData = getLocationData();
        $locationData['price_fall_down_percentage'] = 120;

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $brand = Brand::factory()->make([
            'name' => 'test',
        ]);

        $locationType = LocationTypes::STORE->value;

        $this->mock(LocationQueries::class, function ($mock) use ($locationData, $companyId, $locationType): void {
            $mock->shouldReceive('existsByCodeAndTypeId')
                ->once()
                ->with($locationData['code'], $locationType, $companyId)
                ->andReturn(false);
            $mock->shouldReceive('existsByNameAndTypeId')
                ->once()
                ->with($locationData['name'], $locationType, $companyId)
                ->andReturn(false);
            $mock->shouldReceive('existsByPhoneAndTypeId')
                ->once()
                ->with($locationData['phone'], $locationType, $companyId)
                ->andReturn(false);
        });

        $this->mock(CountryQueries::class, function ($mock) use ($locationData): void {
            $mock->shouldReceive('existsByName')
                ->once()
                ->with($locationData['country'])
                ->andReturn(false);
        });

        $this->mock(StateQueries::class, function ($mock) use ($locationData): void {
            $mock->shouldReceive('existsByName')
                ->times()
                ->with($locationData['state'])
                ->andReturn(false);
        });

        $this->mock(CityQueries::class, function ($mock) use ($locationData): void {
            $mock->shouldReceive('existsByName')
                ->times()
                ->with($locationData['city'])
                ->andReturn(false);
        });

        $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
            $mock->shouldReceive('existsByNames')
                ->once()
                ->andReturn(new Collection([$brand]));
        });

        $importLocation = new ImportLocation();
        $redirectResponse = $importLocation->validate($locationData, $importRecord);

        expect($redirectResponse)->toContain('The price fall down percentage field must be between 0 and 100.');
    }
);

test('validate Import Location Import Columns', function (): void {
    $requiredHeaderColumns = LocationImportColumns::getArrayValues();

    $importLocation = new ImportLocation();
    $response = $importLocation->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});

function getLocationData(): array
{
    return [
        'type' => 'Store',
        'name' => 'location test',
        'code' => 'location code test',
        'registration_number' => '123456',
        'sst_number' => '132465798',
        'email' => 'location@gmail.com',
        'phone' => '132465798',
        'mobile' => null,
        'fax' => null,
        'address_line_1' => 'address line test',
        'address_line_2' => null,
        'city' => 'location_city',
        'area_code' => 123465,
        'website' => null,
        'sales_tax_percentage' => 10,
        'sales_return_days_limit' => 0,
        'credit_note_expiration_days' => 0,
        'loyalty_point_expiration_days' => 0,
        'receipt_footer' => 'location footer',
        'disclaimer' => 'location disclaimer',
        'brands' => 'test',
        'cash_out_limit_info' => 0,
        'cash_out_limit_warning' => 0,
        'cash_out_limit_restrict' => 0,
        'region_id' => null,
        'price_fall_down_percentage' => 80,
        'country' => 'India',
        'state' => 'Gujarat',
    ];
}

function getImportRecordsForLocation(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::LOCATIONS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
}
