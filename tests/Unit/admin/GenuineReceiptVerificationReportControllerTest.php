<?php

declare(strict_types=1);

use App\Domains\GenuineReceiptVerification\GenuineReceiptVerificationQueries;
use App\Domains\GenuineReceiptVerification\Services\ReceiptVerificationReportService;
use App\Http\Controllers\Admin\GenuineReceiptVerificationReportController;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('fetchReceiptVerificationReports method returns an array.', function (): void {
    setCompanyIdInSession(1);
    $request = Request::create('/admin/fetch-receipt-verification-reports', 'GET', [
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
        'location_ids' => [],
        'date_range' => [],
    ]);

    $this->mock(GenuineReceiptVerificationQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedReceiptVerificationReport')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $genuineReceiptVerificationReportController = new GenuineReceiptVerificationReportController();
    $response = $genuineReceiptVerificationReportController->fetchReceiptVerificationReports($request);

    expect($response)->toBeArray();
});

test('It calls the exportReceiptsVerificationReport method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => [],
        'date_range' => [],
        'is_genuine' => true,
        'export_columns' => null,
    ];

    $this->mock(GenuineReceiptVerificationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getReceiptVerificationReportDataForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new Collection([]));
    });

    $genuineReceiptVerificationReportController = new GenuineReceiptVerificationReportController();
    $response = $genuineReceiptVerificationReportController->exportReceiptsVerificationReport(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('the printReceiptVerifications method and returns the string',
    function (): void {
        setCompanyIdInSession();

        $this->mock(ReceiptVerificationReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $genuineReceiptVerificationReportController = new GenuineReceiptVerificationReportController();

        $response = $genuineReceiptVerificationReportController->printReceiptVerifications(new Request());
        expect($response)->toBeString();
    }
);
