<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Region\DataObjects\RegionData;
use App\Domains\Region\RegionQueries;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Company;
use App\Models\Location;
use App\Models\Region;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->managerName = 'def_manager';
    $this->managerEmail = 'def_manager@gmail.com';

    $this->regionA = Region::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
        'manager_name' => $this->managerName,
        'manager_email' => $this->managerEmail,
    ]);

    $this->regionB = Region::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
        'manager_name' => 'abc_manager',
        'manager_email' => 'abc_manager@gmail.com',
    ]);

    $this->regionQueries = new RegionQueries();
});

test('Region can be searched', function (): void {
    $response = $this->regionQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->regionA->name);
});

test("Region are returned as per admin's company", function (): void {
    Color::factory()->create();

    $response = $this->regionQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->regionB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->regionA->name);
});

test('new region can be added', function (): void {
    $region = $this->regionQueries->addNew(
        new RegionData('regionName', 'regionCode', 'managerName', 'abcd@gmail.com'),
        $this->companyId
    );

    $this->assertDatabaseHas('regions', [
        'id' => $region->id,
        'name' => $region->name,
        'code' => $region->code,
        'company_id' => $this->companyId,
        'manager_name' => $region->manager_name,
        'manager_email' => $region->manager_email,
    ]);
});

test('A regions can be fetched', function (): void {
    $response = $this->regionQueries->getById($this->regionA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->regionA->name)
        ->toHaveKey('code', $this->regionA->code);
});

test('A region can be updated', function (): void {
    $this->regionQueries->update(
        new RegionData('regionNameUpdate', 'regionCodeUpdate', 'managerNameUpdate', 'abcd@gmail.com'),
        $this->regionA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('regions', [
        'name' => 'regionNameUpdate',
        'code' => 'regionCodeUpdate',
        'company_id' => $this->companyId,
        'manager_name' => 'managerNameUpdate',
        'manager_email' => 'abcd@gmail.com',
    ]);
});

test('getRegionsExport method returns regions as expected', function (): void {
    $response = $this->regionQueries->getRegionsExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->regionA->id)
        ->toHaveKey('name', $this->regionA->name);
});

test('A regions can be fetched by companyId', function (): void {
    $response = $this->regionQueries->getWithBasicColumns($this->companyId);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->regionA->id)
        ->toHaveKey('name', $this->regionA->name);
});

test(
    'A getRegionsIdColumn method return not null manager email region with id column',
    function (): void {
        $response = $this->regionQueries->getRegionsIdColumn();
        expect($response->first()->toArray())
            ->toHaveKey('id', $this->regionA->id);
    }
);

test(
    'A getRegionByIdWithStoresAndBrands method return not null manager email regions with location and brand',
    function (): void {
        $brand = Brand::factory()->create();
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'region_id' => $this->regionA->id,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $location->brands()->sync($brand->id);

        $response = $this->regionQueries->getRegionByIdWithStoresAndBrands($this->regionA->id);

        expect($response->toArray())
            ->toHaveKeys(['company_id', 'name', 'manager_email', 'locations', 'locations.0.brands']);
    }
);

test('A region update by name', function (): void {
    $regionData = $this->regionA->toArray();
    $regionData['manager_email'] = 'test@gmail.com';
    unset($regionData['created_at'],$regionData['updated_at'],$regionData['id'],$regionData['company_id']);
    $this->regionQueries->updateByName(new RegionData(...$regionData), $this->companyId);
    $this->assertDatabaseHas('regions', $regionData);
});

test('A existsByCodeExceptCurrentRecord method check the code is unique company wise', function (): void {
    $regionData = $this->regionA->toArray();
    $response = $this->regionQueries->existsByCodeExceptCurrentRecord(
        $regionData['code'],
        $regionData['name'],
        $this->companyId
    );
    $this->assertFalse($response);
});

test('Get Region name for export PDF headers', function (): void {
    $response = $this->regionQueries->getRegionNameForFilter([$this->regionA->id]);

    $this->assertIsString($response);
});

test('getAllByCompanyId returns the Regions details', function (): void {
    $this->regionB->delete();

    $response = $this->regionQueries->getAllByCompanyId($this->companyId);

    expect($response->count())->toBe(1);
    expect($response->toArray()[0])->toHaveKey('id', $this->regionA->id);
});
