<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Models\Company;
use App\Models\ExportRecord;

test('the getFiltersById method call and returns the required columns', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
    ]);

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->getFiltersById($exportRecord->id, $exportRecord->company_id);

    expect($response->toArray())
        ->toHaveKey('id', $exportRecord->id)
        ->toHaveKey('filters', $exportRecord->filters);
});

test('the listQuery method call and returns the lists of export record.', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_direction' => '',
        'sort_by' => '',
        'export_record_id' => null,
        'date_range' => [],
        'export_type' => null,
        'status' => null,
        'per_page' => null,
    ];

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->listQuery($filterData, $exportRecord->company_id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('created_by_id', $exportRecord->first()->created_by_id)
        ->toHaveKey('company_id', $exportRecord->first()->company_id)
        ->toHaveKey('type_id', $exportRecord->first()->type_id)
        ->toHaveKey('created_by_type', $exportRecord->first()->created_by_type)
        ->toHaveKey('module_type', $exportRecord->first()->module_type)
        ->toHaveKey('status', $exportRecord->first()->status)
        ->toHaveKey('total_records', $exportRecord->first()->total_records)
        ->toHaveKey('total_exported_records', $exportRecord->first()->total_exported_records);
});

test('the getExportRecordExport method call and returns the lists of export record.', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_direction' => '',
        'sort_by' => '',
        'export_record_id' => null,
        'date_range' => [],
        'export_type' => null,
        'status' => null,
        'per_page' => null,
    ];

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->getExportRecordExport($filterData, $exportRecord->company_id);

    expect($response->first()->toArray())
        ->toHaveKey('created_by_id', $exportRecord->first()->created_by_id)
        ->toHaveKey('company_id', $exportRecord->first()->company_id)
        ->toHaveKey('type_id', $exportRecord->first()->type_id)
        ->toHaveKey('created_by_type', $exportRecord->first()->created_by_type)
        ->toHaveKey('module_type', $exportRecord->first()->module_type)
        ->toHaveKey('status', $exportRecord->first()->status)
        ->toHaveKey('total_records', $exportRecord->first()->total_records)
        ->toHaveKey('total_exported_records', $exportRecord->first()->total_exported_records);
});

test('the listQueryForStoreManager method call and returns the lists of export record.', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::STORE_MANAGER->name,
    ]);

    ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_direction' => '',
        'sort_by' => '',
        'export_record_id' => null,
        'date_range' => [],
        'export_type' => null,
        'status' => null,
        'per_page' => null,
    ];

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->listQueryForStoreManager($filterData, $exportRecord->company_id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('created_by_id', $exportRecord->first()->created_by_id)
        ->toHaveKey('company_id', $exportRecord->first()->company_id)
        ->toHaveKey('type_id', $exportRecord->first()->type_id)
        ->toHaveKey('created_by_type', $exportRecord->first()->created_by_type)
        ->toHaveKey('module_type', $exportRecord->first()->module_type)
        ->toHaveKey('status', $exportRecord->first()->status)
        ->toHaveKey('total_records', $exportRecord->first()->total_records)
        ->toHaveKey('total_exported_records', $exportRecord->first()->total_exported_records);
});

test('the exportListQueryForStoreManager method call and returns the lists of export record.', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::STORE_MANAGER->name,
    ]);

    ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_direction' => '',
        'sort_by' => '',
        'export_record_id' => null,
        'date_range' => [],
        'export_type' => null,
        'status' => null,
        'per_page' => null,
    ];

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->exportListQueryForStoreManager($filterData, $exportRecord->company_id);

    expect($response->first()->toArray())
        ->toHaveKey('created_by_id', $exportRecord->first()->created_by_id)
        ->toHaveKey('company_id', $exportRecord->first()->company_id)
        ->toHaveKey('type_id', $exportRecord->first()->type_id)
        ->toHaveKey('created_by_type', $exportRecord->first()->created_by_type)
        ->toHaveKey('module_type', $exportRecord->first()->module_type)
        ->toHaveKey('status', $exportRecord->first()->status)
        ->toHaveKey('total_records', $exportRecord->first()->total_records)
        ->toHaveKey('total_exported_records', $exportRecord->first()->total_exported_records);
});

test('the listQueryForWarehouseManager method call and returns the lists of export record.', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::WAREHOUSE_MANAGER->name,
    ]);

    ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_direction' => '',
        'sort_by' => '',
        'export_record_id' => null,
        'date_range' => [],
        'export_type' => null,
        'status' => null,
        'per_page' => null,
    ];

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->listQueryForWarehouseManager($filterData, $exportRecord->company_id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('created_by_id', $exportRecord->first()->created_by_id)
        ->toHaveKey('company_id', $exportRecord->first()->company_id)
        ->toHaveKey('type_id', $exportRecord->first()->type_id)
        ->toHaveKey('created_by_type', $exportRecord->first()->created_by_type)
        ->toHaveKey('module_type', $exportRecord->first()->module_type)
        ->toHaveKey('status', $exportRecord->first()->status)
        ->toHaveKey('total_records', $exportRecord->first()->total_records)
        ->toHaveKey('total_exported_records', $exportRecord->first()->total_exported_records);
});

test('the exportListQueryForWarehouseManager method call and returns the lists of export record.', function (): void {
    $company = Company::factory()->create();

    $exportRecord = ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::WAREHOUSE_MANAGER->name,
    ]);

    ExportRecord::factory()->create([
        'company_id' => $company->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_direction' => '',
        'sort_by' => '',
        'export_record_id' => null,
        'date_range' => [],
        'export_type' => null,
        'status' => null,
        'per_page' => null,
    ];

    $exportRecordQueries = resolve(ExportRecordQueries::class);
    $response = $exportRecordQueries->exportListQueryForWarehouseManager($filterData, $exportRecord->company_id);

    expect($response->first()->toArray())
        ->toHaveKey('created_by_id', $exportRecord->first()->created_by_id)
        ->toHaveKey('company_id', $exportRecord->first()->company_id)
        ->toHaveKey('type_id', $exportRecord->first()->type_id)
        ->toHaveKey('created_by_type', $exportRecord->first()->created_by_type)
        ->toHaveKey('module_type', $exportRecord->first()->module_type)
        ->toHaveKey('status', $exportRecord->first()->status)
        ->toHaveKey('total_records', $exportRecord->first()->total_records)
        ->toHaveKey('total_exported_records', $exportRecord->first()->total_exported_records);
});
