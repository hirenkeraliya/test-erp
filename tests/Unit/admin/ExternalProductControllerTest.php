<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\ExternalProduct\Resources\AdminExternalProductListResource;
use App\Http\Controllers\Admin\ExternalProductController;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ExternalProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
        'default_country_id' => 1,
    ]);

    setCompanyIdInSession($this->company->id);

    $this->admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
});

test(
    'It calls the List query method of the external product queries class and returns proper response',
    function (): void {
        $companyId = $this->company->id;

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'status' => null,
            'date_range' => 'null',
        ];

        $externalProductQueries = $this->mock(ExternalProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $externalProductController = new ExternalProductController($externalProductQueries);

        $response = $externalProductController->fetchExternalProducts(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(AdminExternalProductListResource::collection(collect([])), $response['data']);
    }
);

test('It calls the approved method then approve external product as expected', function (): void {
    $externalProduct = ExternalProduct::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'external_company_id' => 1,
        'status' => ExternalProductStatuses::PENDING->value,
        'approved_by_id' => $this->admin->id,
        'approved_by_type' => ModelMapping::ADMIN->name,
    ]);

    $request = new Request([
        'selectedRecords' => [$externalProduct->id],
    ]);

    $request->setUserResolver(fn (): Admin => $this->admin);

    $externalProductQueries = $this->mock(ExternalProductQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsApproved')
            ->once();
    });

    $externalProductController = new ExternalProductController($externalProductQueries);
    $redirectResponse = $externalProductController->approved($request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product approved successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/external-products', $redirectResponse->getTargetUrl());
});

test('It calls the rejected method then reject external product as expected', function (): void {
    $externalProduct = ExternalProduct::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'external_company_id' => 1,
        'status' => ExternalProductStatuses::PENDING->value,
        'approved_by_id' => $this->admin->id,
        'approved_by_type' => ModelMapping::ADMIN->name,
    ]);

    $request = new Request([
        'selectedRecords' => [$externalProduct->id],
    ]);

    $request->setUserResolver(fn (): Admin => $this->admin);

    $externalProductQueries = $this->mock(ExternalProductQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsRejected')
            ->once();
    });

    $externalProductController = new ExternalProductController($externalProductQueries);
    $redirectResponse = $externalProductController->rejected($request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Product rejected successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/external-products', $redirectResponse->getTargetUrl());
});
