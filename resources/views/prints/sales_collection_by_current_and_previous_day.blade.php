<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Collection Report</title>
</head>

<body class="arial-font-custom-report">
     <x-report-header :company="$company" reportName="Sales Collection Report" reportType="By Current Day Vs Previous Day" :filterBy="$filterBy" :date="$selectedDate" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif


    <table class="table table-bordered">
        <thead>
            <tr class="bordered">
                @foreach ($mainColumns as $mainColumn)
                    @if ($mainColumn === 'Sales As At Yesterday')
                        <td colspan="{{ count($previousDates) }}" style="width: 15%;" class="text-center"> {{ $mainColumn }} </td>
                    @elseif ($mainColumn === '% As At Yesterday')
                        <td colspan="{{ count($yearComparisons) }}" style="width: 15%;" class="text-center bordered"> {{ $mainColumn }} </td>
                    @elseif ($mainColumn === 'Location')
                        <td class="text-left bordered" style="width: 30%;"> {{ $mainColumn }} </td>
                    @else
                        <td class="text-right bordered" style="width: 15%;"> {{ $mainColumn }} </td>
                    @endif
                @endforeach
            </tr>
        </thead>

        <thead>
            <tr class="bordered">
                @foreach ($columns as $key => $column)
                    @if ($column === 'previous_date')
                        @foreach ($previousDates as $previousDate)
                            <td class="text-right">
                                @if ($previousDate)
                                    {{ datetime::createFromFormat('d-m-Y', $previousDate)->format('Y') }}
                                @endif
                            </td>
                        @endforeach
                    @elseif ($column === 'year_comparison')
                        @foreach ($yearComparisons as $yearComparison)
                            <td class="text-right">
                                {{ $yearComparison }}
                            </td>
                        @endforeach
                    @elseif ($key === 'location_name')
                        <td class="bordered"> {{ $column }} </td>
                    @else
                        <td class="text-right bordered">
                            @if ($column)
                                {{ datetime::createFromFormat('d-m-Y', $column)->format('Y') }}
                            @endif
                        </td>
                    @endIf
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($locationSales as $sale)
                <tr class="bordered">
                    @foreach ($columns as $key => $column)
                        @if ($key === 'previous_date')
                            @foreach ($previousDates as $previousDate)
                                <td class="{{ array_key_exists('region_name', $sale) ? 'text-right text-bold' : 'text-right' }}">
                                    @currencyFormat($sale['previous_date'][$previousDate])
                                </td>
                            @endforeach
                        @elseif ($key === 'year_comparison')
                            @foreach ($yearComparisons as $yearComparison)
                                <td class="{{ array_key_exists('region_name', $sale) ? 'text-right text-bold' : 'text-right' }}">
                                    @currencyFormat($sale['year_comparison'][$yearComparison] ?? 0)
                                </td>
                            @endforeach
                        @elseif ((array_key_exists('location_name', $sale) || array_key_exists('region_name', $sale)) && $key === 'location_name')
                            <td class="{{ array_key_exists('region_name', $sale) ? 'text-left text-bold' : 'text-left' }}">
                                {{ array_key_exists('region_name', $sale) ? $sale['region_name'] : $sale['location_name'] }}
                            </td>
                        @elseif (array_key_exists($key, $sale))
                            <td class="{{ array_key_exists('region_name', $sale) ? 'text-right text-bold' : 'text-right' }}">
                                @currencyFormat($sale[$key])
                            </td>
                        @endIf
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (count($grandTotals['year_comparison']) > 0)
        <table class="table table-bordered">
            <thead>
                <tr class="bordered">
                    @foreach ($mainColumns as $mainColumn)
                        @if ($mainColumn === 'Sales As At Yesterday')
                            <td colspan="{{ count($previousDates) }}" style="width: 15%;" class="text-center"> {{ $mainColumn }} </td>
                        @elseif ($mainColumn === '% As At Yesterday')
                            <td colspan="{{ count($yearComparisons) }}" style="width: 15%;" class="text-center bordered"> {{ $mainColumn }} </td>
                        @elseif ($mainColumn === 'Location')
                            <td class="text-left bordered" style="width: 30%;"> </td>
                        @else
                            <td class="text-right bordered" style="width: 15%;"> {{ $mainColumn }} </td>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <thead>
                <tr class="bordered">
                    @foreach ($columns as $key => $column)
                        @if ($column === 'previous_date')
                            @foreach ($previousDates as $previousDate)
                                <td class="text-right">
                                    @if ($previousDate)
                                        {{ datetime::createFromFormat('d-m-Y', $previousDate)->format('Y') }}
                                    @endif
                                </td>
                            @endforeach
                        @elseif ($column === 'year_comparison')
                            @foreach ($yearComparisons as $yearComparison)
                                <td class="text-right">
                                    {{ $yearComparison }}
                                </td>
                            @endforeach
                        @elseif ($key === 'location_name')
                            <td class="bordered">  </td>
                        @else
                            <td class="text-right bordered">
                                @if ($column)
                                    {{ datetime::createFromFormat('d-m-Y', $column)->format('Y') }}
                                @endif
                            </td>
                        @endIf
                    @endforeach
                </tr>
            </thead>

            <tbody>
                <tr class="bordered">
                    @foreach ($columns as $key => $column)
                        @if ($key === 'previous_date')
                            @foreach ($previousDates as $previousDate)
                                <td class="text-right text-bold">
                                    @currencyFormat($grandTotals['previous_date'][$previousDate])
                                </td>
                            @endforeach
                        @elseif ($key === 'year_comparison')
                            @foreach ($yearComparisons as $yearComparison)
                                <td class="text-right text-bold">
                                    @currencyFormat($grandTotals['year_comparison'][$yearComparison])
                                </td>
                            @endforeach
                        @elseif ($key === 'location_name')
                            <td class="text-left text-bold"> {{ $grandTotals['location_name'] }} </td>
                        @elseif (array_key_exists($key, $grandTotals))
                            <td class="text-right text-bold">
                                @currencyFormat($grandTotals[$key])
                            </td>
                        @endIf
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
