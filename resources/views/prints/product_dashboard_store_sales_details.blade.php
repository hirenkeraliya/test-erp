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

    <div class="date-display">
        <h4>
            Product Location Sales Details
        </h4>

        <p>
            Date: {{ $date }}
        </p>

        <p>
            Selected Date: {{ $selectedDate }}
        </p>

        <p>
            Brand: {{ $brandName }}
        </p>

        <p>
            Location: {{ $locationName }}
        </p>

        <p>
            Filter Type: {{ $filterType }}
        </p>
    </div>

    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">
                        Name
                    </th>

                    <th class="text-center">
                        Sales Count
                    </th>

                    <th class="text-center">
                        Sales
                    </th>

                    <th class="text-center">
                        Units Sold
                    </th>

                    <th class="text-center">
                        Unit Per Transaction
                    </th>

                    <th class="text-center">
                        Average Transaction Value
                    </th>

                </tr>
            </thead>

            <tbody>
                @forelse ($salesData as $sales)
                    <tr class="page-break-inside-avoid">
                        <td>{{ $sales['name'] }}</td>
                        <td>{{ $sales['sales_count'] }}</td>
                        <td> {{ $currencySymbol }}@currencyFormat($sales['total_sales']) </td>
                        <td>{{ $sales['total_units_sold'] }}</td>
                        <td>{{ $sales['upt'] }}</td>
                        <td>{{ $currencySymbol }}@currencyFormat($sales['atv'])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse
                <tr class="page-break-inside-avoid text-bold">
                    <td>
                        <b>
                            {{ $totalData['name'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $totalData['sales_count'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $totalData['total_sales'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $totalData['total_units_sold'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $totalData['upt'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $totalData['atv'] }}
                        </b>
                    </td>

                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
