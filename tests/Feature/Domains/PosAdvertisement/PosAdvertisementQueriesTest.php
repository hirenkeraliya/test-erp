<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PosAdvertisement\DataObjects\PosAdvertisementData;
use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use App\Domains\PosAdvertisement\PosAdvertisementQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\PosAdvertisement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);

    $this->companyB = Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'DEFG',
    ]);

    $this->posAdvertisementA = PosAdvertisement::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => PosAdvertisementTypes::IMAGE->value,
    ]);

    $this->posAdvertisementB = PosAdvertisement::factory()->create([
        'company_id' => $this->companyB->id,
    ]);

    $this->posAdvertisementQueries = new PosAdvertisementQueries();
});

test('Pos advertisement are returned as per page', function (): void {
    $response = $this->posAdvertisementQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('type_id', $this->posAdvertisementA->type_id)
        ->toHaveKey('name', $this->posAdvertisementA->name);
});

test('It can store pos advertisement', function (): void {
    Storage::fake('public');

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $posAdvertisementData = [
        'type_id' => PosAdvertisementTypes::IMAGE->value,
        'photo' => $uploadedFile,
        'video' => null,
        'name' => 'test',
        'location_ids' => [$location->id],
        'status' => true,
    ];

    $this->posAdvertisementQueries->addNew(new PosAdvertisementData(...$posAdvertisementData), $this->companyA->id);
    unset($posAdvertisementData['photo']);
    unset($posAdvertisementData['video']);
    unset($posAdvertisementData['location_ids']);

    $this->assertDatabaseHas('pos_advertisements', $posAdvertisementData);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::POS_ADVERTISEMENT->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('It can update pos advertisement', function (): void {
    Storage::fake('public');

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $posAdvertisementData = [
        'type_id' => PosAdvertisementTypes::IMAGE->value,
        'photo' => $uploadedFile,
        'video' => null,
        'name' => 'test1',
        'location_ids' => [$location->id],
        'status' => true,
    ];

    $this->posAdvertisementQueries->update(
        new PosAdvertisementData(...$posAdvertisementData),
        $this->posAdvertisementA->id,
        $this->companyA->id
    );

    unset($posAdvertisementData['photo']);
    unset($posAdvertisementData['video']);
    unset($posAdvertisementData['location_ids']);

    $this->assertDatabaseHas('pos_advertisements', $posAdvertisementData);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::POS_ADVERTISEMENT->name,
        'collection_name' => 'photo',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('admin can change the status of the pos advertisement', function (): void {
    $this->posAdvertisementQueries->adminSetStatus($this->posAdvertisementA->id, $this->companyA->id, false);

    $this->assertDatabaseHas('pos_advertisements', [
        'id' => $this->posAdvertisementA->id,
        'status' => false,
    ]);
});

test('getPosAdvertisementExport method returns pos advertisement as expected', function (): void {
    $response = $this->posAdvertisementQueries->getPosAdvertisementExport([
        'search_text' => $this->posAdvertisementA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->posAdvertisementA->id)
        ->toHaveKey('name', $this->posAdvertisementA->name);
});
