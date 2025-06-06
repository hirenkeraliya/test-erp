<?php

declare(strict_types=1);

use App\Domains\SaleThroughRatio\DataObjects\SaleThroughRatioData;
use App\Models\Company;
use App\Models\SaleThroughRatio;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->saleThroughRatio = SaleThroughRatio::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test',
    ]);
});

test(
    'company wise unique name validation works while adding a sale through ratio.',
    function (): void {
        setCompanyIdInSession($this->companyId);

        $saleThroughRatioDetails = SaleThroughRatio::factory()->make([
            'company_id' => $this->companyId,
            'name' => 'Test',
        ])->toArray();

        $request = new Request($saleThroughRatioDetails);

        $request->validate(SaleThroughRatioData::rules($request));
    }
)->throws(ValidationException::class);

test(
    'user can add different sale through ratio with same company.',
    function (): void {
        setCompanyIdInSession($this->companyId);

        $saleThroughRatioDetails = SaleThroughRatio::factory()->make([
            'company_id' => $this->companyId,
            'name' => 'XYZ',
        ])->toArray();

        $request = new Request($saleThroughRatioDetails, server: [
            'REQUEST_URI' => 'sales-store-ratios/' . $this->saleThroughRatio->id . '/update',
        ]);
        $request->setRouteResolver(
            fn (): Route => (new Route(
                'Post',
                'sales-store-ratios/{saleThroughRatioId}/update',
                [
                    'as' => 'admin.sale_through_ratios.update',
                    'uses' => [SaleThroughRatioController::class, 'update'],
                ]
            ))->bind($request)
        );

        $request->validate(SaleThroughRatioData::rules($request));
        $this->assertTrue(true);
    }
);

test(
    'user can add same sale through ratio with different company.',
    function (): void {
        $companyId = Company::factory()->create()->id;
        setCompanyIdInSession($companyId);

        $saleThroughRatioDetails = SaleThroughRatio::factory()->make([
            'name' => 'Test',
            'company_id' => $companyId,
        ])->toArray();

        $request = new Request($saleThroughRatioDetails);
        $request->validate(SaleThroughRatioData::rules($request));
        $this->assertTrue(true);
    }
);
