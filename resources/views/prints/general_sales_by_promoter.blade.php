<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>General Sales Summary Report</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="General Sales Report" :reportType="$reportType"
        :filterBy="$filterBy" :dateRange="$dateRange" :date="$date" />

    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    @foreach ($locationSales['data'] as $locationSale)
        <p class="mb-4">Location : <strong>{{ $locationSale['location_name'] }}</strong></p>
        <table class="table table-bordered">
            <thead>
                <tr>
                        <th class="text-left">Date</th>
                        <th class="text-left">Promoter</th>
                        <th class="text-right">Items</th>
                        <th class="text-right">Sales</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($locationSale['products'] as $product)
                <tr class="page-break-inside-avoid @if ($product['date'] === 'Total') text-bold @endif">
                    <td class="text-left">{{ $product['date'] }}</td>
                    <td class="text-left">{{ $product['promoter'] }}</td>
                    <td class="text-right"> @truncateDecimal($product['items']) </td>
                    <td class="text-right"> @currencyFormat($product['sales']) </td>
                </tr>
            @empty
                <tr class="page-break-inside-avoid">
                    <td class="text-left" colspan="4">No records found for this location</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @endforeach

    <table class="table table-bordered">
        <strong>Grand Total:</strong>
        <thead>
            <tr>
                <th width="60%"></th>
                <th width="20%" class="text-right">Grand Total Items:</th>
                <th width="20%" class="text-right">Grand Total Amount:</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th></th>
                <th class="text-right">@truncateDecimal($locationSales['grand_total']['totalItemSold'])</th>
                <th class="text-right">@currencyFormat($locationSales['grand_total']['totalSalesAmount'])
                </th>
            </tr>
        </tbody>
    </table>
</body>

</html>
<style :scope>
    .table-bordered {
        margin-top: 0.3rem;
    }

    .mb-4 {
        margin-bottom: 1.4rem !important;
    }
</style>
