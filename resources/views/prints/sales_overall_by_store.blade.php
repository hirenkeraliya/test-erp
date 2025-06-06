<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Sales Overall Report</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Sales Overall Report" :reportType="$reportType" :filterBy="null" :dateRange="$dateRange" :date="$date"  />

        <table class="table table-bordered bordered">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($allLocations as $key => $location)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $location['location_name'] === 'Grand Total' ? 'text-bold' : '' }}">
                            {{ $location['location_name'] }}
                        </td>

                        @foreach($location['total_collection'] as $monthWise)
                        <td class="text-right {{ $location['location_name'] === 'Grand Total' ? 'text-bold' : '' }}">
                            @currencyFormat($monthWise)
                        </td>
                        @endforeach
                        <td class="text-right {{ $location['location_name'] === 'Grand Total' ? 'text-bold' : '' }}">
                            @currencyFormat($location['grand_total'])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
</body>
</html>
