<?php

use App\Domains\Banner\DataObjects\BannerData;
use App\Domains\Banner\Enums\ActionTypes;
use App\Models\Banner;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->bannerA = Banner::factory()->create([
        'company_id' => $this->companyAId,
        'name' => '',
        'description' => 'ABC',
    ]);
    $this->bannerB = Banner::factory()->create([
        'company_id' => $this->companyBId,
        'name' => 'XYZ',
        'description' => 'XYZ',
        'action_type_id' => ActionTypes::CUSTOM_URL->value,
        'custom_url' => '',
        'status' => false,
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('Banner validation fails when required fields are empty', function (): void {
    Storage::fake('public');

    $fakeImage = UploadedFile::fake()->image('avatar.jpg');
    $request = [
        'name' => '',
        'description' => '',
        'status' => false,
        'commission_type_id' => ActionTypes::CUSTOM_URL->value,
        'custom_url' => null,
        'image' => $fakeImage,
    ];
    BannerData::validate($request);
})->throws(ValidationException::class);

test('Validation fails when name is missing while adding a banner', function (): void {
    $request = new Request([
        'description' => $this->bannerA->description,
    ]);

    BannerData::validate($request);
})->throws(ValidationException::class);

test('Custom URL is required when action type is ActionTypes::CUSTOM_URL', function (): void {
    $request = new Request([
        'name' => 'Banner Name',
        'description' => 'Banner Description',
        'action_type_id' => ActionTypes::CUSTOM_URL->value,
        'custom_url' => null,
        'status' => true,
    ]);

    BannerData::validate($request);
})->throws(ValidationException::class);
