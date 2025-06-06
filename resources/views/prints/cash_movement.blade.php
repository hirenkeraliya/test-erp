<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Cash Movement Report</title>
</head>

<body class="font-arial">
    <x-report-header :company="$company" reportName="Cash Movement Report" reportType="" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach ($cashMovements as $cashMovement)
        <p> Location: <strong> {{ $cashMovement['location_name'] }} </strong> </p>

        <div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th class="vertical-align text-center">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @if (2)
                        @foreach ($cashMovement['cash_movements'] as $key => $cashMovementDetails)
                            <tr>
                                @foreach($columns as $column)
                                    @if ($column === 'Cash In' || $column === 'Cash Out')
                                        <td class="text-right">{{ $cashMovementDetails[strtolower(str_replace(' ', '_', $column))] }}</td>
                                    @else
                                        <td>{{ $cashMovementDetails[strtolower(str_replace(' ', '_', $column))] }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="page-break-inside-avoid">
                            <th class="text-center font-bold" colspan="3">Grand Total</th>
                            <th class="font-bold text-right">{{ $cashMovement['cash_in_total'] }}</th>
                            <th class="font-bold text-right">{{ $cashMovement['cash_out_total'] }}</th>
                        </tr>
                    @else
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center font-bold"> No Record Found</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
