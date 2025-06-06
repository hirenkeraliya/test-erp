<?php

declare(strict_types=1);

use App\Domains\ImportRecord\Services\ImportRecordService;

beforeEach(function (): void {
    $this->importRecordService = new ImportRecordService();
});

test('It calls has more records method and returns proper response', function (): void {
    $this->assertTrue($this->importRecordService->hasMoreRecords(5, 5, 10));
    $this->assertFalse($this->importRecordService->hasMoreRecords(10, 10, 10));
    $this->assertFalse($this->importRecordService->hasMoreRecords(10, 11, 10));
});

test('It calls header Already Set method and returns proper response', function (): void {
    $this->assertFalse($this->importRecordService->headerColumnsAlreadySet(2, []));
    $this->assertFalse($this->importRecordService->headerColumnsAlreadySet(1, []));
    $this->assertFalse($this->importRecordService->headerColumnsAlreadySet(1, [null]));
    $this->assertFalse($this->importRecordService->headerColumnsAlreadySet(1, ['']));
    $this->assertTrue($this->importRecordService->headerColumnsAlreadySet(1, [1, 2]));
});

test('It calls get job restart time method and returns proper response', function (): void {
    $this->assertTrue(
        now()->addSeconds(48)->toString() === $this->importRecordService->getJobRestartTime()->toString()
    );

    config([
        'horizon.environments.' . config('app.env') . '.supervisor-1.timeout' => 300,
    ]);
    $this->assertTrue(
        now()->addSeconds(240)->toString() === $this->importRecordService->getJobRestartTime()->toString()
    );
});

test('It calls job is ready to expire method and returns proper response', function (): void {
    $this->assertFalse($this->importRecordService->jobIsReadyToExpire(now()->addSeconds(10)));
    $this->assertFalse($this->importRecordService->jobIsReadyToExpire(now()->addSeconds(30)));
    $this->assertTrue($this->importRecordService->jobIsReadyToExpire(now()));
});

test('It calls get End Row Index method and returns proper response', function (): void {
    $this->assertTrue($this->importRecordService->getNewEndRowNumber(
        insertedRowsCount: 30,
        currentEndRowNumber: null,
        currentStartRowNumber: null,
        totalRecordsInFile: 60
    ) === 53);

    $this->assertTrue($this->importRecordService->getNewEndRowNumber(
        insertedRowsCount: 30,
        currentEndRowNumber: 53,
        currentStartRowNumber: 30,
        totalRecordsInFile: 200
    ) === 53);

    $this->assertTrue($this->importRecordService->getNewEndRowNumber(
        insertedRowsCount: 33,
        currentEndRowNumber: 53,
        currentStartRowNumber: 30,
        totalRecordsInFile: 50
    ) === 51);
});

test('It calls the isThisFirstImportCycle method and returns proper response', function (): void {
    $response = $this->importRecordService->isThisFirstImportCycle(null, null);
    $this->assertTrue($response);

    $response = $this->importRecordService->isThisFirstImportCycle(10, 15);
    $this->assertFalse($response);
});
