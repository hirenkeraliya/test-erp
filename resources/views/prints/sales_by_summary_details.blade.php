<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Report By Summary Details</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Sales Collection Report" reportType="By Summary Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif
    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center" width="110">Location</th>
                    <th class="text-center">Location Name</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Gross <br> Sales</th>
                    <th class="text-center">Discount</th>
                    <th class="text-center">Net Sales <br> Excl. Tax</th>
                    <th class="text-center">Tax <br> amount</th>
                    <th class="text-center">Net Sales <br> Incl. Tax</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($locationSales as $locationSale)
                    <tr class="page-break-inside-avoid">
                        <td>{{$locationSale['location_code']}}</td>
                        <td>{{$locationSale['location_name']}}</td>
                        <td class="text-center">{{$locationSale['quantity']}}</td>
                        <td class="text-right">{{$locationSale['gross_sales_exclusive_tax']}}</td>
                        <td class="text-right">{{$locationSale['discount_amount']}}</td>
                        <td class="text-right">{{$locationSale['net_sales_exclusive_tax']}}</td>
                        <td class="text-right">{{$locationSale['tax_amount']}}</td>
                        <td class="text-right">{{$locationSale['net_sales_inclusive_tax']}}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center"> No Record Found</td>
                    </tr>
                @endforelse

                <tr class="page-break-inside-avoid">
                    <th colspan="2" class="text-right">Total:</th>
                    <th class="text-center">{{ $totalQty }}</th>
                    <th class="text-right">@currencyFormat($totalGross)</th>
                    <th class="text-right">@currencyFormat($totalDiscount)</th>
                    <th class="text-right">@currencyFormat($totalNetSaleEx)</th>
                    <th class="text-right">@currencyFormat($totalTaxAmount)</th>
                    <th class="text-right">@currencyFormat($totalNetSaleIn)</th>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
