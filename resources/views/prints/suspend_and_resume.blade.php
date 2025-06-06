<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Suspend And Resume Sales Report</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Suspend And Resume Report" reportType="" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach($locationHoldSales as $locationHoldSale)
        <p> Location: <strong> {{ $locationHoldSale['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-center">Suspend Counter.</th>
                    <th class="text-center item">Suspend Date</th>
                    <th class="text-center mt-2" >Suspend Receipt No.</th>
                    <th class="text-center mt-2" >Sales</th>
                    <th class="text-center mt-2" >Discount</th>
                    <th class="text-center mt-2" >Net</th>
                    <th class="text-center mt-2" >Cashier</th>
                    <th class="text-center mt-2" >Cancelled Date</th>
                    <th class="text-center mt-2" >Reason</th>
                    <th class="text-center mt-2" >Resume Date</th>
                    <th class="text-center mt-2" >Resume Receipt No.</th>
                    <th class="text-center mt-2" >Completed Date</th>
                </tr>
            </thead>

            <tbody>
                @forelse($locationHoldSale['hold_sale'] as $key => $holdSaleDetails)
                    <tr class="page-break-inside-avoid">
                        <td >{{ $holdSaleDetails['suspend_counter'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['suspend_date'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['suspend_receipt_no'] }}</td>
                        <td class="text-center mt-2"> {{ $currencySymbol }} {{ $holdSaleDetails['total_sales'] }}</td>
                        <td class="text-center mt-2"> {{ $currencySymbol }} {{ $holdSaleDetails['discount'] }}</td>
                        <td class="text-center mt-2"> {{ $currencySymbol }} {{ $holdSaleDetails['total_net_sales'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['cashier'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['cancelled_date'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['reason'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['resume_date'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['resume_receipt_no'] }}</td>
                        <td class="text-center mt-2">{{ $holdSaleDetails['completed_date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
