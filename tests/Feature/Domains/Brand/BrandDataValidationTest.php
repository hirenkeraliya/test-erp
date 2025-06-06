<?php

declare(strict_types=1);

use App\Domains\Brand\DataObjects\BrandData;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

test('brand validation works', function (): void {
    $request = new Request([
        'name' => '',
        'code' => '',
    ]);

    BrandData::validate($request);
})->throws(ValidationException::class);

test('unique name validation fails as expected while adding a brand', function (): void {
    Brand::factory()->create([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);

    $request = new Request([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);

    BrandData::validate($request);
})->throws(ValidationException::class);

test('unique name validation fails as expected while updating a brand', function (): void {
    $brandA = Brand::factory()->create([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);

    Brand::factory()->create([
        'name' => 'XYZ',
        'code' => 'XYZ',
    ]);

    $request = new Request([
        'name' => 'XYZ',
        'code' => 'XYZ',
    ], server: [
        'REQUEST_URI' => 'brands/' . $brandA->id . '/update',
    ]);

    $request->setRouteResolver(
        fn (): Route => (new Route(
            'Post',
            'brands/{brandId}/update',
            [
                'as' => 'super_admin.brands.update_brand',
                'uses' => [BrandController::class, 'update'],
            ]
        ))->bind($request)
    );

    $request->validate(BrandData::rules($request));
})->throws(ValidationException::class);

test('unique name validation works while updating a brand', function (): void {
    $brandA = Brand::factory()->create([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);

    $request = new Request([
        'name' => 'ABCD',
        'code' => 'XYZ',
    ], server: [
        'REQUEST_URI' => 'brands/' . $brandA->id . '/update',
    ]);

    $request->setRouteResolver(
        fn (): Route => (new Route(
            'Post',
            'brands/{brandId}/update',
            [
                'as' => 'super_admin.brands.update_brand',
                'uses' => [BrandController::class, 'update'],
            ]
        ))->bind($request)
    );

    $request->validate(BrandData::rules($request));

    $this->assertTrue(true);
});
