<?php

declare(strict_types=1);

use App\Domains\Store\DataObjects\StoreSelectionData;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test Name',
        'code' => '123456',
        'phone' => '0123456789',
    ]);
});

test('it checks that store id exist in our records', function (): void {
    $request = new Request([
        'location_id' => $this->location->id,
    ]);

    StoreSelectionData::validate($request);
    $this->assertTrue(true);
});

test('it fails if that store id does not exist in our records', function (): void {
    $request = new Request([
        'location_id' => 3,
    ]);
    StoreSelectionData::validate($request);
})->throws(ValidationException::class);
