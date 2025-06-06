<?php

declare(strict_types=1);

use App\Domains\Location\DataObjects\LocationData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Store\Enums\StoreTimings;
use App\Http\Controllers\Admin\LocationController;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test Name',
        'code' => '123456',
        'phone' => '012345678911',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->brandId = Brand::factory()->create()->id;
});

function getLocationDetails(string $name, string $code, string $phone, int $brandId, int $companyId): array
{
    $location = Location::factory()->make([
        'name' => $name,
        'code' => $code,
        'email' => 'abcd@gmail.com',
        'phone' => (int) $phone,
        'company_id' => $companyId,
        'price_fall_down_percentage' => 50,
        'open_time' => StoreTimings::OPEN_TIME->value,
        'close_time' => StoreTimings::CLOSE_TIME->value,
        'type_id' => LocationTypes::STORE->value,
        'sales_tax_percentage' => 10,
        'sales_return_days_limit' => 10,
        'receipt_footer' => 'abc',
        'disclaimer' => 'def',
        'state_id' => 1,
        'city_id' => 1,
    ])->toArray();

    $location['location_type'] = LocationTypes::getFormattedCaseName(LocationTypes::STORE->value);
    $location['brand_ids'] = [$brandId];
    $location['country_id'] = 1;
    $location['price_fall_down_percentage'] = 10;

    return $location;
}

test(
    'company wise and Type wise unique name, phone and code validation works while adding.',
    function (string $name, string $code, string $phone): void {
        setCompanyIdInSession($this->companyId);

        $locationData = getLocationDetails($name, $code, $phone, $this->brandId, $this->companyId);
        $request = new Request($locationData);

        $request->validate(LocationData::rules($request));
    }
)->with([
    ['Test Name', '4555555', '159753468211'],
    ['XYZ', '123456', '123456789011'],
    ['WXYZ', '1234567', '012345678911'],
])->throws(ValidationException::class);

test(
    'unique name, phone and code with same company and type validation works while updating a location.',
    function (string $name, string $code, string $phone): void {
        setCompanyIdInSession($this->companyId);

        $locationDetails = getLocationDetails($name, $code, $phone, $this->brandId, $this->companyId);

        $request = new Request($locationDetails, server: [
            'REQUEST_URI' => 'locations/' . $this->location->id . '/update',
        ]);
        $request->setRouteResolver(
            fn (): Route => (new Route(
                'Post',
                'locations/{locationId}/update',
                [
                    'as' => 'admin.locations.update',
                    'uses' => [LocationController::class, 'update'],
                ]
            ))->bind($request)
        );

        $request->validate(LocationData::rules($request));
        $this->assertTrue(true);
    }
)->with([
    ['Test Name', '4555555', '159753468211'],
    ['XYZ', '123456', '123456789011'],
    ['WXYZ', '1234567', '012345678912'],
]);

test(
    'unique name, phone and code with different company validation works while updating.',
    function (string $name, string $code, string $phone): void {
        $companyId = Company::factory()->create()->id;

        setCompanyIdInSession($companyId);

        $locationDetails = getLocationDetails($name, $code, $phone, $this->brandId, $companyId);

        $request = new Request($locationDetails, server: [
            'REQUEST_URI' => 'locations/' . $this->location->id . '/update',
        ]);
        $request->setRouteResolver(
            fn (): Route => (new Route(
                'Post',
                'locations/{locationId}/update',
                [
                    'as' => 'admin.locations.update',
                    'uses' => [LocationController::class, 'update'],
                ]
            ))->bind($request)
        );

        $request->validate(LocationData::rules($request));
        $this->assertTrue(true);
    }
)->with([
    ['Test Name', '4555555', '159753468212'],
    ['XYZ', '123456', '123456789012'],
    ['WXYZ', '1234567', '012345678912'],
]);
