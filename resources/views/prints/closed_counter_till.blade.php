<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Closed Counter Till Report </title>

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
                <th> Date </th>
                <th> Type </th>
            </tr>
        </thead>

        <tbody>
            @foreach ($closedCounterTills as $counterUpdateDeclarationAttemptPayments)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ $counterUpdateDeclarationAttemptPayments['happened_at'] }}
                    </td>
                    <td>
                        {{ $counterUpdateDeclarationAttemptPayments['type'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
