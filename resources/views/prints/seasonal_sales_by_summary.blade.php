<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Seasonal Sales </title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Summary Of Seasonal Sales" reportType="By Summary" :date="$date" />

    @forelse($seasonalSalesData as $seasonalSaleData)
        <h4>
            Brand {{ $seasonalSaleData['brand_name'] }}
        </h4>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        Branch
                    </th>

                    <th class="text-right">
                        {{ $saleSeasonName }} ({{ $currencySymbol }})
                    </th>

                    <th class="text-right">
                        {{ $compareSaleSeasonName }} ({{ $currencySymbol }})
                    </th>

                    <th class="text-right">
                        Balance To Achieve ({{ $currencySymbol }})
                    </th>

                    <th class="text-center">
                        Achievement (%)
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse($seasonalSaleData['locations'] as $saleData)
                    <tr class="page-break-inside-avoid">
                        <td> {{ $saleData['location_name'] }}</td>
                        <td class="text-right" style="width: 15%;"> @currencyFormat($saleData['total_amount'])</td>
                        <td style="width: 15%;" class="text-right"> @currencyFormat($saleData['total_amount_compare'])</td>
                        <td class="text-right" style="width: 15%;">
                            @currencyFormat($saleData['total_amount'] - $saleData['total_amount_compare'])
                        </td>
                        <td class="text-center" style="width: 10%;">
                            @if ((float) $saleData['total_amount'] === 0.0)
                                0
                            @else
                                @truncateDecimal((($saleData['total_amount'] - $saleData['total_amount_compare']) * 100) / $saleData['total_amount'])%
                            @endif
                        </td>
                    </tr>
                @empty
                    <td colspan="5" class="text-center">No Records</td>
                @endforelse

                @if (array_key_exists('brand_total_compare', $seasonalSaleData) && array_key_exists('brand_total', $seasonalSaleData))
                    <tr>
                        <th>
                            Total
                        </th>

                        <th class="text-right" style="width: 15%;">
                            @currencyFormat($seasonalSaleData['brand_total'])
                        </th>

                        <th class="text-right" style="width: 15%;">
                            @currencyFormat($seasonalSaleData['brand_total_compare'])
                        </th>

                        <th class="text-right" style="width: 15%;">
                            @currencyFormat($seasonalSaleData['brand_total'] - $seasonalSaleData['brand_total_compare'])
                        </th>

                        <th class="text-center" style="width: 10%;">
                            @if ((float) $seasonalSaleData['brand_total'] !== 0.0)
                                @truncateDecimal((($seasonalSaleData['brand_total'] - $seasonalSaleData['brand_total_compare']) * 100) / $seasonalSaleData['brand_total'])%
                            @else
                                0
                            @endif
                        </th>
                    </tr>
                @endif
            </tbody>
        </table>
    @empty
        <span class="text-center">No Records</span>
    @endforelse

    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="page-break-inside-avoid">
                    Grand Total:
                </th>

                <th class="text-right" style="width: 15%;">
                    @currencyFormat($grandTotal)
                </th>

                <th class="text-right" style="width: 15%;">
                    @currencyFormat($grandTotalCompare)
                </th>

                <th class="text-right" style="width: 15%;">
                    @currencyFormat($grandTotal - $grandTotalCompare)
                </th>

                <th class="text-center" style="width: 10%;">
                    @if ((float) $grandTotal === 0.0)
                        0
                    @else
                        @truncateDecimal((($grandTotal - $grandTotalCompare) * 100) / $grandTotal)%
                    @endif
                </th>
            </tr>
        </thead>
    </table>
</body>

</html>
