<?php

declare(strict_types=1);

use App\Domains\Vendor\DataObjects\VendorData;
use App\Domains\Vendor\VendorQueries;
use App\Models\Company;
use App\Models\Vendor;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->vendorA = Vendor::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
        'code' => 'EFGH',
    ]);

    $this->vendorQueries = new VendorQueries();
});

test('Vendor can be searched', function (): void {
    Vendor::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'XYZ',
        'code' => 'X1234',
    ]);

    $response = $this->vendorQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->vendorA->name)
        ->toHaveKey('code', $this->vendorA->code);
});

test('New warehouse can be added', function (): void {
    $newVendorRecord = Vendor::factory()->make([
        'company_id' => $this->companyA->id,
    ])->toArray();
    $companyId = $newVendorRecord['company_id'];
    unset($newVendorRecord['company_id']);

    $this->vendorQueries->addNew(new VendorData(...$newVendorRecord), $companyId);

    $this->assertDatabaseHas('vendors', $newVendorRecord);
});

test('A vendor can be fetched', function (): void {
    $response = $this->vendorQueries->getById($this->vendorA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->vendorA->name)
        ->toHaveKey('code', $this->vendorA->code);
});

test('A vendor can be updated', function (): void {
    $newVendorRecord = Vendor::factory()->make([
        'company_id' => $this->companyA->id,
    ])->toArray();
    unset($newVendorRecord['company_id']);

    $this->vendorQueries->update(new VendorData(...$newVendorRecord), $this->vendorA->id, $this->companyA->id);

    $this->assertDatabaseHas('vendors', $newVendorRecord);
});

test('getVendorsExport method returns vendor as expected', function (): void {
    $response = $this->vendorQueries->getVendorsExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->vendorA->id)
        ->toHaveKey('name', $this->vendorA->name);
});

test('it retrieves a collection of vendors by their IDs', function (): void {
    $vendorId = Vendor::factory()->create()->id;
    $response = $this->vendorQueries->getByIds([$vendorId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test('A vendor can be fetched by id and company id', function (): void {
    $response = $this->vendorQueries->getByIdAndCompanyId($this->vendorA->id, $this->companyA->id);
    expect($response->toArray())
        ->toHaveKey('id', $this->vendorA->id);
});

test('Update vendor data by phone', function (): void {
    $vendorDataUpdate = $this->vendorA->toArray();
    $vendorDataUpdate['code'] = 'ACC';
    unset($vendorDataUpdate['id'], $vendorDataUpdate['updated_at'], $vendorDataUpdate['created_at']);
    $this->vendorQueries->updateByPhone($vendorDataUpdate, $this->companyA->id);
    $this->assertDatabaseHas('vendors', $vendorDataUpdate);
});

test('Name is exists except current record', function (): void {
    $vendorDataUpdate = $this->vendorA->toArray();
    $response = $this->vendorQueries->existsByNameExpectCurrentRecord(
        $vendorDataUpdate['name'],
        $vendorDataUpdate['phone'],
        $this->companyA->id
    );
    expect($response)->toBe(false);
});

test('phone is exists or not', function (): void {
    $vendorDataUpdate = $this->vendorA->toArray();
    $response = $this->vendorQueries->existsByPhone($vendorDataUpdate['phone'], $this->companyA->id);
    expect($response)->toBe(true);
});

test('getIdByName is exists or not', function (): void {
    $vendorData = $this->vendorA->toArray();
    $response = $this->vendorQueries->getIdByName($vendorData['name'], $this->companyA->id);
    expect($response->toArray())
        ->toHaveKey('id', $this->vendorA->id);
});
