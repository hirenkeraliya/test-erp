<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Comparisons Of Seasonal Sales </title>

    <style>
        td {
            border: 1px solid;
        }

        th {
            border-top: 1px solid;
            border-left: 1px solid;
            border-right: 1px solid;
        }
    </style>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Details Of Seasonal Sales" :reportType="$reportType" :date="$date" :dateRange="$dateRange" />

    <table class="table" style="font-size: 8px;">
        @forelse ($seasonalSalesData as $seasonalSaleData)
            <thead>
                <tr>
                    <th colspan="{{ count($columns) }}" class="text-left" style="border: none; font-size:15px;">
                        <h4>
                            Brand: {{ $seasonalSaleData['brand_name'] }}
                        </h4>
                    </th>
                </tr>

                <tr>
                    @foreach ($columns as $column)
                        @if ($column === 'Location Name')
                            <th>
                                {{ $column }}
                            </th>
                        @elseif ($column === 'total')
                            <th>
                                Total {{ $saleSeasonName }}
                            </th>
                        @elseif ($column === 'total_compare')
                            <th>
                                Total {{ $compareSaleSeasonName }}
                            </th>
                        @elseif ($column === '%')
                            <th>
                                Comparison To Date %
                            </th>
                        @else
                            <th class="text-right">
                                {{ $column }}
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($seasonalSaleData['locations'] as $saleData)
                    <tr class="page-break-inside-avoid">
                        @foreach ($columns as $key => $column)
                            @if ($column === 'Location Name')
                                <td style="width: 50px;">{{ $saleData['location_name'] }}</td>
                            @elseif ($column === 'total')
                                <td class="text-right">
                                    @isset($saleData['total'])
                                        @currencyFormat($saleData['total'])
                                    @endisset
                                </td>
                            @elseif ($column === 'total_compare')
                                <td class="text-right">
                                    @isset($saleData['total_compare'])
                                        @currencyFormat($saleData['total_compare'])
                                    @endisset
                                </td>
                            @elseif ($column === '%')
                                <td class="text-right">
                                   @if (isset($saleData['total_compare']) || isset($saleData['total']))
                                    @php
                                        $totalCompare = isset($saleData['total_compare']) ? $saleData['total_compare'] : 0;
                                        $total = isset($saleData['total']) ? $saleData['total'] : 0;
                                    @endphp

                                    @if ((float) $total !== 0.0)
                                            {{ number_format((($total - $totalCompare) * 100) / $total, 2) }}
                                    @else
                                        0
                                    @endif
                                @else
                                    0
                                @endif
                                </td>
                            @elseif (array_key_exists($key, $saleData))
                                <td class="text-right"> @currencyFormat($saleData[$key])</td>
                            @else
                                <td class="text-center">-</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No Records</td>
                    </tr>
                @endforelse

            </tbody>
        @empty
            <tr>
                <td colspan="5" class="text-center">No Records</td>
            </tr>
        @endforelse

        <tr>
            <td colspan="{{ count($columns) }}" class="text-center" style="border: none;"> &nbsp;</td>
        </tr>

        <tr>
            @if (count($grandTotal) > 2)
                @foreach ($columns as $key => $column)
                    @if ($column === 'Location Name')
                        <td style="width: 50px;">{{ $grandTotal['location_name'] }}</td>
                    @elseif ($column === 'total')
                        <td>@currencyFormat($grandTotal['total'])</td>
                    @elseif ($column === 'total_compare')
                        <td>@currencyFormat($grandTotal['total_compare'])</td>
                    @elseif (isset($grandTotal[$key]))
                        <td class="text-right"> @currencyFormat($grandTotal[$key])</td>
                    @else
                        <td class="text-center">-</td>
                    @endif
                @endforeach
            @endif
        </tr>
    </table>
</body>

</html>
