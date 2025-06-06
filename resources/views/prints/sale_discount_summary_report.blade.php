<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Discount Summary Report</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Discount Summary Report" :reportType="$reportType"  :dateRange="$dateRange" :date="$date"  />

    @foreach ($saleDiscounts as $saleDiscount)
        <p> Location : <strong>{{ $saleDiscount['location_name'] }}</strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-left">Sale ID</th>
                    <th class="text-left">Location</th>
                    <th class="text-left">Counter</th>
                    <th class="text-left">Date</th>
                    <th class="text-right">Cart Total</th>
                    <th class="text-right">Cart Discount</th>
                    <th class="text-right">5% Off</th>
                    <th class="text-right">10% Off</th>
                    <th class="text-right">20% Off</th>
                    <th class="text-right">30% Off</th>
                    <th class="text-right">40% Off</th>
                    <th class="text-right">Other Discount</th>
                </tr>
            </thead>

            <tbody>
                @if (count($saleDiscount['sales_data']) > 0)
                    @forelse($saleDiscount['sales_data'] as $saleDiscountDetails)
                        <tr class="page-break-inside-avoid">
                            <td class="mt-2 text-left">
                                {{ $saleDiscountDetails['offline_sale_id'] }}
                            </td>
                            <td class="mt-2 text-left">
                                {{ $saleDiscountDetails['location_code'] }}
                            </td>
                            <td class="mt-2 text-left">
                                {{ $saleDiscountDetails['counter_code'] }}
                            </td>
                            <td class="mt-2 text-left">
                                {{ $saleDiscountDetails['date'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['cart_total'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['cart_discount'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['five_per_off'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['ten_per_off'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['twenty_per_off'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['thirty_per_off'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['forty_per_off'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['other_discount'] }}
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" class="text-center text-bold">Total</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_cart_total']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_cart_discount']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_total_five_per_off']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_total_ten_per_off']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_total_twenty_per_off']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_total_thirty_per_off']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_total_forty_per_off']}}</td>
                        <td class="text-right text-bold">{{$grandTotal['grand_total_other_discount']}}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="14" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
