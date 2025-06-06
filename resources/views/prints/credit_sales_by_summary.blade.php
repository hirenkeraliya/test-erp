<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Credit Sales </title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Summary Of Credit Sales" reportType="By Summary" :date="$date"
        :dateRange="$dateRange" />

    @forelse($creditSalesData as $creditSaleData)
        <h4>
            Location Name {{ $creditSaleData['location_name'] }}
        </h4>

        <table class="table table-bordered bordered">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th class="text-center">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($creditSaleData['products'] as $saleData)
                    <tr class="page-break-inside-avoid">
                        <td> {{ $saleData['receipt_number'] }}</td>
                        <td> {{ $saleData['status'] }}</td>
                        <td> {{ $saleData['counter'] }}</td>
                        <td> {{ $saleData['cashier'] }}</td>
                        <td> {{ $saleData['credit_authorizer'] }}</td>
                        <td class="text-right" style="width: 8%"> @currencyFormat($saleData['total_amount'])</td>
                        <td class="text-right" style="width: 8%"> @currencyFormat($saleData['total_amount_paid'])</td>
                        <td class="text-right" style="width: 8%"> @currencyFormat($saleData['credit_pending_amount'])</td>
                    </tr>
                @empty
                    <td colspan="8" class="text-center">No Records</td>
                @endforelse

                @if (array_key_exists('totals', $creditSaleData))
                    <tr>
                        <th colspan="5">
                            Total
                        </th>

                        <th class="text-right" style="width: 8%">
                            @currencyFormat($creditSaleData['totals']['total_amount'])
                        </th>

                        <th class="text-right" style="width: 8%">
                            @currencyFormat($creditSaleData['totals']['total_amount_paid'])
                        </th>

                        <th class="text-right" style="width: 8%">
                            @currencyFormat($creditSaleData['totals']['total_credit_pending_amount'])
                        </th>
                    </tr>
                @endif
            </tbody>
        </table>
    @empty
        <span class="text-center">No Records</span>
    @endforelse

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                <th class="page-break-inside-avoid" colspan="5">
                    Grand Total:
                </th>

                <th class="text-right" style="width: 8%">
                    @currencyFormat($grandTotal['total_amount'])
                </th>

                <th class="text-right" style="width: 8%">
                    @currencyFormat($grandTotal['total_amount_paid'])
                </th>

                <th class="text-right" style="width: 8%">
                    @currencyFormat($grandTotal['total_credit_pending_amount'])
                </th>
            </tr>
        </thead>
    </table>
</body>

</html>
