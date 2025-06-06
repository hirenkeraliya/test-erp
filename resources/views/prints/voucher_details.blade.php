<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Voucher Details</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <div class="date-display">
        <h4>
            Voucher Details
        </h4>

        <p>
            Date: {{ $date }}
        </p>
    </div>

    <div>
        <table class="table table-bordered">
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
                @forelse ($voucherDetails as $voucherDetail)
                    <tr class="page-break-inside-avoid">
                        <td>{{ $voucherDetail['date'] }}</td>
                        <td>{{ $voucherDetail['offline_sale_id'] }}</td>
                        <td>{{ $voucherDetail['location'] }}</td>
                        <td>{{ $voucherDetail['action_type'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>
