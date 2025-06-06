<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sale Hour</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Sale Hour Report" reportType="by Hour" filterBy="" :dateRange="$dateRange"
        :date="$date" />
    <p> Location: <strong> {{ $locationName }} </strong> </p>
    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th class="text-center mt-2">
                        {{ $column }}
                    </th>
                @endforeach
                <th class="text-center mt-2">Grand Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse($saleHours['sales'] as $key => $saleHour)
                <tr class="page-break-inside-avoid">
                    @foreach ($columns as $column)
                        @if ($column === 'Date')
                            <td class="text-left">
                                {{ $key }}
                            </td>
                        @else
                            @if (array_key_exists($column, $saleHour))
                                <td class="text-right">
                                    {{ $currencySymbol }} @currencyFormat($saleHour[$column])
                                </td>
                            @else
                                <td class="text-right"> {{ $currencySymbol }}0.00 </td>
                            @endif
                        @endif
                    @endforeach
                    <td class="text-right text-bold"> {{ $currencySymbol }} @currencyFormat($saleHour['grand_total'])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="text-center">No Records</td>
                </tr>
            @endforelse

            @if (isset($saleHours['totals']))
                <tr class="page-break-inside-avoid text-bold">
                    @foreach ($columns as $column)
                        @if (array_key_exists($column, $saleHours['totals']))
                            <td class="text-right">
                                {{ $currencySymbol }} @currencyFormat($saleHours['totals'][$column])
                            </td>
                        @else
                            <td class="text-left">Grand total</td>
                        @endif
                    @endforeach
                    <td class="text-right"> {{ $currencySymbol }} @currencyFormat($saleHours['grand_total']) </td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
