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
    <x-report-header :company="$company" reportName="Discount Report" :reportType="$reportType" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

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
                @if (2)
                    @forelse($saleDiscount['sales_data'] as $saleDiscountDetails)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['location_code'] }}
                            </td>
                            <td class="{{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['counter_code'] }}
                            </td>
                            <td class="{{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['cashier_code'] }}
                            </td>
                            @if (in_array('Employee Name', $columns))
                                <td class="{{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                    {{ $saleDiscountDetails['employee_name'] }}
                                </td>
                            @endif
                            <td class="{{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['date'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['upc'] }}
                            </td>
                            <td class="mt-2 {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['name'] }}
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
                            <td class="mt-2 text-center {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['quantity'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['price'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['item_discount'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['percentage'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['net_sales'] }}
                            </td>
                            <td class="mt-2 text-right {{ $saleDiscountDetails['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $saleDiscountDetails['variation'] }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="page-break-inside-avoid">
                        <td class="{{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['location_code'] }}
                        </td>
                        <td class="{{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['counter_code'] }}
                        </td>
                        <td class="{{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['cashier_code'] }}
                        </td>
                        @if (in_array('Employee Name', $columns))
                                <td class="{{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                                    {{ $saleDiscount['employee_name'] }}
                                </td>
                            @endif
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['cashier_code'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['upc'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['name'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['brand_name'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['department_name'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ config('app.product_variant') ? $saleDiscount['attribute'] : $saleDiscount['style_name'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['tag_name'] }}
                        </td>
                        <td class="mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['article_number'] }}
                        </td>
                        <td
                            class="text-center mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}"
                        >
                            {{ $saleDiscount['quantity'] }}
                        </td>
                        <td class="text-right mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['price'] }}
                        </td>
                        <td class="text-right mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['item_discount'] }}
                        </td>
                        <td class="text-right mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['percentage'] }}
                        </td>
                        <td class="text-right mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['net_sales'] }}
                        </td>
                        <td class="text-right mt-2 {{ $saleDiscount['article_number'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $saleDiscount['variation'] }}
                        </td>
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
