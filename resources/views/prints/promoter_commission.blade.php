<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Promoter Commission Report</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Promoter Commission Report" :reportType="$reportType" :filterBy="$filterBy" :dateRange="null" :date="$date"  />
    <p> Sales from <strong> {{ $dateRange }} </strong> </p>

    @foreach ($promoterCommissionSales as $promoterCommissionSale)
        <p> Location: <strong> {{ $promoterCommissionSale['location_name'] }} </strong> </p>
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
                    @if ($promoterCommissionSale['promoters'])
                        @foreach ($promoterCommissionSale['promoters'] as $promoter)
                            <tr class="page-break-inside-avoid">
                                <td class="text-left" colspan="8">
                                    {{ $promoter['name'] }} ({{ $promoter['code'] }})
                                </td>
                            </tr>

                            @foreach ($promoter['details'] as $details)
                                <tr class="page-break-inside-avoid">
                                    @foreach($columns as $column)
                                        @if ($column === 'Promoter')
                                            <td class="text-left">{{ $details['status'] }}</td>
                                        @elseif ($column === 'Name')
                                            <td class="text-left">{{ $details['store_code'] }}</td>
                                        @elseif ($column === 'Commission Percentage' || $column === 'Quantity')
                                            <td class="text-center">
                                                {{ $details[strtolower(str_replace(' ', '_', $column))] }}
                                            </td>
                                        @elseif ($column === 'Commission Amount')
                                            <td class="text-right">
                                                {{ $details[strtolower(str_replace(' ', '_', $column))] }}
                                            </td>
                                        @else
                                            <td class="text-left">
                                                {{ $details[strtolower(str_replace(' ', '_', $column))] }}
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach

                            <tr class="page-break-inside-avoid">
                                <td colspan="{{ count($columns) - 3  }}" class="text-center font-bold"> <b> Grand Total </b> </td>
                                <td class="text-center font-bold"> {{ $promoter['total']['total_quantity'] }} </td>
                                <td></td>
                                <td class="text-right font-bold"> {{ $promoter['total']['total_commission_amount'] }} </td>
                            </tr>
                        @endforeach
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
