<?php

declare(strict_types=1);

use App\Domains\Batch\BatchQueries;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'has_batch' => true,
    ]);
    $this->batch = Batch::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
    ]);

    $this->batchQueries = new BatchQueries();
});

test('It returns the batch id that matches with given parameters.', function (): void {
    $goodsReceivedNoteProduct = [
        'batch_number' => $this->batch->number,
        'batch_expiry_date' => $this->batch->expiry_date,
        'batch_notes' => null,
        'batch_external_id' => null,
    ];

    $response = $this->batchQueries->addNewAndGetId($goodsReceivedNoteProduct, $this->company->id, $this->product->id);

    expect($response)->toBe($this->batch->id);
});

test('It creates a new batch if the given parameters do not match', function (): void {
    $goodsReceivedNoteProduct = [
        'batch_number' => 'abc12312',
        'batch_expiry_date' => '1999-10-10',
        'batch_notes' => 'test_notes',
        'batch_external_id' => '123',
    ];

    $this->batchQueries->addNewAndGetId($goodsReceivedNoteProduct, $this->company->id, $this->product->id);

    $this->assertDatabaseHas('batches', [
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'number' => $goodsReceivedNoteProduct['batch_number'],
        'expiry_date' => $goodsReceivedNoteProduct['batch_expiry_date'],
        'notes' => $goodsReceivedNoteProduct['batch_notes'],
        'external_id' => $goodsReceivedNoteProduct['batch_external_id'],
    ]);
});

test('It returns batches by numbers', function (): void {
    $response = $this->batchQueries->getByNumbers([$this->batch->number], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->batch->id)
        ->toHaveKey('product_id', $this->batch->product_id)
        ->toHaveKey('number', $this->batch->number)
        ->toHaveKey('expiry_date', $this->batch->expiry_date);
});

test('It returns batch by number', function (): void {
    $response = $this->batchQueries->getByNumber($this->batch->number, $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->batch->id)
        ->toHaveKey('product_id', $this->batch->product_id)
        ->toHaveKey('number', $this->batch->number)
        ->toHaveKey('expiry_date', $this->batch->expiry_date);
});

test('It returns batches by product ids', function (): void {
    $response = $this->batchQueries->getByProductIds([$this->product->id], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->batch->id)
        ->toHaveKey('product_id', $this->batch->product_id)
        ->toHaveKey('number', $this->batch->number);
});

test('addNew method returns the batch that matches with given parameters.', function (): void {
    $response = $this->batchQueries->addNew(
        $this->company->id,
        $this->product->id,
        $this->batch->number,
        $this->batch->expiry_date
    );
    expect($response->toArray())
        ->toHaveKey('id', $this->batch->id)
        ->toHaveKey('product_id', $this->batch->product_id)
        ->toHaveKey('number', $this->batch->number);
});

test('addNew method creates a new batch if the given parameters do not match.', function (): void {
    $response = $this->batchQueries->addNew($this->company->id, $this->product->id, 'abc12312xyz', '1999-10-10');

    expect($response->toArray())
        ->toHaveKey('product_id', $this->product->id)
        ->toHaveKey('number', 'abc12312xyz')
        ->toHaveKey('expiry_date', '1999-10-10');
});

test('if product is merged then the product id is updated', function (): void {
    $productAId = Product::factory()->create()->id;

    $this->batchQueries->updateProductId($this->company->id, $this->product->id, $productAId);

    $this->assertDatabaseHas(Batch::class, [
        'company_id' => $this->company->id,
        'product_id' => $productAId,
    ]);
});

test('batch expiry list report', function (): void {
    $batch = Batch::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'expiry_date' => now()->format('Y-m-d'),
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 10,
        'category_id' => null,
        'brand_id' => null,
        'location_id' => null,
        'tag_ids' => null,
        'date_range' => null,
    ];

    $response = $this->batchQueries->batchExpiryReportList($filterData, $this->company->id);

    expect($response->first()->toArray())->toMatchArray([
        'expiry_date' => $this->batch->expiry_date,
        'number' => $this->batch->number,
    ]);

    expect($response->last()->toArray())->toMatchArray([
        'expiry_date' => $batch->expiry_date,
        'number' => $batch->number,
    ]);

    expect($response->first()->toArray())
        ->toHaveKeys(['product', 'product.brand', 'product.categories', 'inventory_unit']);
});

test('export batch expiry report', function (): void {
    $batch = Batch::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'expiry_date' => now()->format('Y-m-d'),
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'category_id' => null,
        'brand_id' => null,
        'location_id' => null,
        'tag_ids' => null,
        'date_range' => null,
    ];

    $response = $this->batchQueries->batchExpiryReportForExport($filterData, $this->company->id);

    expect($response->first()->toArray())->toMatchArray([
        'expiry_date' => $this->batch->expiry_date,
        'number' => $this->batch->number,
    ]);

    expect($response->last()->toArray())->toMatchArray([
        'expiry_date' => $batch->expiry_date,
        'number' => $batch->number,
    ]);

    expect($response->first()->toArray())
        ->toHaveKeys(['product', 'product.brand', 'product.categories', 'inventory_unit']);
});
