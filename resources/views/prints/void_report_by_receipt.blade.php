<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Void Sales Report by Receipt</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Void Sales Report" reportType="" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach($locationsSales as $locationSales)
        <p> Location : <strong> {{ $locationSales['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($locationSales['sales']  as $locationSale)
                    @foreach($locationSale['products'] as $product)
                        <tr class="page-break-inside-avoid">
                            <td>{{$locationSale['receipt_date']}}</td>
                            <td>{{$locationSale['receipt_no']}}</td>
                            <td>{{$product['product_upc']}}</td>
                            <td>{{$product['product_name']}}</td>
                            <td class="text-right">@currencyFormat($product['total'])</td>
                            <td>{{$locationSale['void_reason']}}</td>
                            <td>{{$locationSale['voided_by']}}</td>
                            <td>{{$locationSale['void_sale_number']}}</td>
                            <td> {{ $product['promoters'] }} </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
