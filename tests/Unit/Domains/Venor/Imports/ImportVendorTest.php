<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Vendor\Imports\ImportVendor;
use App\Domains\Vendor\VendorQueries;
use App\Models\ImportRecord;

test('validate method returns blank array', function (): void {
    $companyId = 1;
    $vendorData = getVendorData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(VendorQueries::class, function ($mock) use ($vendorData, $companyId): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($vendorData['name'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('existsByPhone')
            ->once()
            ->with($vendorData['phone'], $companyId)
            ->andReturn(false);
    });

    $importVendor = new ImportVendor();
    $redirectResponse = $importVendor->validate($vendorData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('validate method returns issues list of the given data', function (): void {
    $companyId = 1;
    $vendorData = getVendorData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(VendorQueries::class, function ($mock) use ($vendorData, $companyId): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($vendorData['name'], $companyId)
            ->andReturn(true);

        $mock->shouldReceive('existsByPhone')
            ->once()
            ->with($vendorData['phone'], $companyId)
            ->andReturn(true);
    });

    $importVendor = new ImportVendor();
    $redirectResponse = $importVendor->validate($vendorData, $importRecord);
    $this->assertEquals(2, count($redirectResponse));
});

test('name and phone, email are require while import record', function (): void {
    $companyId = 1;
    $vendorData = [
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
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(VendorQueries::class, function ($mock) use ($vendorData, $companyId): void {
        vendorMockExistsByNameMethod($mock, $vendorData['name'], $companyId, false, 0);

        $mock->shouldReceive('existsByPhone')
            ->times(0)
            ->with($vendorData['phone'], $companyId)
            ->andReturn(false);
    });

    $importVendor = new ImportVendor();
    $redirectResponse = $importVendor->validate($vendorData, $importRecord);
    $this->assertEquals(6, count($redirectResponse));
});

test('save method saves the data', function (): void {
    $companyId = 1;

    $vendorData = getVendorData();

    $importRecord = getImportRecordsForVendor($companyId);

    $this->mock(VendorQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importVendor = new ImportVendor();
    $importVendor->save($vendorData, $importRecord);
});

function vendorMockExistsByNameMethod($mockClass, ?string $name, int $companyId, bool $returnData, int $times = 1): void
{
    $mockClass->shouldReceive('existsByName')
        ->times($times)
        ->with($name, $companyId)
        ->andReturn($returnData);
}

function getVendorData(): array
{
    return [
        'name' => 'test',
        'code' => 'code test',
        'registration_number' => '123456',
        'sst_number' => '132465798',
        'email' => 'vendor@gmail.com',
        'phone' => '132465798',
        'mobile' => null,
        'fax' => null,
        'address_line_1' => 'address line test',
        'address_line_2' => null,
        'city' => 'vendor_city',
        'area_code' => 123465,
        'website' => null,
        'consignment' => null,
        'commission_percentage' => null,
    ];
}

function getImportRecordsForVendor(int $companyId): ImportRecord
{
    return ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::VENDORS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
}
