<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Promoter Commission Details Report</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <div class="date-display">
        <h4>
            Promoter Commission Details
        </h4>

        <p>
            Date: {{ $date }}
        </p>
    </div>

    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th> Offline Id </th>
                    <th> Product </th>
                    <th> Brand </th>
                    @if ($productVariant)
                        <th> Attributes </th>
                    @else
                        <th> Color </th>
                        <th> Size </th>
                    @endif
                    <th> Department </th>
                    <th> Location Name </th>
                    <th class="text-right"> Units </th>
                    <th class="text-right"> Commission Percentage </th>
                    <th class="text-right"> Amount </th>
                    <th class="text-right"> Commission </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($promoterCommissionDetails as $promoterCommissionDetail)
                    <tr class="page-break-inside-avoid">
                        <td>{{ $promoterCommissionDetail['offline_id'] }}</td>
                        <td>{{ $promoterCommissionDetail['product'] }}</td>
                        <td>{{ $promoterCommissionDetail['brand'] }}</td>
                        @if ($productVariant)
                            <td> {{ $promoterCommissionDetail['attributes'] }} </td>
                        @else
                            <td> {{ $promoterCommissionDetail['color'] }} </td>
                            <td> {{ $promoterCommissionDetail['size'] }} </td>
                        @endif
                        <td>{{ $promoterCommissionDetail['department'] }}</td>
                        <td>{{ $promoterCommissionDetail['location_name'] }}</td>
                        <td>{{ $promoterCommissionDetail['units'] }}</td>
                        <td class="text-right">{{ $promoterCommissionDetail['commission_percentage'] }}</td>
                        <td class="text-right">{{ $promoterCommissionDetail['amount'] }}</td>
                        <td class="text-right">{{ $promoterCommissionDetail['commission_amount'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse

                @if (count($promoterCommissionDetails) > 0)
                    <tr class="page-break-inside-avoid">
                        <th colspan="{{ $productVariant ? 8 : 9 }}" class="text-center">Grand Total</th>
                        <th class=text-right>{{ $totalSaleAmount }}</th>
                        <th class=text-right>{{ $totalCommissionAmount }}</th>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>

</html>
