<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
    <script type="text/javascript" src="{{ asset('build/js/chartHelper.js') }}"></script>
    <title>Sell Through Report Details</title>

</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Sell Through Report {{ $reportType }}</strong>
    </h4>

    <p>
        @if (is_array($filterDate))
        Sell Through from {{ $filterDate[0] }} to {{ $filterDate[1] }}
        @else
        Sell Through Date : {{ $filterDate }}
        @endif
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <x-filter-label-header :getFilterLabels="$getFilterLabels" />

    <table class="table table-bordered bordered mt-2">
        <thead>
            <tr>
                <th>Location Name</th>
                <th>Balance</th>
            </tr>
        </thead>

        <tbody>

            @forelse($sellThroughBalanceDetails as $k => $sellThroughBalanceDetail)
            <tr class="page-break-inside-avoid">
                <td>
                    {{ $sellThroughBalanceDetail['location_name'] }}
                </td>

                <td class="text-right">
                    {{ $sellThroughBalanceDetail['balance'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="2" class="text-center">No Records</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
