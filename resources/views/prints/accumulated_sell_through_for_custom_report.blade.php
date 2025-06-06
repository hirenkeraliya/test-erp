<!DOCTYPE html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Accumulated Sales</title>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Accumulated Sales Through Custom Report</strong>
    </h4>

    <p>
        Accumulated Sales Through Report Till Date : {{ $date }}
    </p>

    <p>
        {{ $locations }}
    </p>

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($mainColumns as $mainColumn)
                    <th> {{ $mainColumn }} </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($preparedData as $item)
                <tr>
                    <td>  </td>
                    <td> {{ $item['name'] }} </td>
                    <td> {{ $item['price'] }} </td>
                    <td>
                        <table class="table table-bordered bordered">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th colspan="{{ count($columns) }}">
                                        <b> Received </b>
                                    </th>
                                    <th colspan="{{ count($columns) }}">
                                        <b> Sold </b>
                                    </th>
                                    <th colspan="{{ count($columns) }}">
                                        <b> Returned </b>
                                    </th>
                                    <th colspan="{{ count($columns) }}">
                                        <b> Balance </b>
                                    </th>
                                    <th colspan="{{ count($columns) }}">
                                        <b> Sell Through (%) </b>
                                     </th>
                                </tr>
                            </thead>

                            <thead>
                                <tr>
                                    <th>Color</th>
                                    @foreach ($columns as $column)
                                        <th> {{ $column }} </th>
                                    @endforeach
                                    @foreach ($columns as $column)
                                        <th> {{ $column }} </th>
                                    @endforeach
                                    @foreach ($columns as $column)
                                        <th> {{ $column }} </th>
                                    @endforeach
                                    @foreach ($columns as $column)
                                        <th> {{ $column }} </th>
                                    @endforeach
                                    <th> {{ $column }} </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($item['colors'] as $key => $colorWise)
                                    <tr>
                                        <td>
                                            @if ($key === 'grand_total')
                                                Total
                                            @else
                                                {{ $key }}
                                            @endif
                                        </td>

                                        @foreach ($columns as $column)
                                            @if ($column === 'total')
                                                <td>
                                                    <b> {{ $colorWise['received'][$column] }} </b>
                                                </td>
                                            @else
                                                <td>
                                                    {{ $colorWise['received'][$column]['received'] }}
                                                </td>
                                            @endif
                                        @endforeach

                                        @foreach ($columns as $column)
                                            @if ($column === 'total')
                                                <td>
                                                    <b> {{ $colorWise['sold'][$column] }} </b>
                                                </td>
                                            @else
                                                <td>
                                                    {{ $colorWise['sold'][$column]['units_sold'] }}
                                                </td>
                                            @endif
                                        @endforeach

                                        @foreach ($columns as $column)
                                            @if ($column === 'total')
                                                <td>
                                                    <b> {{ $colorWise['returned'][$column] }} </b>
                                                </td>
                                            @else
                                                <td>
                                                    {{ $colorWise['returned'][$column]['units_returned'] }}
                                                </td>
                                            @endif
                                        @endforeach

                                        @foreach ($columns as $column)
                                            @if ($column === 'total')
                                                <td>
                                                    <b> {{ $colorWise['balance'][$column] }} </b>
                                                </td>
                                            @else
                                                <td>
                                                    {{ $colorWise['balance'][$column]['balance'] }}
                                                </td>
                                            @endif
                                        @endforeach

                                        <td>
                                            <b> {{ $colorWise['accumulated_sell_through']['accumulated_sell_through'] }} </b>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
