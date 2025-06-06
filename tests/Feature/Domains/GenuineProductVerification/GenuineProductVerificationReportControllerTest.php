<?php

declare(strict_types=1);

use App\Domains\GenuineProductVerification\GenuineProductVerificationQueries;
use App\Http\Controllers\Admin\GenuineProductVerificationReportController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Response;

beforeEach(function (): void {
    $this->controller = new GenuineProductVerificationReportController();
    $this->genuineProductVerificationQueries = $this->createMock(GenuineProductVerificationQueries::class);
});

test('productVerificationReports method returns a Response instance.', function (): void {
    setCompanyIdInSession();
    $request = Request::create('/admin/product-verification-reports', 'GET');
    $response = $this->controller->productVerificationReports($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('fetchProductVerificationReports method returns an array.', function (): void {
    setCompanyIdInSession(1);
    $request = Request::create('/admin/fetch-product-verification-reports', 'GET', [
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
        'product_id' => null,
        'location_ids' => [],
        'date_range' => [],
    ]);

    $this->mock(GenuineProductVerificationQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedProductVerificationReport')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $controller = new GenuineProductVerificationReportController();
    $response = $controller->fetchProductVerificationReports($request);

    expect($response)->toBeArray();
});
