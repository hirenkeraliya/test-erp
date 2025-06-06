<?php

declare(strict_types=1);

use App\Domains\Designation\DataObjects\DesignationData;
use App\Domains\Designation\DataObjects\SuperAdminDesignationData;
use App\Domains\Designation\DesignationQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Designation;
use App\Models\SuperAdmin;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->companyB = Company::factory()->create();

    $this->designationA = Designation::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
    ]);

    $this->designationB = Designation::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
    ]);

    $this->designationC = Designation::factory()->create([
        'company_id' => $this->companyB->id,
        'name' => 'PQR',
        'code' => 'RST',
    ]);

    $this->designationQueries = new DesignationQueries();
});

test('Designations can be searched', function (): void {
    $response = $this->designationQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->designationA->name);
});

test('Designations on Super admin panel can be searched', function (): void {
    $response = $this->designationQueries->listQueryForSuperAdmin([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->designationA->name);
});

test('Designations on Super admin panel only include active companies', function (): void {
    $this->companyB->delete();

    $response = $this->designationQueries->listQueryForSuperAdmin([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->pluck('id'))->toContain($this->designationA->id);
    expect($response->getCollection()->pluck('id'))->toContain($this->designationB->id);

    expect($response->getCollection()->pluck('id'))->not->toContain($this->designationC->id);
});

test("Designations are returned as per admin's company", function (): void {
    Designation::factory()->create();

    $response = $this->designationQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->designationB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->designationA->name);
});

test('new designation can be added', function (): void {
    $admin = Admin::factory()->create();

    $this->designationQueries->addNew(
        new DesignationData('designationName', 'designationCode'),
        $this->companyId,
        $admin
    );

    $this->assertDatabaseHas('designations', [
        'name' => 'designationName',
        'code' => 'designationCode',
        'company_id' => $this->companyId,
    ]);
});

test('new designation can be added for super admin', function (): void {
    $superAdmin = SuperAdmin::factory()->create();
    $this->designationQueries->addForSuperAdmin(
        new SuperAdminDesignationData($this->companyId, 'designationName', 'designationCode'),
        $superAdmin
    );

    $this->assertDatabaseHas('designations', [
        'name' => 'designationName',
        'code' => 'designationCode',
        'company_id' => $this->companyId,
    ]);
});

test('A designations can be fetched', function (): void {
    $response = $this->designationQueries->getById($this->designationA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->designationA->name)
        ->toHaveKey('code', $this->designationA->code);
});

test('A designations can be fetched for super admin', function (): void {
    $response = $this->designationQueries->getByIdWithoutCompanyFilter($this->designationA->id);
    expect($response->toArray())
        ->toHaveKey('name', $this->designationA->name)
        ->toHaveKey('code', $this->designationA->code);
});

test('A designation can be updated', function (): void {
    $this->designationQueries->update(
        new DesignationData('designationNameUpdate', 'designationCodeUpdate'),
        $this->designationA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('designations', [
        'name' => 'designationNameUpdate',
        'code' => 'designationCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('A designation can be updated for super admin', function (): void {
    $this->designationQueries->updateForSuperAdmin(
        new SuperAdminDesignationData($this->companyId, 'designationNameUpdate1', 'designationCodeUpdate1'),
        $this->designationA->id,
    );

    $this->assertDatabaseHas('designations', [
        'name' => 'designationNameUpdate1',
        'code' => 'designationCodeUpdate1',
        'company_id' => $this->companyId,
    ]);
});

test('designations are returned as per page', function (): void {
    $response = $this->designationQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->designationB->name);
});

test('designations can be sorted by id', function (): void {
    $response = $this->designationQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->designationA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->designationB->name);
});

test('designations can be fetched', function (): void {
    $response = $this->designationQueries->getByCompanyId($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->designationA->id)
        ->toHaveKey('name', $this->designationA->name);
});

test('getIdByName method returns designation id', function (): void {
    $response = $this->designationQueries->getIdByName($this->designationA->name, $this->companyId);
    $this->assertEquals($this->designationA->id, $response);
});

test('getDesignationsExport method returns designation as expected', function (): void {
    $response = $this->designationQueries->getDesignationsExport([
        'search_text' => $this->designationA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->designationA->id)
        ->toHaveKey('name', $this->designationA->name);
});
