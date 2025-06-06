<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Details</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <x-pdf-report-header :filterData="$filter_header_data" />

    <div class="date-display">
        <h4>
            Activities Details
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
                        <th class="text-left">
                            {{ ucfirst(str_replace(' ', '_', $column)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($orders as $activity)
                    <tr class="page-break-inside-avoid">
                        @foreach($columns as $column)
                            <td>{{ $activity[$column] }}</td>
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
