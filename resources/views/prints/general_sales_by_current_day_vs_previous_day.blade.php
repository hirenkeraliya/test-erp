<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>General Sales</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="General Sales Report" :reportType="$reportType" :filterBy="$filterBy" :date="$selectedDate" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    @foreach ($locationSales as $brandName => $locationSale)
        <h3> {{ $brandName }} </h3>

        <table class="table table-bordered">
            <thead>
                <tr class="bordered">
                    @foreach ($mainColumns as $key => $mainColumn)
                        @if ($key === 'Sales As At Yesterday')
                            <td colspan="{{ count($previousDates) }}" style="width: 15%;" class="text-center"> {{ $mainColumn }} </td>
                        @elseif ($key === '% As At Yesterday')
                            <td colspan="{{ count($yearComparisons) }}" style="width: 15%;" class="text-center bordered"> {{ $mainColumn }} </td>
                        @elseif ($key === 'store')
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
                @foreach ($locationSale as $sale)
                    <tr class="bordered">
                        @foreach ($columns as $key => $column)
                            @if ($key === 'previous_date')
                                @foreach ($previousDates as $previousDate)
                                    <td class="{{ array_key_exists('region_name', $sale) ? 'text-right text-bold' : 'text-right' }}">
                                        {{ $sale['previous_date'][$previousDate] }}
                                    </td>
                                @endforeach
                            @elseif ($key === 'year_comparison')
                                @foreach ($yearComparisons as $yearComparison)
                                    <td class="{{ array_key_exists('region_name', $sale) ? 'text-right text-bold' : 'text-right' }}">
                                        {{ $sale['year_comparison'][$yearComparison] }}
                                    </td>
                                @endforeach
                            @elseif ((array_key_exists('location_name', $sale) || array_key_exists('region_name', $sale)) && $key === 'location_name')
                                <td class="{{ array_key_exists('region_name', $sale) ? 'text-left text-bold' : 'text-left' }}"> {{ array_key_exists('region_name', $sale) ? $sale['region_name'] : $sale['location_name'] }} </td>
                            @elseif (array_key_exists($key, $sale))
                                <td class="{{ array_key_exists('region_name', $sale) ? 'text-right text-bold' : 'text-right' }}">
                                    {{ $sale[$key] }}
                                </td>
                            @endIf
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @if (count($grandTotals['year_comparison']) > 0)
        <table class="table table-bordered">
            <thead>
                    <tr class="bordered">
                        @foreach ($mainColumns as $key => $mainColumn)
                            @if ($key === 'Sales As At Yesterday')
                                <td colspan="{{ count($previousDates) }}" style="width: 15%;" class="text-center"> {{ $mainColumn }} </td>
                            @elseif ($key === '% As At Yesterday')
                                <td colspan="{{ count($yearComparisons) }}" style="width: 15%;" class="text-center bordered"> {{ $mainColumn }} </td>
                            @elseif ($key === 'store')
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
                                    {{ $grandTotals['previous_date'][$previousDate] }}
                                </td>
                            @endforeach
                        @elseif ($key === 'year_comparison')
                            @foreach ($yearComparisons as $yearComparison)
                                <td class="text-right text-bold">
                                    {{ $grandTotals['year_comparison'][$yearComparison] }}
                                </td>
                            @endforeach
                        @elseif ($key === 'location_name')
                            <td class="text-left text-bold"> {{ $grandTotals['location_name'] }} </td>
                        @elseif (array_key_exists($key, $grandTotals))
                            <td class="text-right text-bold">
                                {{ $grandTotals[$key] }}
                            </td>
                        @endIf
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif

</body>

</html>
