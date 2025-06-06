<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Revenue Location Sales Details</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <div class="date-display">
        <h4>
            Revenue Location Sales Details
        </h4>

        <p>
            Date: {{ $date }}
        </p>

        <p>
            Sales Data From: {{ $dateRange[0] }} To: {{ $dateRange[1] }}
        </p>

        <p>
            Brand: {{ $brandName }}
        </p>
    </div>

    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">
                        Locations (Code)
                    </th>

                    <th class="text-center">
                        Sales ($)
                    </th>

                    <th class="text-center">
                        Sales (Count)
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
                @forelse ($locationSales as $locationSale)
                    <tr class="page-break-inside-avoid">
                        <td>{{ $locationSale['name'] }} {{ $locationSale['code'] }} </td>
                        <td> {{ $currencySymbol }}@currencyFormat($locationSale['total_sales']) </td>
                        <td>{{ $locationSale['sales_count'] }}</td>
                        <td>{{ $locationSale['total_units_sold'] }}</td>
                        <td>{{ $locationSale['unit_per_transaction'] }}</td>
                        <td>{{ $currencySymbol }}@currencyFormat($locationSale['average_transaction_value'])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse
                <tr class="page-break-inside-avoid text-bold">
                    <td>
                        <b>
                            {{ $salesTotalData['name'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $salesTotalData['total_sales'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $salesTotalData['sales_count'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $salesTotalData['total_units_sold'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $salesTotalData['unit_per_transaction'] }}
                        </b>
                    </td>
                    <td>
                        <b>
                            {{ $salesTotalData['average_transaction_value'] }}
                        </b>
                    </td>

                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
