<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Collection by Time</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Sales Collection Report" reportType="by Time" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    @foreach($locationsSales as $locationSales)
        <p> Location : <strong> {{ $locationSales['location_name'] }} </strong> </p>

        <table class="table table-bordered bordered">
            <thead >
                <tr>
                    @foreach($columns as $column)
                        @if($column === 'Time')
                            <th class="text-center" width="100">{{ $column }}</th>
                        @else
                            <th class="text-center mt-2">{{ $column }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($locationSales['sales'] as $locationSale)
                    <tr class="page-break-inside-avoid">
                        @foreach($columns as $column)
                            @if(array_key_exists(strtolower(str_replace(' ', '_', $column)), $locationSale))
                                @if($column === 'Time')
                                    <td class="text-left">
                                        {{ $locationSale[strtolower(str_replace(' ', '_', $column))]}}
                                    </td>
                                @elseif($column === 'Orders')
                                    <td class="text-center">
                                        {{ $locationSale[strtolower(str_replace(' ', '_', $column))]}}
                                    </td>
                                @else
                                    <td class="text-right">
                                        @currencyFormat((float)$locationSale[strtolower(str_replace(' ', '_', $column))])
                                    </td>
                                @endif
                            @else
                                <td class="text-right">
                                    @currencyFormat(0.00)
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                    </tr>
                @endforelse
                @if (isset($locationSales['totals']))
                    <tr class="page-break-inside-avoid text-bold">
                        @foreach($columns as $column)
                            @if(array_key_exists(strtolower(str_replace(' ', '_', $column)), $locationSales['totals']))
                                @if($column === 'Time')
                                    <td class="text-left">
                                        {{ $locationSales['totals'][strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @elseif($column === 'Orders')
                                    <td class="text-center">
                                        {{ $locationSales['totals'][strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @else
                                    <td class="text-right">
                                        @currencyFormat((float) $locationSales['totals'][strtolower(str_replace(' ', '_', $column))])
                                    </td>
                                @endif
                            @else
                                <td class="text-right">
                                    @currencyFormat(0.00)
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="text-left" style="line-height: 1px !important;">
            <p><b>Collection : @currencyFormat($locationSales['totals']['collection'] ?? 0) </b></p>
            <p><b>- Rounding Adjust : @currencyFormat($locationSale['roundingAdjust'] ?? 0)</b></p>
            <p><b>- GST : @currencyFormat($locationSales['totalTaxAmount'] ?? 0)</b></p>
        </div>

        <div>
            <p>
                <b>Average Sales per Receipt : {{ count($locationSales['sales']) !== 0 && $locationSales['totals']['orders'] !== 0 ? round(($locationSales['totals']['collection']/ $locationSales['totals']['orders']), 2) : 0 }}</b>
            </p>
        </div>
    @endforeach
</body>
</html>
