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
        <strong>Accumulated Sales Through Custom Report By Location</strong>
    </h4>

    <p>
        Accumulated Sales Through Report Till Date : {{ $date }}
    </p>

    <p>
        {{ $locations }}
    </p>

    @if (count($preparedRecords) > 0)
        <table class="table table-bordered bordered">
            <thead>
                <tr>
                    <td colspan="2"> Product </td>
                    @foreach($locationColumns as $locationColumn)
                        <td colspan="4" class="text-center"> {{ $locationColumn }} </td>
                    @endforeach
                        <th colspan="5" class="text-center"> Summary </th>
                </tr>
            </thead>

            <tbody>
                @foreach($preparedRecords as $record)
                    @if(array_key_exists('colors', $record))
                        <tr>
                            <th colspan="2"> {{ $record['name'] }} </th>
                            @foreach($locationColumns as $locationColumn)
                                @foreach($columns as $column)
                                    <td class="text-right"> {{ $column }} </td>
                                @endforeach
                            @endforeach
                            @foreach($columns as $column)
                                <td class="text-right"> {{ $column }} </td>
                            @endforeach
                            <td class="text-right"> Sell Through (%) </td>
                        </tr>

                        @foreach($record['colors'] as $colorName => $color)
                            <tr>
                                <td colspan="2"> {{ $colorName }} </td>
                                @if(array_key_exists('color_wise_total', $color))
                                    @foreach($locationColumns as $locationColumn)
                                        @foreach($columns as $columnKey => $column)
                                            <td class="text-right"> {{ $color['color_wise_total'][$locationColumn][$columnKey] }} </td>
                                        @endforeach
                                    @endforeach
                                    @foreach($columns as $columnKey => $column)
                                        <td class="text-right"> {{ $color['color_wise_total']['summary_total'][$columnKey] }} </td>
                                    @endforeach
                                    @if(array_key_exists('sell_through', $color['color_wise_total']['summary_total']))
                                        <td class="text-right">
                                            {{ $color['color_wise_total']['summary_total']['sell_through'] }}
                                        </td>
                                    @endif
                                @endif
                            </tr>

                            @foreach($sizeColumns as $sizeColumn)
                                <tr>
                                    <td></td>
                                    <td> {{ $sizeColumn }} </td>
                                    @foreach($locationColumns as $locationColumn)
                                        @foreach($columns as $columnKey => $column)
                                            <td class="text-right"> {{ $color[$sizeColumn][$locationColumn][$columnKey] }}</td>
                                        @endforeach
                                    @endforeach
                                    @foreach($columns as $columnKey => $column)
                                        <td class="text-right"> {{ $color[$sizeColumn]['summary'][$columnKey] }} </td>
                                    @endforeach
                                    @if(array_key_exists('sell_through', $color[$sizeColumn]['summary']))
                                        <td class="text-right"> {{ $color[$sizeColumn]['summary']['sell_through'] }} </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <th colspan="2" class="text-bold">
                        <b>
                            Grand Total
                        </b>
                    </th>
                    @foreach($locationColumns as $locationColumn)
                        @foreach($columns as $columnKey => $column)
                            <th class="text-right">
                                <b>
                                    {{ $preparedRecords['final_total'][$locationColumn][$columnKey] }}
                                </b>
                            </th>
                        @endforeach
                    @endforeach
                    @foreach($columns as $columnKey => $column)
                        <th class="text-right">
                            <b>
                                {{ $preparedRecords['final_total']['summary_grand_total'][$columnKey] }}
                            </b>
                        </th>
                    @endforeach
                    @if ($preparedRecords && array_key_exists('sell_through', $preparedRecords['final_total']['summary_grand_total']))
                        <th class="text-right">
                            <b>
                                {{ $preparedRecords['final_total']['summary_grand_total']['sell_through'] }}
                            </b>
                        </th>
                    @endif
                </tr>
            </tfoot>
        </table>
    @else
        <h3>
            No Records Found.
        </h3>
    @endif
</body>
</html>
