<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>The Worst 20 Sales Qty</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Worst Twenty Report" reportType="By Attribute" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach($locationsSales as $locationSales)
        <p> Location: <strong> {{ $locationSales['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-center">Product No.</th>
                    <th class="text-center item">Description</th>
                    <th class="text-center mt-2" >Qty</th>
                    @if ($displayAmount)
                        <th class="text-center mt-2" >Gross Sales <br>Excl. GST</th>
                        <th class="text-center mt-2" >Discount <br> Amount</th>
                        <th class="text-center mt-2" >Net Sales <br> Excl. GST</th>
                        <th class="text-center mt-2" >GST <br> Amount</th>
                        <th class="text-center mt-2" >Net Sales <br> Incl. GST</th>
                    @endIf
                </tr>
            </thead>

            <tbody>
                @forelse($locationSales['attributes'] as $attribute)
                    @if(count($attribute['products']) > 0)
                        <tr class="page-break-inside-avoid">
                            <td colspan="8" class="text-center"><b>{{ $attribute['name'] }}</b></td>
                        </tr>
                        @foreach($attribute['products'] as $product)
                            <tr class="page-break-inside-avoid">
                                <td class="{{ $product['product_no'] === 'Total' ? 'text-bold' : '' }}">
                                    {{ $product['product_no'] }}
                                </td>
                                <td class="{{ $product['product_no'] === 'Total' ? 'text-bold' : '' }}">
                                    {{ $product['name'] }}
                                </td>
                                <td class="{{ $product['product_no'] === 'Total' ? 'text-bold text-center mt-2' : 'text-center mt-2' }}">
                                    {{ $product['qty'] }}
                                </td>
                                @if ($displayAmount)
                                    <td class="{{ $product['product_no'] === 'Total' ? 'text-bold text-center mt-2' : 'text-center mt-2' }}">
                                        @currencyFormat($product['gross_sales_excl_gst'])
                                    </td>
                                    <td class="{{ $product['product_no'] === 'Total' ? 'text-bold text-center mt-2' : 'text-center mt-2' }}">
                                        @currencyFormat($product['discount_amount'])
                                    </td>
                                    <td class="{{ $product['product_no'] === 'Total' ? 'text-bold text-center mt-2' : 'text-center mt-2' }}">
                                        @currencyFormat($product['net_sales_excl_gst'])
                                    </td>
                                    <td class="{{ $product['product_no'] === 'Total' ? 'text-bold text-center mt-2' : 'text-center mt-2' }}">
                                        @currencyFormat($product['gst_amount'])
                                    </td>
                                    <td class="{{ $product['product_no'] === 'Total' ? 'text-bold text-center mt-2' : 'text-center mt-2' }}">
                                        @currencyFormat($product['net_sales_incl_gst'])
                                    </td>
                                @endIf
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
