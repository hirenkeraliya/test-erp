<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>General Sales Summary Report</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="General Sales Report" :reportType="$reportType" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    @foreach ($locationSales as $locationSale)
        @if (array_key_exists('location_name', $locationSale))
            <p> Location : <strong> {{ $locationSale['location_name'] }} </strong> </p>

            <div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            @foreach($columns as $column)
                                <th class="text-center">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($locationSale['products'] as $product)
                            <tr class="page-break-inside-avoid">
                                @foreach($columns as $column)
                                    @if ($column === 'Date')
                                        <td class="text-left">{{ $product[strtolower(str_replace(' ', '_', $column))] }}</td>
                                    @elseif ($column === 'Items')
                                        <td class="text-right">
                                            @truncateDecimal($product[strtolower(str_replace(' ', '_', $column))])
                                        </td>
                                    @else
                                        <td class="text-right">
                                            @currencyFormat($product[strtolower(str_replace(' ', '_', $column))])
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-center"> No Record Found</td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tbody>
                        <tr>
                            <th>
                                Grand Total
                            </th>
                            @foreach ($locationSale['totals'] as $key => $totals)
                                @if ($key === 'totalItemSold')
                                    <th class="text-right">
                                        @truncateDecimal($totals)
                                    </th>
                                @elseif ($key === 'totalSalesAmount')
                                    <th class="text-right">
                                        @currencyFormat($totals)
                                    </th>
                                @endif

                            @endforeach
                            </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    <table class="table table-bordered">
        <caption class="caption-top font-extrabold">
            <strong>
                Grand Total:
            </strong>
        </caption>
        <thead>
            <tr>
                <th>
                </th>
                <th class="text-center">
                    Grand Total Items:
                </th>
                <th class="text-center">
                    Grand Sales:
                </th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <th></th>
                <th class="text-right">
                    @truncateDecimal($locationSales['grand_total']['totalItemSold'])
                </th>
                <th class="text-right">
                    @currencyFormat($locationSales['grand_total']['totalSalesAmount'])
                </th>
            </tr>
        </tbody>
    </table>
</body>

</html>
