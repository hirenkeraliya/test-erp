<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales By Promoter Report</title>
</head>

<body class="font-arial">
    <x-report-header :company="$company" reportName="Sales By Promoter Report" reportType="By Summary" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @forelse ($locationSales as $locationSale)
    <p> Location : <strong> {{ $locationSale['location_name'] }} </strong> </p>

    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="vertical-align text-center">Promoter</th>
                    <th class="vertical-align text-center">Staff Id</th>
                    <th class="vertical-align text-center">Units Sold</th>
                    <th class="vertical-align text-center">Units Returned</th>
                    <th class="vertical-align text-center">Returned</th>
                    <th class="vertical-align text-center">Gross</th>
                    <th class="vertical-align text-center">Discount</th>
                    <th class="vertical-align text-center">Tax</th>
                    <th class="vertical-align text-center">Net</th>
                </tr>
            </thead>

            <tbody>
                @foreach($locationSale['promoter_sales'] as $promoterSale)
                    <tr class="page-break-inside-avoid">
                        <td class="text-bold">{{ $promoterSale['promoter_name'] }}</td>
                        <td class="text-bold">{{ $promoterSale['staff_id'] }}</td>
                        <td class="text-bold text-center">{{ $promoterSale['units_sold'] }}</td>
                        <td class="text-bold text-center">{{ $promoterSale['units_returned'] }}</td>
                        <td class="text-bold text-right">
                            @currencyFormat($promoterSale['total_units_returned_amount'])
                        </td>
                        <td class="text-bold text-right">
                            @currencyFormat($promoterSale['gross_amount'])
                        </td>
                        <td class="text-bold text-right">
                            @currencyFormat($promoterSale['discount_amount'])
                        </td>
                        <td class="text-bold text-right">
                            @currencyFormat($promoterSale['tax_amount'])
                        </td>
                        <td class="text-bold text-right">
                            @currencyFormat($promoterSale['net_amount'])
                        </td>
                    </tr>
                @endforeach
                <tr class="page-break-inside-avoid">
                    <td colspan="2" class="text-bold text-center">Total</td>
                    <td class="text-bold text-center">{{ $total[$locationSale['location_id']]['units_sold'] }}</td>
                    <td class="text-bold text-center">{{ $total[$locationSale['location_id']]['units_returned'] }}</td>
                    <td class="text-bold text-right">@currencyFormat($total[$locationSale['location_id']]['total_units_returned_amount'])</td>
                    <td class="text-bold text-right">@currencyFormat($total[$locationSale['location_id']]['gross_amount'])</td>
                    <td class="text-bold text-right">@currencyFormat($total[$locationSale['location_id']]['discount_amount'])</td>
                    <td class="text-bold text-right">@currencyFormat($total[$locationSale['location_id']]['tax_amount'])</td>
                    <td class="text-bold text-right">@currencyFormat($total[$locationSale['location_id']]['net_amount'])</td>
                </tr>
            </tbody>
        </table>
    </div>
    @empty
        <div class="text-center border-1 p-2">No Records</div>
    @endforelse
</body>

</html>
