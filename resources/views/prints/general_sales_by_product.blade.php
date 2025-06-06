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

    @foreach ($locationSales as $locationSale)
    <p> Location : <strong> {{ $locationSale['location_name'] }} </strong> </p>

    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($locationSale['products'] as $product)
                    <tr class="page-break-inside-avoid">

                        @foreach($columns as $column)
                            @if ($column === 'Counter Name' || 'Product UPC' || $column === 'Product Name' || $column === 'Color' || $column === 'Size' || $column === 'Attributes')
                                <td class="text-left">{{ $product[strtolower(str_replace(' ', '_', $column))] }}</td>
                            @elseif ($column === 'Quantity')
                                <td class="text-center">{{ $product[strtolower(str_replace(' ', '_', $column))] }}</td>
                            @else
                                <td class="text-right">{{ $product[strtolower(str_replace(' ', '_', $column))] }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse

                <tr class="page-break-inside-avoid">
                    <th colspan="{{ config('app.product_variant') ? 6 : 7 }}" class="text-left">Total:</th>
                    <th class="text-center">{{ $locationSale['totals']['totalQuantity'] }}</th>
                    <th class="text-right">@currencyFormat($locationSale['totals']['totalGrossSales'])</th>
                    <th class="text-right">@currencyFormat($locationSale['totals']['totalDiscountAmount'])</th>
                    <th class="text-right">@currencyFormat($locationSale['totals']['totalNetSaleExclusiveTax'])</th>
                    <th class="text-right">@currencyFormat($locationSale['totals']['totalTaxAmount'])</th>
                    <th class="text-right">@currencyFormat($locationSale['totals']['totalNetSaleInclusiveTax'])</th>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
</body>

</html>
