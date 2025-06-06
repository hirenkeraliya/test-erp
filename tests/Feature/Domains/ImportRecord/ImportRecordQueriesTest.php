<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\ImportRecordFailedRow;
use App\Models\StockAdjustment;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin = Admin::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $this->importRecordQueries = new ImportRecordQueries();
});

test('Import Records can be searched', function (): void {
    $importRecordA = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'records_in_file' => 999999,
        'records_imported' => 999999,
        'records_failed' => 999999,
    ]);

    ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'records_in_file' => 888,
        'records_imported' => 888,
        'records_failed' => 888,
    ]);

    $response = $this->importRecordQueries->listQuery([
        'search_text' => '999999',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'import_record_id' => null,
        'status' => null,
        'import_type' => null,
        'date_range' => null,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('records_imported', $importRecordA->records_imported);
});

test('An Import Record can be added', function (): void {
    config()->set('filesystems.default', 'public');
    $filename = 'import-file.xlsx';

    Storage::put($filename, '');

    Excel::store([
        'name' => 'Test',
    ], $filename);

    $uploadedFile = new UploadedFile(
        base_path('storage/app/public/import-file.xlsx'),
        $filename,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $newImportRecord = [
        'type_id' => 1,
        'upload_file' => $uploadedFile,
    ];

    $this->importRecordQueries->addNew(
        new ImportRecordData(...$newImportRecord),
        $this->admin,
        $this->company->id,
        null,
    );

    $this->assertDatabaseHas('import_records', [
        'company_id' => $this->company->id,
        'type_id' => 1,
        'status' => 1,
        'records_in_file' => 0,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::IMPORT_RECORD->name,
        'collection_name' => 'upload_file',
        'file_name' => $filename,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
    config()->set('filesystems.default', 'local');
});

test('Import Record mark As In Progress', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->importRecordQueries->markAsInProgress($importRecord, 10);

    $this->assertDatabaseHas('import_records', [
        'company_id' => $this->company->id,
        'status' => Status::IN_PROGRESS->value,
        'records_in_file' => 10,
    ]);
});
test('Import Record mark As Completed', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->importRecordQueries->markAsCompleted($importRecord);

    $this->assertDatabaseHas('import_records', [
        'company_id' => $this->company->id,
        'status' => Status::COMPLETED->value,
    ]);
});

test('Import Record save Header Details', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->importRecordQueries->saveHeaderColumns($importRecord, ['abc', 'xyz']);

    $importRecord->refresh();

    expect($importRecord)
        ->company_id->toEqual($this->company->id)
        ->header_columns->toEqual(['abc', 'xyz']);
});

test('Import Record increment Failed Records Count', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'records_failed' => 0,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->importRecordQueries->incrementFailedRecordsCount($importRecord, 10);

    $this->assertDatabaseHas('import_records', [
        'company_id' => $this->company->id,
        'records_failed' => 1,
    ]);
});

test('Import Record increment Imported Records Count', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'records_imported' => 0,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->importRecordQueries->incrementImportedRecordsCount($importRecord, 10);

    $this->assertDatabaseHas('import_records', [
        'company_id' => $this->company->id,
        'records_imported' => 1,
    ]);
});

test('failed records file can be created', function (): void {
    Storage::fake('public');

    $this->freezeTime();

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'header_columns' => ['name', 'code'],
        'records_imported' => 0,
        'records_failed' => 2,
    ]);

    ImportRecordFailedRow::factory()->create([
        'import_record_id' => $importRecord->id,
        'row_data' => [
            'name' => 'product 1',
            'code' => '123',
        ],
        'fail_reasons' => ['abc'],
    ]);

    $this->importRecordQueries->generateFailedRecordsFile($importRecord);
    $importRecord->refresh();

    $importRecordData = $importRecord->getFirstMedia('failed_rows_file')->toArray();

    $filename = now()->format('y-m-d-h-i-s') . '.xlsx';
    $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    $this->assertTrue($importRecordData['file_name'] === $filename);
    $this->assertTrue($importRecordData['mime_type'] === $mimeType);
});

test('failed records zip file can be created', function (): void {
    Storage::fake('public');

    $this->freezeTime();
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'header_columns' => [],
        'records_imported' => 0,
        'records_failed' => 1,
    ]);

    ImportRecordFailedRow::factory()->create([
        'import_record_id' => $importRecord->id,
        'row_data' => [],
        'fail_reasons' => ['Image avatar.jpg does not have dimensions of 343*260 pixels.'],
    ]);

    $this->importRecordQueries->generateFailedRecordsImage($importRecord);
    $importRecord->refresh();

    $importRecordData = $importRecord->getFirstMedia('failed_rows_file')->toArray();

    $filename = now()->format('d-m-Y_H:i:s') . '.zip';
    $mimeType = 'application/zip';

    $this->assertTrue($importRecordData['file_name'] === $filename);
    $this->assertTrue($importRecordData['mime_type'] === $mimeType);
})->skip();

test('getUploadedMedia method returns the uploaded media object', function (): void {
    Storage::fake('public');

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'header_columns' => ['name', 'code'],
        'records_imported' => 0,
        'records_failed' => 2,
    ]);

    $importRecord->copyMedia(__DIR__ . '/Jobs/import-file.xlsx')
        ->toMediaCollection('upload_file');

    $media = $this->importRecordQueries->getUploadedMedia($importRecord);
    expect($media)
        ->file_name->toEqual('import-file.xlsx')
        ->collection_name->toEqual('upload_file');
});

test('getFilePath method return proper response', function (): void {
    Storage::fake('public');

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'header_columns' => ['name', 'code'],
        'records_imported' => 0,
        'records_failed' => 2,
    ]);

    $importRecord->copyMedia(__DIR__ . '/Jobs/import-file.xlsx')
        ->toMediaCollection('upload_file');

    $media = $this->importRecordQueries->getFilePath($importRecord);
    expect($media)->toBeString();
});

test('getUploadedMediaUrl method return proper response', function (): void {
    Storage::fake('public');

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'header_columns' => ['name', 'code'],
        'records_imported' => 0,
        'records_failed' => 2,
    ]);

    $importRecord->copyMedia(__DIR__ . '/Jobs/import-file.xlsx')
        ->toMediaCollection('upload_file');

    $media = $this->importRecordQueries->getUploadedMediaUrl($importRecord);
    expect($media)->toBeString();
});

test('getImportRecordExport method returns import records as expected', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'records_in_file' => 888,
        'records_imported' => 888,
        'records_failed' => 888,
    ]);

    $response = $this->importRecordQueries->getImportRecordExport([
        'search_text' => '888',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'import_record_id' => null,
        'status' => null,
        'import_type' => null,
        'date_range' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
       ->toHaveKey('records_imported', $importRecord->records_imported);
});

test('listQueryForStoreManager method returns import records as expected', function (): void {
    $storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $storeManager->id,
        'created_by_type' => ModelMapping::STORE_MANAGER->name,
        'records_in_file' => 888,
        'records_imported' => 888,
        'records_failed' => 888,
    ]);

    $response = $this->importRecordQueries->listQueryForStoreManager([
        'search_text' => '888',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'import_record_id' => null,
        'date_range' => null,
        'import_type' => null,
        'status' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
       ->toHaveKey('records_imported', $importRecord->records_imported)
       ->toHaveKeys([
           'company_id',
           'created_by_id',
           'type_id',
           'status',
           'created_by_type',
           'created_by.employee_id',
           'created_by.employee.staff_id',
       ]);
});

test('listQueryForWarehouseManager method returns import records as expected', function (): void {
    $warehouseManager = WarehouseManager::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $warehouseManager->id,
        'created_by_type' => ModelMapping::WAREHOUSE_MANAGER->name,
        'records_in_file' => 888,
        'records_imported' => 888,
        'records_failed' => 888,
    ]);

    $response = $this->importRecordQueries->listQueryForWarehouseManager([
        'search_text' => '888',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'import_record_id' => null,
        'date_range' => null,
        'import_type' => null,
        'status' => null,
    ], $this->company->id);

    expect($response->first()->toArray())
       ->toHaveKey('records_imported', $importRecord->records_imported)
       ->toHaveKeys([
           'company_id',
           'created_by_id',
           'type_id',
           'status',
           'created_by_type',
           'created_by.employee_id',
           'created_by.employee.staff_id',
       ]);
});

test('getPendingImportRecordCount method returns import records as expected', function (): void {
    $stockAdjustment = StockAdjustment::factory()->create([
        'company_id' => $this->company->id,
        'created_by_admin_id' => $this->admin->id,
        'approved_by_employee_id' => $this->employee->id,
    ]);

    ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'created_by_id' => $this->admin->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'module_id' => $stockAdjustment->id,
        'module_type' => ModelMapping::STOCK_ADJUSTMENT->name,
        'status' => Status::PENDING->value,
    ]);

    $response = $this->importRecordQueries->getPendingImportRecordCount(
        ModelMapping::STOCK_ADJUSTMENT->name,
        $this->company->id
    );

    expect($response)->toBe(1);
});

test('getByIdWithCompany method returns import records as expected', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'records_in_file' => 888,
        'records_imported' => 888,
        'records_failed' => 888,
    ]);

    $response = $this->importRecordQueries->getByIdWithCompany($importRecord->id, $this->company->id);

    expect($response->toArray())
       ->toHaveKeys(['company', 'company_id', 'created_by_id', 'type_id', 'status', 'created_by_type']);
});

test('getById method returns import records as expected', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'company_id' => $this->company->id,
        'records_in_file' => 888,
        'records_imported' => 888,
        'records_failed' => 888,
    ]);

    $response = $this->importRecordQueries->getById($importRecord->id, $this->company->id);

    expect($response->toArray())
       ->toHaveKeys(['company_id', 'created_by_id', 'type_id', 'status', 'created_by_type']);
});
