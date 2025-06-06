<?php

declare(strict_types=1);

use App\Domains\Member\MemberQueries;
use App\Http\Controllers\StoreManager\MemberReportController;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated members report method returns proper response response and counts in store-manager panel',
    function (): void {
        $companyId = 1;
        $locationId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);
        setStoreManagerStoreIdInSession($locationId);
        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => 10,
            'location_ids' => null,
            'date_range' => [Carbon::now()->format('Y-m-d'), Carbon::now()->addMonth()->format('Y-m-d')],
        ];

        $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedMemberReportForStoreManager')
                ->once()
                ->andReturn([
                    'total_members' => 50,
                    'member_data' => new LengthAwarePaginator([], 50, 15),
                ]);
        });

        $memberReportController = new MemberReportController($memberQueries);

        $response = $memberReportController->fetchMembersReport(new Request($requestParameter));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the export members report and returns a proper response in store-manager panel.', function (): void {
    $companyId = 1;
    $locationId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);
    setStoreManagerStoreIdInSession($locationId);

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'desc',
        'location_ids' => null,
        'date_range' => [Carbon::now()->format('Y-m-d'), Carbon::now()->addMonth()->format('Y-m-d')],
        'export_columns' => null,
    ];

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getMembersReportForExportForStoreManager')
            ->once()
            ->andReturn(collect(new Member()));
    });

    $memberReportController = new MemberReportController($memberQueries);

    $response = $memberReportController->exportMembersReport('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the fetchMembersDetails method and returns a proper response at store-manager panel', function (): void {
    $companyId = 1;
    $locationId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);
    setStoreManagerStoreIdInSession($locationId);

    $requestParameter = [
        'select_date' => Carbon::now()->format('Y-m-d'),
        'select_store_id' => $locationId,
    ];

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('fetchMemberDetails')
        ->once()
        ->andReturn(collect(new Member()));
    });

    $memberReportController = new MemberReportController($memberQueries);

    $response = $memberReportController->fetchMembersDetails(new Request($requestParameter));
    $this->assertEquals(collect([]), $response['data']->resource);
});
