<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Counter+Cashier </title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Sales Collection Report" reportType="by Counter And by Cashier" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    @foreach($locationPayments as $locationPayment)
        <p> Location : <strong> {{ $locationPayment['location_name'] }} </strong> </p>

        <table class="table table-bordered bordered">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($locationPayment['payment_details'] as $payment)
                    <tr class="page-break-inside-avoid">
                        @foreach($columns as $column)
                            @if(array_key_exists(strtolower(str_replace(' ', '_', $column)), $payment))
                                @if($column === 'Counter' || $column === 'Cashier')
                                    <td class="text-left">
                                        {{ $payment[strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @elseif($column === 'Orders')
                                    <td class="text-center">
                                        {{ $payment[strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @else
                                    <td class="text-right">
                                        @currencyFormat((float) $payment[strtolower(str_replace(' ', '_', $column))])
                                    </td>
                                @endif
                            @else
                                <td class="text-right">
                                    @currencyFormat(0.00)
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                    </tr>
                @endforelse
                @if (isset($locationPayment['totals']))
                    <tr class="page-break-inside-avoid text-bold">
                        @foreach($columns as $column)
                            @if(array_key_exists(strtolower(str_replace(' ', '_', $column)), $locationPayment['totals']))
                                @if($column === 'Counter' || $column === 'Cashier')
                                    <td class="text-left">
                                        {{ $locationPayment['totals'][strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @elseif($column === 'Orders')
                                    <td class="text-center">
                                        {{ $locationPayment['totals'][strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @else
                                    <td class="text-right">
                                        @currencyFormat((float) $locationPayment['totals'][strtolower(str_replace(' ', '_', $column))])
                                    </td>
                                @endif
                            @else
                                <td class="text-right">
                                    @currencyFormat(0.00)
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="text-left" style="line-height: 1px !important;">
                <p><b>Collection : @currencyFormat($locationPayment['totals']['sales_collection'] ?? 0)</b></p>
                <p><b>- Rounding Adjust : @currencyFormat($locationPayment['totals']['sales_round_off'] ?? 0)</b></p>
                <p><b>- GST : @currencyFormat($locationPayment['totals']['total_tax_amount'] ?? 0)</b></p>
        </div>

        <div>
            <p>
                <b>Average Sales per Receipt : {{ count($locationPayment['payment_details']) !== 0 && $locationPayment['totals']['orders'] !== 0 ? round(($locationPayment['totals']['sales_collection']/ $locationPayment['totals']['orders']), 2) : 0 }}</b>
            </p>
        </div>
    @endforeach
</body>
</html>
