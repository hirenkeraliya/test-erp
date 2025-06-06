<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Discount Report</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Discount Report" :reportType="$reportType"  :dateRange="$dateRange" :date="$date"  />

    @foreach ($saleDiscounts as $saleDiscount)
        <p> Location : <strong>{{ $saleDiscount['location_name'] }}</strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                        <th class="text-left">Location Code</th>
                        <th class="text-left">Counter Code</th>
                        <th class="text-left">Cashier Code</th>
                        <th class="text-left">Employee Name</th>
                        <th class="text-left">Offline Sale Id</th>
                        <th class="text-left">Date</th>
                        <th class="text-right">Cart Total</th>
                        <th class="text-right">Cart Discount</th>
                        <th class="text-right">Percentage</th>
                        <th class="text-right">Net Sales</th>
                        <th class="text-right">Variation</th>
                </tr>
            </thead>

            <tbody>
                @if (1)
                    @forelse($saleDiscount['sales_data'] as $saleDiscountDetails)
                        <tr class="page-break-inside-avoid">
                            <td class="text-left">
                                {{ $saleDiscountDetails['location_code'] }}
                            </td>
                            <td class="text-left">
                                {{ $saleDiscountDetails['counter_code'] }}
                            </td>
                            <td class="text-left">
                                {{ $saleDiscountDetails['cashier_code'] }}
                            </td>

                            <td class="text-left">
                                {{ $saleDiscountDetails['employee_name'] }}
                            </td>

                            <td class="text-left">
                                {{ $saleDiscountDetails['offline_sale_id'] }}
                            </td>

                            <td class="text-left">
                                {{ $saleDiscountDetails['date'] }}
                            </td>

                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['cart_total'] }}
                            </td>

                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['cart_discount'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['percentage'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['net_sales'] }}
                            </td>
                            <td class="mt-2 text-right">
                                {{ $saleDiscountDetails['variation'] }}
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="6" class="text-center text-bold">Total</td>
                        <td class="text-right text-bold">{{$saleDiscount['grand_cart_total']}}</td>
                        <td class="text-right text-bold">{{$saleDiscount['grand_cart_discount']}}</td>
                        <td></td>
                        <td class="text-right text-bold">{{$saleDiscount['grand_net_sales']}}</td>
                        <td class="text-right text-bold">{{$saleDiscount['grand_variation']}}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
