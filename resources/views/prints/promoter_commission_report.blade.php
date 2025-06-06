<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Promoter Commission Report</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <div class="date-display">
        <h4>
            Promoter Commission Report
        </h4>

        <p>
            Date: {{ $date }}
        </p>
        <x-pdf-report-header :filterData="$filterHeaderData"  />
    </div>

    <div>
        <table class="table table-bordered" width="100%">
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
                @forelse ($promoterCommissions as $promoterCommission)
                    <tr class="page-break-inside-avoid">
                         @foreach ($columnsKeys as $column)
                            <td class="text-right">
                                {{ $promoterCommission[$column] ?? '-' }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
