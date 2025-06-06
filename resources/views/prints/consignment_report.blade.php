<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consignment Report</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <div class="date-display">
        <h4>
            Consignment Report
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
                            {{ ucfirst(str_replace('_', ' ', $column)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($products as $product)
                    <tr class="page-break-inside-avoid">
                        @foreach($columns as $column)
                            @if ($column === 'unit_sold' || $column === 'price' || $column === 'total' || $column === 'commission')
                                <td class="text-right">{{ $product[$column] }}</td>
                            @else
                                <td>{{ $product[$column] }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                <tr>
                    <td colspan="13" class="text-center"> No Record Found</td>
                </tr>
            @endforelse
    </div>
</body>

</html>
