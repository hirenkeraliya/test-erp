<!DOCTYPE html>
<html lang="en">

<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> {{ 'Stock Summary By Module' }} </title>
</head>

<body>
    @php
    $columnCount = count($sellThroughAggregate['location_codes']);
    if (isset($sellThroughAggregate[array_key_first($sellThroughAggregate)]['article_number'])) {
    $columnCount += 2;
    } else {
    $columnCount += 3;
    }
    @endphp
    <x-report-header :company="$company" reportName="Stock Summary By Module Report" reportType="By Sales" :dateRange="$dateRange" :date="$date" />

    @if($filteredLocation)
    <p>
        Locations:
        <strong>
            {{ $filteredLocation }}
        </strong>
    </p>
    @endif
    <table class="table table-bordered bordered">
        <thead>
            <tr>
                <th colspan="{{ $columnCount }}" class="text-center">{{ \App\Domains\StockSummary\Enums\StockSummaryByModuleReportBy::getFormattedCaseName($report_by) }}</th>
            </tr>
            <tr>
                <th>ITEM NAME</th>
                @if ($report_type === \App\Domains\StockSummary\Enums\StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value)
                <th>ARTICLE</th>
                @else
                    @if(config('app.product_variant'))
                        <th>ATTRIBUTE</th>
                    @else
                        <th>COLOR</th>
                        <th>SIZE</th>
                    @endif
                @endif

                @foreach ($sellThroughAggregate['location_codes'] as $locationCode)
                <th>{{ $locationCode }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (is_null($sellThroughAggregate) || empty(array_filter($sellThroughAggregate, fn($key) => $key !== 'location_codes', ARRAY_FILTER_USE_KEY)))
                <tr>
                    <td colspan="{{ $columnCount }}" class="text-center">No records</td>
                </tr>
            @else
                @foreach ($sellThroughAggregate as $key => $data)
                @if ($key !== 'location_codes')
                    <tr>
                        <td>{{ $data['product_name'] }}</td>
                        @if ($report_type === \App\Domains\StockSummary\Enums\StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value)
                            <td>{{ $data['article_number'] }}</td>
                        @else
                            @if(config('app.product_variant'))
                                <td>{{ $data['attributes'] }}</td>
                            @else
                                <td>{{ $data['color_name'] }}</td>
                                <td>{{ $data['size_name'] }}</td>
                            @endif
                        @endif
                        @foreach ($sellThroughAggregate['location_codes'] as $locationCode)
                            <td>{{ $data['locations'][$locationCode] ?? 0 }}</td>
                        @endforeach
                    </tr>
                @endif
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ ($report_type === \App\Domains\StockSummary\Enums\StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value || config('app.product_variant')) ? 2 : 3 }}" class="text-center"><strong>Grand Total</strong></td>
                @foreach ($grandTotals['totals'] as $total)
                <td><strong>{{ $total }}</strong></td>
                @endforeach
            </tr>
        </tfoot>
    </table>
</body>

</html>
