<?php

declare(strict_types=1);

use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\Jobs\ProductBulkMediaUploadJob;
use App\Domains\Product\Enums\ProductUploadTypes;
use App\Models\Company;
use App\Models\ImportRecord;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

test('It can import thumbnail image in product', function (): void {
    Mail::fake();

    $importRecord = importRecordFactory();

    $products = Product::factory(2)->sequence(
        [
            'upc' => 'test1',
            'company_id' => $importRecord->company_id,
        ],
        [
            'upc' => 'test2',
            'company_id' => $importRecord->company_id,
        ]
    )->create();

    ProductBulkMediaUploadJob::dispatch($importRecord, ProductUploadTypes::THUMBNAIL->value)->onQueue(
        config('horizon.default_queue_name')
    );

    expect($products->last()->getMedia('thumbnail'))->toBeInstanceOf(MediaCollection::class);
});

test('check if the thumbnail media collection is empty', function (): void {
    Mail::fake();

    $importRecord = importRecordFactory();

    $products = Product::factory(2)->sequence(
        [
            'upc' => 'test1',
            'company_id' => $importRecord->company_id,
        ],
        [
            'upc' => 'test2',
            'company_id' => $importRecord->company_id,
        ]
    )->create();

    ProductBulkMediaUploadJob::dispatch($importRecord, ProductUploadTypes::THUMBNAIL->value)->onQueue(
        config('horizon.default_queue_name')
    );

    expect($products->first()->getMedia('thumbnail')->first())->toBeNull();
});

test('It can import images in product', function (): void {
    Mail::fake();

    $importRecord = importRecordFactory();

    $products = Product::factory(2)->sequence(
        [
            'upc' => 'test1',
            'company_id' => $importRecord->company_id,
        ],
        [
            'upc' => 'test2',
            'company_id' => $importRecord->company_id,
        ]
    )->create();

    ProductBulkMediaUploadJob::dispatch($importRecord, ProductUploadTypes::IMAGES->value)->onQueue(
        config('horizon.default_queue_name')
    );

    expect($products->last()->getMedia('images'))->toBeInstanceOf(MediaCollection::class);
});

test('check if the images media collection is empty', function (): void {
    Mail::fake();

    $importRecord = importRecordFactory();

    $products = Product::factory(2)->sequence(
        [
            'upc' => 'test1',
            'company_id' => $importRecord->company_id,
        ],
        [
            'upc' => 'test2',
            'company_id' => $importRecord->company_id,
        ]
    )->create();

    ProductBulkMediaUploadJob::dispatch($importRecord, ProductUploadTypes::IMAGES->value)->onQueue(
        config('horizon.default_queue_name')
    );

    expect($products->first()->getMedia('images')->first())->toBeNull();
});

test('It can import videos in product', function (): void {
    Mail::fake();

    $importRecord = importRecordFactory();

    $products = Product::factory(2)->sequence(
        [
            'upc' => 'test1',
            'company_id' => $importRecord->company_id,
        ],
        [
            'upc' => 'test2',
            'company_id' => $importRecord->company_id,
        ]
    )->create();

    ProductBulkMediaUploadJob::dispatch($importRecord, ProductUploadTypes::VIDEOS->value)->onQueue(
        config('horizon.default_queue_name')
    );

    expect($products->last()->getMedia('videos'))->toBeInstanceOf(MediaCollection::class);
});

test('check if the videos media collection is empty', function (): void {
    Mail::fake();

    $importRecord = importRecordFactory();

    $products = Product::factory(2)->sequence(
        [
            'upc' => 'test1',
            'company_id' => $importRecord->company_id,
        ],
        [
            'upc' => 'test2',
            'company_id' => $importRecord->company_id,
        ]
    )->create();

    ProductBulkMediaUploadJob::dispatch($importRecord, ProductUploadTypes::VIDEOS->value)->onQueue(
        config('horizon.default_queue_name')
    );

    expect($products->first()->getMedia('videos')->first())->toBeNull();
});

function importRecordFactory(): ImportRecord
{
    Storage::fake('public');

    $company = Company::factory()->create();

    $importRecord = ImportRecord::factory()->create([
        'type_id' => ImportTypes::PRODUCT_BULK_IMAGE_UPLOAD->value,
        'records_in_file' => 0,
        'records_imported' => 0,
        'records_failed' => 0,
        'header_columns' => [],
        'status' => Status::PENDING->value,
        'company_id' => $company->id,
    ]);

    $importRecord->copyMedia(__DIR__ . '/import-product-bulk-upload.zip')
        ->toMediaCollection('upload_file');

    $zipFileName = Carbon::now()->format('d-m-Y_H:i:s') . '.zip';

    Storage::disk('public')
        ->put('product_image_zip/' . $zipFileName, 'content of the pre-existing zip file');

    return $importRecord;
}
