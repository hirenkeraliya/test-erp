<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>General Sales Report</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="General Sales Report" :reportType="$reportType" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    @forelse ($locationSales as $locationSaleDetails)
        <p> Location : <strong> {{ $locationSaleDetails['location_name'] }} </strong> </p>
        <div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">Counter Name</th>
                        <th class="text-center">Product No</th>
                        <th class="text-center">Receipt Data</th>
                        <th class="text-center">Promoter</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Gross <br> Sales</th>
                        <th class="text-center">Discount</th>
                        <th class="text-center">Net Sales <br> Excl. Tax</th>
                        <th class="text-center">Tax <br> amount</th>
                        <th class="text-center">Net Sales <br> Incl. Tax</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($locationSaleDetails['data'] as $locationSale)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{
                                $locationSale['sale']['counter_name'] }}</td>
                            <td class="{{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['product_no'] }}</td>
                            <td>{{ $locationSale['sale']['receipt_data'] }}</td>
                            <td></td>
                            <td class="text-center {{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['qty'] }}</td>
                            <td class="text-right {{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['gross_sales'] }}</td>
                            <td class="text-center {{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['discount'] }}</td>
                            <td class="text-right {{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['net_sales_exclusive_tax'] }}</td>
                            <td class="text-right {{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['tax_amount'] }}</td>
                            <td class="text-right {{ $locationSale['sale']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['sale']['net_sales_inclusive_tax'] }}</td>
                        </tr>
                        @if(array_key_exists('products', $locationSale))
                            @foreach($locationSale['products'] as $product)
                                <tr class="page-break-inside-avoid ">
                                    <td class="border-top-none">{{ $product['counter_name'] }}</td>
                                    <td class="border-top-none">{{ $product['product_no'] }}</td>
                                    <td class="border-top-none">{{ $product['promoters'] }}</td>
                                    <td class="border-top-none">{{ $product['receipt_data'] }}</td>
                                    <td class="border-top-none text-center">{{ $product['qty'] }}</td>
                                    <td class="border-top-none text-right">{{ $product['gross_sales'] }}</td>
                                    <td class="border-top-none text-right">{{ $product['discount'] }}</td>
                                    <td class="border-top-none text-right">{{ $product['net_sales_exclusive_tax'] }}</td>
                                    <td class="border-top-none text-right">{{ $product['tax_amount'] }}</td>
                                    <td class="border-top-none text-right">{{ $product['net_sales_inclusive_tax'] }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
