<?php

declare(strict_types=1);

use App\Domains\ImportRecord\Readers\FileReaderFilters;
use App\Services\SpreadsheetService;

test('It can return proper last row', function (): void {
    $spreadsheet = new SpreadsheetService();
    $spreadsheet->loadFileDetails('Xlsx', __DIR__ . '/import-file.xlsx');

    $this->assertTrue($spreadsheet->getHighestRow() === 4);
});

test('It can return proper last column index', function (): void {
    $spreadsheet = new SpreadsheetService();
    $spreadsheet->loadFileDetails('Xlsx', __DIR__ . '/import-file.xlsx');

    $this->assertTrue($spreadsheet->getHighestColumn() === 'B');
});

test('It can return proper column value', function (): void {
    $spreadsheet = new SpreadsheetService();
    $spreadsheet->loadFileDetails('Xlsx', __DIR__ . '/import-file.xlsx');

    $this->assertTrue($spreadsheet->getColumnValueFor($rowIndex = 2, $columnIndex = 1) === 'Test Name');

    $this->assertTrue($spreadsheet->getColumnValueFor($rowIndex = 2, $columnIndex = 2) === 'TestCode');
});

test('It can return column index from column name', function (): void {
    $spreadsheet = new SpreadsheetService();
    $spreadsheet->loadFileDetails('Xlsx', __DIR__ . '/import-file.xlsx');

    $lastColumnName = $spreadsheet->getHighestColumn();

    $this->assertTrue($spreadsheet->columnIndexFromString($lastColumnName) === 2);
});

test('It can set and use proper row filters', function (): void {
    $spreadsheet = new SpreadsheetService();
    $spreadsheet->setRowFilters(new FileReaderFilters($startRow = 1, $endRow = 2));
    $spreadsheet->loadFileDetails('Xlsx', __DIR__ . '/import-file.xlsx');

    $this->assertTrue($spreadsheet->getHighestRow() === 2);
});
