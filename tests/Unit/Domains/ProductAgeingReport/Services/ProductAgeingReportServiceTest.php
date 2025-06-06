<?php

declare(strict_types=1);

use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportToExcelJob;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\ExportRecord;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Queue;

test(
    'exportProductAgeingWithJob method call getProductAgeingExportCount method of ProductAgeingQueries class and response',
    function (): void {
        config()->set('app.excel.export.job_limit', '11');

        $this->mock(ProductAgeingQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductAgeingExportCount')
                ->once()
                ->andReturn(10);
        });

        $user = new User();

        $productAgeingReportService = new ProductAgeingReportService();
        $response = $productAgeingReportService->exportProductAgeingWithJob($user, [], 1, collect(['abc', 'def']));
        $this->assertEquals($response, [
            'exceeds_limit' => false,
        ]);
    }
);

test(
    'exportProductAgeingWithJob method call addNew method of ExportRecordQueries class and response',
    function (): void {
        Queue::fake();

        config()->set('app.excel.export.job_limit', '5');

        $this->mock(ProductAgeingQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductAgeingExportCount')
                ->once()
                ->andReturn(10);
        });

        $exportRecord = ExportRecord::factory()->make([
            'company_id' => 1,
            'created_by_id' => 1,
            'filters' => [],
        ]);

        $this->mock(ExportRecordQueries::class, function ($mock) use ($exportRecord): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($exportRecord);
        });

        $user = new User();

        $productAgeingReportService = new ProductAgeingReportService();
        $response = $productAgeingReportService->exportProductAgeingWithJob($user, [], 1, collect(['abc', 'def']));
        $this->assertEquals($response, [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ]);
        Queue::assertPushed(ExportToExcelJob::class);
    }
);

test(
    'exportProductAgeingByMonthAndYearWithJob method call getProductAgeingByMonthAndYearExportCount method of ProductAgeingQueries class and response',
    function (): void {
        config()->set('app.excel.export.job_limit', '11');

        $this->mock(ProductAgeingQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductAgeingByMonthAndYearExportCount')
                ->once()
                ->andReturn(10);
        });

        $user = new User();

        $productAgeingReportService = new ProductAgeingReportService();
        $response = $productAgeingReportService->exportProductAgeingByMonthAndYearWithJob(
            $user,
            [],
            1,
            collect(['abc', 'def'])
        );
        $this->assertEquals($response, [
            'exceeds_limit' => false,
        ]);
    }
);

test(
    'exportProductAgeingByMonthAndYearWithJob method call addNew method of ExportRecordQueries class and response',
    function (): void {
        Queue::fake();

        config()->set('app.excel.export.job_limit', '5');

        $this->mock(ProductAgeingQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductAgeingByMonthAndYearExportCount')
                ->once()
                ->andReturn(10);
        });

        $exportRecord = ExportRecord::factory()->make([
            'company_id' => 1,
            'created_by_id' => 1,
            'filters' => [],
        ]);

        $this->mock(ExportRecordQueries::class, function ($mock) use ($exportRecord): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($exportRecord);
        });

        $user = new User();

        $productAgeingReportService = new ProductAgeingReportService();
        $response = $productAgeingReportService->exportProductAgeingByMonthAndYearWithJob(
            $user,
            [],
            1,
            collect(['abc', 'def'])
        );
        $this->assertEquals($response, [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ]);
        Queue::assertPushed(ExportToExcelJob::class);
    }
);
