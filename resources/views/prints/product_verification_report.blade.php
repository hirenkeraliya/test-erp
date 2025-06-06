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
        Genuine Product Verification Report
    </h4>

    <x-pdf-report-header :filterData="$filter_header_data"/>

    <div class="date-display">
        <h4>
            Products
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
                @forelse ($productVerificationData as $product)
                    <tr class="page-break-inside-avoid">
                        @foreach($columns as $column)
                            @if ($column === 'total_sales' || $column === 'total_sale_returns')
                                <td class="text-right">{{ $product[$column] }}</td>
                            @elseif ($column === 'units_sold' || $column === 'units_returned')
                                <td class="text-center">{{ $product[$column] }}</td>
                            @elseif ($column === 'attributes')
                                <td>
                                    @foreach ($product[$column] as $attribute)
                                        {{ $attribute['name'] }}: {{ $attribute['value'] }}<br>
                                    @endforeach
                                </td>
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
            </tbody>
            @if (collect($columns)->intersect(['total_sales', 'total_sale_returns', 'units_sold', 'units_returned'])->isNotEmpty())
                <tr>
                    @foreach ($columns as $column)
                        @if ($column === 'total_sales')
                            <th class="text-right">@currencyFormat($grand_totals['total_amount_sold'])</th>
                        @elseif ($column === 'total_sale_returns')
                            <th class="text-right">@currencyFormat($grand_totals['total_returned_amount'])</th>
                        @elseif ($column === 'units_sold')
                            <th class="text-center">{{ $grand_totals['total_quantity_sold'] }}</th>
                        @elseif ($column === 'units_returned')
                            <th class="text-center">{{ $grand_totals['total_quantity_returned'] }}</th>
                        @else
                            <th></th>
                        @endif
                    @endforeach
                </tr>
            @endif
        </table>
    </div>
</body>

</html>
