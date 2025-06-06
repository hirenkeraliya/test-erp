<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>The Best 20 Sales Qty</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Top Twenty Report" reportType="By Products" :filterBy="$filterBy"
        :dateRange="$dateRange" :date="$date" />

    @foreach ($locationsSales as $locationSales)
        <p> Location : <strong> {{ $locationSales['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">Product No.</th>
                    <th class="text-center item">Description</th>
                    @if(config('app.product_variant'))
                        <th class="text-center item">Attributes</th>
                    @else
                        <th class="text-center item">Color</th>
                        <th class="text-center item">Size</th>
                    @endif
                    <th class="text-center mt-2">Qty</th>
                    @if ($displayAmount)
                        <th class="text-center mt-2">Gross Sales <br>Excl. GST</th>
                        <th class="text-center mt-2">Discount <br> Amount</th>
                        <th class="text-center mt-2">Net Sales <br> Excl. GST</th>
                        <th class="text-center mt-2">GST <br> Amount</th>
                        <th class="text-center mt-2">Net Sales <br> Incl. GST</th>
                    @endIf
                </tr>
            </thead>

            <tbody>
                @forelse($locationSales['products'] as $product)
                    <tr>
                        <td>
                            {{ $product['product_no'] }}
                        </td>

                        <td>
                            {{ $product['name'] }}
                        </td>

                        @if(config('app.product_variant'))
                            <td> 
                                {{ $product['attributes'] }}
                            </td>
                        @else
                            <td>
                                {{ $product['color'] }}
                            </td>

                            <td>
                                {{ $product['size'] }}
                            </td>
                        @endif

                        <td class="text-center">
                            {{ $product['qty'] }}
                        </td>

                        @if ($displayAmount)
                            <td class="text-right">
                                {{ $product['gross_sales_excl_gst'] }}
                            </td>

                            <td class="text-right">
                                {{ $product['discount_amount'] }}
                            </td>

                            <td class="text-right">
                                {{ $product['net_sales_excl_gst'] }}
                            </td>

                            <td class="text-right">
                                {{ $product['gst_amount'] }}
                            </td>

                            <td class="text-right">
                                {{ $product['net_sales_incl_gst'] }}
                            </td>
                        @endIf
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No Records</td>
                    </tr>
                @endforelse

                @if ($locationSales['total'])
                    <tr>
                        <th>
                            {{ $locationSales['total']['product_no'] }}
                        </th>

                        <th>
                            {{ $locationSales['total']['name'] }}
                        </th>

                        @if(config('app.product_variant'))
                            <th>
                                {{ $locationSales['total']['attributes'] }}
                            </th>
                        @else
                            <th>
                                {{ $locationSales['total']['color'] }}
                            </th>

                            <th>
                                {{ $locationSales['total']['size'] }}
                            </th>
                        @endif

                        <th class="text-center">
                            {{ $locationSales['total']['qty'] }}
                            {{ $displayAmount }}
                        </th>

                        @if ($displayAmount)
                            <th class="text-right">
                                {{ $locationSales['total']['gross_sales_excl_gst'] }}
                            </th>

                            <th class="text-right">
                                {{ $locationSales['total']['discount_amount'] }}
                            </th>

                            <th class="text-right">
                                {{ $locationSales['total']['net_sales_excl_gst'] }}
                            </th>

                            <th class="text-right">
                                {{ $locationSales['total']['gst_amount'] }}
                            </th>

                            <th class="text-right">
                                {{ $locationSales['total']['net_sales_incl_gst'] }}
                            </th>
                        @endIf
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>

</html>
