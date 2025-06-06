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
    <x-report-header :company="$company" reportName="Discount Summary Report" :reportType="$reportType" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach ($saleDiscounts as $saleDiscount)
        <p> Location : <strong>{{ $saleDiscount['location_name'] }}</strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    @foreach ($columns as $column)
                        <th class="text-center"> {{ $column }} </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if (count($saleDiscount['sales_data']) > 0)
                    @forelse($saleDiscount['sales_data'] as $saleDiscountDetails)
                        <tr class="page-break-inside-avoid">
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['name'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['upc'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['brand_name'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['department_name'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ config('app.product_variant') ? $saleDiscountDetails['attribute'] : $saleDiscountDetails['style_name'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['tag_name'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['article_number'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['price'] }}
                            </td>
                            <td class="mt-2 text-center {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['five_per_off'] }}
                            </td>
                            <td class="mt-2 text-center {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['ten_per_off'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['twenty_per_off'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['thirty_per_off'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['forty_per_off'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['other_discount'] }}
                            </td>
                        </tr>
                    @endforeach
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
