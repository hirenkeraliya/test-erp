<?php

declare(strict_types=1);

use App\Domains\UnitOfMeasureDerivative\DataObjects\UnitOfMeasureDerivativeData;
use App\Http\Controllers\Admin\UnitOfMeasureDerivativeController;
use App\Models\Company;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

test('unit of measure wise unique name validation works while adding', function (): void {
    $companyId = Company::factory()->create()->id;

    $unitOfMeasure = UnitOfMeasure::factory()->create([
        'company_id' => $companyId,
    ]);

    setCompanyIdInSession($companyId);

    UnitOfMeasureDerivative::factory()->create([
        'unit_of_measure_id' => $unitOfMeasure->id,
        'name' => 'derivative',
    ]);

    $request = new Request([
        'name' => 'derivative',
        'ratio' => 1,
    ], server: [
        'REQUEST_URI' => 'unit-of-measures/' . (int) $unitOfMeasure->id . '/derivatives',
    ]);

    $request->setRouteResolver(
        fn (): Route => (new Route(
            'Post',
            'unit-of-measures/{unitOfMeasureId}/derivatives',
            [
                'as' => 'admin.unit_of_measure_derivatives.store',
                'uses' => [UnitOfMeasureDerivativeController::class, 'store'],
            ]
        ))->bind($request)
    );

    $request->validate(UnitOfMeasureDerivativeData::rules($request));
})->throws(ValidationException::class);

test('derivative with the same name can be added if unit of measure is different', function (): void {
    $companyId = Company::factory()->create()->id;
    $unitOfMeasureFirst = UnitOfMeasure::factory()->create([
        'company_id' => $companyId,
    ]);
    $unitOfMeasureSecond = UnitOfMeasure::factory()->create([
        'company_id' => $companyId,
    ]);
    setCompanyIdInSession($companyId);

    UnitOfMeasureDerivative::factory()->create([
        'unit_of_measure_id' => $unitOfMeasureFirst->id,
        'name' => 'derivative',
    ]);

    $request = new Request([
        'name' => 'derivative',
        'ratio' => 1,
    ], server: [
        'REQUEST_URI' => 'unit-of-measures/' . (int) $unitOfMeasureSecond->id . '/derivatives',
    ]);

    $request->setRouteResolver(
        fn (): Route => (new Route(
            'Post',
            'unit-of-measures/{unitOfMeasureId}/derivatives',
            [
                'as' => 'admin.unit_of_measure_derivatives.store',
                'uses' => [UnitOfMeasureDerivativeController::class, 'store'],
            ]
        ))->bind($request)
    );

    $request->validate(UnitOfMeasureDerivativeData::rules($request));

    $this->assertTrue(true);
});
