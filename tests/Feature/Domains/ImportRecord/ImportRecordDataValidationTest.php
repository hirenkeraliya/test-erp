<?php

declare(strict_types=1);

use App\Domains\ImportRecord\DataObjects\ImportRecordData;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('type id required validation works while import record.', function (): void {
    $importRecordDetails = [
        'type_id' => '',
        'upload_file' => '',
    ];

    $request = new Request($importRecordDetails);

    $request->validate(ImportRecordData::rules($request));
})->throws(ValidationException::class);

test('user can only upload xlsx, ods, xls while import record.', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $importRecordDetails = [
        'type_id' => 1,
        'upload_file' => $uploadedFile,
    ];

    $request = new Request($importRecordDetails);

    $request->validate(ImportRecordData::rules($request));
})->throws(ValidationException::class);

test('user can upload xlsx, ods, xls while import record.', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->create('import.xlsx');

    $importRecordDetails = [
        'type_id' => 1,
        'upload_file' => $uploadedFile,
    ];

    $request = new Request($importRecordDetails);

    $request->validate(ImportRecordData::rules($request));

    $this->assertTrue(true);
});
