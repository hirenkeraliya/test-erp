<?php

declare(strict_types=1);

use App\Domains\CustomReport\DataObjects\StockSummaryByModuleReportData;
use Illuminate\Support\Facades\Validator;

it('validates StockSummaryByModuleReportData successfully with valid data', function (): void {
    $data = [
        'report_by' => 1,
        'report_type' => 2,
        'location_ids' => ['loc1', 'loc2'],
        'date_range' => ['2023-01-01', '2023-01-31'],
        'article_number' => ['A123', 'B456'],
    ];

    $rules = StockSummaryByModuleReportData::rules();

    $validator = Validator::make($data, $rules);

    expect($validator->passes())->toBeTrue();
});

it('fails validation for StockSummaryByModuleReportData with invalid data', function (): void {
    $data = [
        'report_by' => null,
        'report_type' => 'invalid',
        'location_ids' => 'not-an-array',
        'date_range' => [],
        'article_number' => null,
    ];

    $rules = StockSummaryByModuleReportData::rules();

    $validator = Validator::make($data, $rules);

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->toArray())->toHaveKeys([
        'report_by',
        'report_type',
        'location_ids',
        'date_range',
    ]);
});
