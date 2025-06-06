<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Take Break Report </title>

    <style>
        tr, td, th {
            padding: 2px;
        }

        table {
            padding: 5px
        }
    </style>
</head>
<body class="arial-font-custom-report">
    <h2 class="text-center">
        {{ $location->name }} ({{ $location->code }})
    </h2>

    <h2 class="text-center">
        {{ $counter }}
    </h2>

    <p>
        Date: {{ $date }}
    </p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th> Take A Break </th>
                <th> Back From Break </th>
                <th>Duration</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($takeBreakDetails['data'] as $takeBreakDetail)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ $takeBreakDetail['take_a_break'] }}
                    </td>
                    <td>
                        {{ $takeBreakDetail['back_from_break'] }}
                    </td>
                    <td>
                        {{ $takeBreakDetail['duration'] }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td></td>
                <td class="ml-8"><b>Duration</b></td>
                <td><b>{{$takeBreakDetails['total_duration']}}</b></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
