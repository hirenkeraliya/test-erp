<?php

declare(strict_types=1);

use App\Domains\PackageType\DataObjects\PackageTypeData;
use App\Domains\PackageType\PackageTypeQueries;
use App\Models\Company;
use App\Models\PackageType;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);

    $this->companyB = Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'EFGH',
    ]);

    $this->packageTypeA = PackageType::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'DEF',
    ]);

    $this->packageTypeB = PackageType::factory()->create([
        'company_id' => $this->companyB->id,
        'name' => 'GHI',
    ]);

    $this->packageTypeQueries = new PackageTypeQueries();
});

test('Package type can be searched', function (): void {
    $response = $this->packageTypeQueries->listQuery([
        'search_text' => $this->packageTypeA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->packageTypeA->name);
});

test('New package type can be added', function (): void {
    $this->packageTypeQueries->addNew(new PackageTypeData('ABCD'), $this->companyA->id);

    $this->assertDatabaseHas('package_types', [
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);
});

test('A package type can be fetched', function (): void {
    $response = $this->packageTypeQueries->getById($this->packageTypeA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->packageTypeA->name);
});

test('A package type can be updated', function (): void {
    $this->packageTypeQueries->update(new PackageTypeData('EFGHI'), $this->packageTypeA->id, $this->companyA->id);

    $this->assertDatabaseHas('package_types', [
        'company_id' => $this->companyA->id,
        'name' => 'EFGHI',
    ]);
});

test('getWithBasicColumns method returns the list of package type', function (): void {
    $response = $this->packageTypeQueries->getWithBasicColumns($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->packageTypeA->name);
});

test('getPackageTypeExport method returns package type as expected', function (): void {
    $response = $this->packageTypeQueries->getPackageTypeExport([
        'search_text' => $this->packageTypeA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->packageTypeA->name);
});

test('getLists method returns package type as expected', function (): void {
    $response = $this->packageTypeQueries->getLists($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->packageTypeA->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->packageTypeQueries->existsByName($this->packageTypeA->name, $this->packageTypeA->company_id);
    $this->assertTrue($response);

    $response = $this->packageTypeQueries->existsByName('ABCDEFGH', $this->packageTypeA->company_id);
    $this->assertFalse($response);
});

test('getIdByName method returns result as expected', function (): void {
    $response = $this->packageTypeQueries->getIdByName($this->packageTypeA->name, $this->packageTypeA->company_id);

    $this->assertEquals($response, $this->packageTypeA->id);
});
