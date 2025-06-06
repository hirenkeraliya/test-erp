<!DOCTYPE html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Analysis Report</title>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Sales Analysis Report</strong>
    </h4>

    <x-pdf-report-header :filterData="$filter_header_data" />

    <p>
        Sales Analysis from {{ $dateRange }}
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <h3>
        @if ($location)
            {{ $location->getNameWithCode() }}
        @else
            All Stores
        @endif
    </h3>

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th class="text-center">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($saleAnalysis as $saleThroughDataBySize)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ $saleThroughDataBySize['name'] }}
                    </td>

                    <td>
                        {{ $saleThroughDataBySize['upc'] }}
                    </td>

                    <td>
                        {{ $saleThroughDataBySize['article_number'] }}
                    </td>

                    @if(config('app.product_variant'))
                        <td> 
                            @foreach ( $saleThroughDataBySize['product_variant_values'] as $product_variant )
                                {{ $product_variant['attribute']['name'] }}: {{ $product_variant['value'] }}<br>
                            @endforeach
                        </td>
                    @else
                        <td class="text-right">
                            {{ $saleThroughDataBySize['color'] ? $saleThroughDataBySize['color']['name'] : 'N/A' }}
                        </td>

                        <td class="text-right">
                            {{ $saleThroughDataBySize['size'] ? $saleThroughDataBySize['size']['name'] : 'N/A' }}
                        </td>
                    @endif

                    <td class="text-right">
                        {{ $saleThroughDataBySize['sale_analysis_grade'] }}
                    </td>

                    <td class="text-right">
                        {{ $saleThroughDataBySize['total_units_sold'] }}
                    </td>

                    <td class="text-right">
                        {{ $saleThroughDataBySize['total_sales'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                </tr>
            @endforelse

            <tr class="page-break-inside-avoid text-bold">
                <td>
                    <b>
                        {{ $saleAnalysisTotals['name'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleAnalysisTotals['upc'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleAnalysisTotals['article_number'] }}
                    </b>
                </td>

                @if(config('app.product_variant'))
                    <td class="text-right">
                        <b>
                            {{ $saleAnalysisTotals['attributes'] }}
                        </b>
                    </td>
                @else
                    <td class="text-right">
                        <b>
                            {{ $saleAnalysisTotals['color'] }}
                        </b>
                    </td>

                    <td class="text-right">
                        <b>
                            {{ $saleAnalysisTotals['size'] }}
                        </b>
                    </td>
                @endif

                <td class="text-right">
                    <b>
                        {{ $saleAnalysisTotals['sale_analysis_grade'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        @currencyFormat($saleAnalysisTotals['total_units_sold'])
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleAnalysisTotals['total_sales'] }}
                    </b>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
