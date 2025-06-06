<?php

declare(strict_types=1);

use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\ComplimentaryItemReason\DataObjects\ComplimentaryItemReasonData;
use App\Http\Controllers\Admin\ComplimentaryItemReasonController;
use App\Models\ComplimentaryItemReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the ComplimentaryItemReason queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'reason',
            'sort_direction' => 'desc',
            'per_page' => 1,
        ];

        $complimentaryItemReasonQueries = $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $complimentaryItemReasonController = new ComplimentaryItemReasonController($complimentaryItemReasonQueries);

        $response = $complimentaryItemReasonController->fetchComplimentaryItemReasons(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        expect($response['data'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test(
    'It calls the addNew method of the complimentary item reason queries class and returns proper response',
    function (): void {
        $complimentaryItemReasonRecord = [
            'reason' => 'Test Complimentary Item Reason',
        ];

        setCompanyIdInSession();

        $complimentaryItemReasonData = new ComplimentaryItemReasonData(...$complimentaryItemReasonRecord);

        $complimentaryItemReasonQueries = $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use (
            $complimentaryItemReasonData
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($complimentaryItemReasonData, 1);
        });

        $complimentaryItemReasonController = new ComplimentaryItemReasonController($complimentaryItemReasonQueries);
        $redirectResponse = $complimentaryItemReasonController->store($complimentaryItemReasonData);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'The reason for adding the complimentary item has been successfully added.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/complimentary-item-reasons', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the get by id method of the complimentary item reason queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $complimentaryItemReasonRecord = [
            'reason' => 'Test Complimentary Item Reason',
        ];

        $complimentaryItemReasonQueries = $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use (
            $complimentaryItemReasonRecord,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new ComplimentaryItemReason($complimentaryItemReasonRecord));
        });

        $complimentaryItemReasonController = new ComplimentaryItemReasonController($complimentaryItemReasonQueries);
        $response = $complimentaryItemReasonController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'complimentaryItemReason',
            fn (Assert $complimentaryItemReason): Assert => $complimentaryItemReason->where(
                'reason',
                'Test Complimentary Item Reason'
            )
        )
        );
    }
);

test(
    'It calls the update method of the ComplimentaryItemReason queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $complimentaryItemReasonRecord = [
            'reason' => 'Test Complimentary Item Reason',
        ];

        $complimentaryItemReasonData = new ComplimentaryItemReasonData(...$complimentaryItemReasonRecord);

        $complimentaryItemReasonQueries = $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use (
            $complimentaryItemReasonData,
            $companyId
        ): void {
            $mock->shouldReceive('update')
                ->once()
                ->with($complimentaryItemReasonData, 1, $companyId);
        });

        $complimentaryItemReasonController = new ComplimentaryItemReasonController($complimentaryItemReasonQueries);
        $redirectResponse = $complimentaryItemReasonController->update($complimentaryItemReasonData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'The reason for the complimentary item has been updated successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/complimentary-item-reasons', $redirectResponse->getTargetUrl());
    }
);

test('It calls the exportComplimentaryItemReasons method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $complimentaryItemReasonQueries = $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getComplimentaryItemReasonsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new ComplimentaryItemReason()));
    });

    $complimentaryItemReasonController = new ComplimentaryItemReasonController($complimentaryItemReasonQueries);

    $response = $complimentaryItemReasonController->exportComplimentaryItemReasons(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
