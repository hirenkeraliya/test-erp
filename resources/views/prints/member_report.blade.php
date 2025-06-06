<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Member Details</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
    <style>
        .table-alignment {
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <x-pdf-report-header :filterData="$filter_header_data" />

    <div class="date-display">
        <h4>
            Member Details
        </h4>

        @if ($dateRangeFrom && $dateRangeTo)
            <p>
                Members from {{ $dateRangeFrom }} to {{ $dateRangeTo }}
            </p>
        @endif

        <p>
            Date: {{ $date }}
        </p>
    </div>

    <div>
        <table width=100% class="table table-bordered table-alignment">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th class="text-center">
                           {{ ucfirst(str_replace('_', ' ', $column)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($memberDetails as $member)
                <tr class="page-break-inside-avoid">
                   @foreach($columns as $column)
                        <td>{{ $member[$column] }}</td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center"> No Record Found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>