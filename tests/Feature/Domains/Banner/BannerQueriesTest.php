<?php

declare(strict_types=1);

use App\Domains\Banner\BannerQueries;
use App\Domains\Banner\DataObjects\BannerData;
use App\Domains\Common\Enums\ModelMapping;
use App\Models\Banner;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->bannerA = Banner::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test',
        'description' => 'Test',
        'action_type_id' => 1,
        'status' => true,
    ]);

    $this->bannerB = Banner::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'description' => 'Test',
        'action_type_id' => 2,
        'custom_url' => 'Test',
        'status' => true,
    ]);

    $this->bannerQueries = new BannerQueries();
});

test('Banner can be searched', function (): void {
    $response = $this->bannerQueries->listQuery([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->bannerB->name);
});

test('New banner can be added', function (): void {
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $bannerData = Banner::factory()->make()->toArray();
    $bannerData['image'] = $uploadedFile;
    $bannerData['company_id'] = $this->companyId;
    unset($bannerData['company_id']);
    $this->bannerQueries->addNew(new BannerData(...$bannerData), $this->companyId);
    unset($bannerData['image']);
    $this->assertDatabaseHas('banners', $bannerData);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::BANNER->name,
        'collection_name' => 'banner',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test("Banner are returned as per admin's company", function (): void {
    $response = $this->bannerQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
    ->toHaveKey('name', $this->bannerB->name)
    ->toHaveKey('description', $this->bannerB->description)
    ->toHaveKey('action_type_id', $this->bannerB->action_type_id)
    ->toHaveKey('custom_url', $this->bannerB->custom_url)
    ->toHaveKey('status', $this->bannerB->status);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->bannerA->name)
        ->toHaveKey('description', $this->bannerA->description)
        ->toHaveKey('action_type_id', $this->bannerA->action_type_id)
        ->toHaveKey('custom_url', $this->bannerA->custom_url)
        ->toHaveKey('status', $this->bannerA->status);
});

test('It can return banner', function (): void {
    $response = $this->bannerQueries->getById($this->bannerA->id, $this->companyId);

    $banner = $this->bannerA->load('media');

    unset($banner['updated_at'], $banner['created_at']);

    $this->assertEquals($banner->toArray(), $response->toArray());
});

test('A banner can be updated', function (): void {
    $bannerData = Banner::factory()->make([
        'name' => 'TestingUpdate',
        'description' => 'TestingUpdateDesc',
        'action_type_id' => 1,
        'custom_url' => null,
        'status' => true,
    ])->toArray();
    $bannerData['image'] = UploadedFile::fake()->image('avatar.jpg');
    $bannerData['company_id'] = $this->companyId;
    unset($bannerData['company_id']);

    $this->bannerQueries->update(new BannerData(...$bannerData), $this->bannerB->id, $this->companyId);
    unset($bannerData['image']);
    $this->assertDatabaseHas('banners', $bannerData);
});

test('Can change the status of the banner', function (): void {
    $this->bannerQueries->updateStatus($this->bannerA->id, $this->companyId, false);

    $this->assertDatabaseHas('banners', [
        'id' => $this->bannerA->id,
        'status' => false,
    ]);
});

test('call getListInEcommerce method and return active banners for ecommerce', function (): void {
    $response = $this->bannerQueries->getListInEcommerce([
        'search_text' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->bannerA->name)
        ->toHaveKey('description', $this->bannerA->description)
        ->toHaveKey('action_type_id', $this->bannerA->action_type_id)
        ->toHaveKey('custom_url', $this->bannerA->custom_url)
        ->toHaveKey('status', $this->bannerA->status);
});
