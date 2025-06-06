<?php

declare(strict_types=1);

use App\Domains\DreamPriceProduct\DataObjects\DreamPriceProductsData;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('user can only upload xlsx, ods, xls while upload dream price.', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $dreamPriceProductDetails = [
        'dream_price_products' => $uploadedFile,
    ];

    $request = new Request($dreamPriceProductDetails);

    $request->validate(DreamPriceProductsData::rules());
})->throws(ValidationException::class);

test('dream_price_products required validation works while upload dream price.', function (): void {
    $importRecordDetails = [
        'dream_price_products' => '',
    ];

    $request = new Request($importRecordDetails);

    $request->validate(DreamPriceProductsData::rules());
})->throws(ValidationException::class);

test('user can upload xlsx, ods, xls while upload dream price.', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->create('import.xlsx');

    $importRecordDetails = [
        'dream_price_products' => $uploadedFile,
    ];

    $request = new Request($importRecordDetails);

    $request->validate(DreamPriceProductsData::rules());

    $this->assertTrue(true);
});
