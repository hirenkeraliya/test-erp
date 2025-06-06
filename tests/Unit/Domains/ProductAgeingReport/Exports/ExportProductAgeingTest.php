<?php

declare(strict_types=1);

use App\Domains\ProductAgeingReport\Exports\ExportProductAgeing;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\ExportRecord;

test(
    'fetch method call exportProductAgeingRecords method of ProductAgeingReportService class',
    function (): void {
        $returnData = collect([]);
        $exportRecord = ExportRecord::factory()->make([
            'company_id' => 1,
            'created_by_id' => 1,
            'filters' => [],
        ]);

        $this->mock(ProductAgeingQueries::class, function ($mock) use ($returnData): void {
            $mock->shouldReceive('exportProductAgeingRecords')
                ->once()
                ->andReturn($returnData);
        });

        $this->mock(ProductAgeingReportService::class, function ($mock) use ($returnData): void {
            $mock->shouldReceive('preparedData')
                ->once()
                ->andReturn($returnData);
        });

        $exportProductAgeing = new ExportProductAgeing();
        $response = $exportProductAgeing->fetch($exportRecord, 10, 10);
        $this->assertEquals($returnData, $response);
    }
);
