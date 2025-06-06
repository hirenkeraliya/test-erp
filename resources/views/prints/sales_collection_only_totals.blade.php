<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Collection By Total</title>
</head>

<body class="arial-font-custom-report">
     <x-report-header :company="$company" reportName="Sales Collection Report" reportType="by summary" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif
        <table class="table table-bordered bordered">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($locationPayments as $key => $locationPayment)
                    <tr class="page-break-inside-avoid">
                        @foreach($columns as $column)
                            @if (array_key_exists(strtolower(str_replace(' ', '_', $column)), $locationPayment))
                                @if ($column === 'Location Name')
                                    <td class="{{ $key === 'totals' ? 'text-bold' : '' }}">
                                        {{ $locationPayment[strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @elseif($column === 'Orders')
                                    <td class="text-center {{ $key === 'totals' ? 'text-bold' : '' }}">
                                        {{ $locationPayment[strtolower(str_replace(' ', '_', $column))] }}
                                    </td>
                                @else
                                    <td class="text-right {{ $key === 'totals' ? 'text-bold' : '' }}">
                                        @if (array_key_exists(strtolower(str_replace(' ', '_', $column)), $locationPayment))
                                            @currencyFormat((float) $locationPayment[strtolower(str_replace(' ', '_', $column))] ?? 0)
                                        @else
                                            0
                                        @endif
                                    </td>
                                @endif
                            @else
                                <td class="text-right">
                                    @currencyFormat(0.00)
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
</body>
</html>
