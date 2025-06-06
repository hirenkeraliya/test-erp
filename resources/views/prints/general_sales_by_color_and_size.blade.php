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
                    <th class="text-center" width="110">Counter Name</th>
                    <th class="text-center" width="110">Product No</th>
                    <th class="text-center">Promoter</th>
                    <th class="text-center">Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Gross Sales</th>
                    <th class="text-center">Discount</th>
                    <th class="text-center">Net Sales</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($locationSaleDetails['data'] as $locationSale)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $locationSale['product']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['product']['counter_name'] }}</td>
                        <td class="{{ $locationSale['product']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['product']['product_no'] }}</td>
                        <td>{{ $locationSale['product']['promoters'] }}</td>
                        <td>{{ $locationSale['product']['description'] }}</td>
                        <td class="text-center {{ $locationSale['product']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['product']['qty'] }}</td>
                        <td class="text-right {{ $locationSale['product']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['product']['gross_sales'] }}</td>
                        <td class="text-right {{ $locationSale['product']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['product']['discount'] }}</td>
                        <td class="text-right {{ $locationSale['product']['counter_name'] === 'Total' ? 'text-bold' : ''}}">{{ $locationSale['product']['net_sales'] }}</td>
                    </tr>
                    @if(array_key_exists('sales',$locationSale))
                        <tr>
                            <td colspan="6" class="border-top-none">
                                <table class="ml-10 width-70-per">
                                    <thead>
                                        <tr>
                                            <th class="border-top-none text-center"> UPC </th>
                                            <th class="border-top-none text-center"> Color </th>
                                            <th class="border-top-none text-center"> Size </th>
                                            <th class="border-top-none text-center"> Quantity </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($locationSale['sales'] as $sales)
                                            @foreach($sales as $sale)
                                                <tr>
                                                    <td class="border-top-none text-left">{{ $sale['product_no'] }}</td>
                                                    <td class="border-top-none text-left">{{ $sale['color'] }}</td>
                                                    <td class="border-top-none text-left">{{ $sale['size'] }}</td>
                                                    <td class="border-top-none text-center">{{ $sale['qty'] }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</body>

</html>
