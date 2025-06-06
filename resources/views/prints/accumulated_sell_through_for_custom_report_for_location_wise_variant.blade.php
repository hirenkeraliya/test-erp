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
                    @if(array_key_exists('variants', $record))
                        <tr>
                            <th colspan="2"> {{ $record['name'] }} </th>
                            @foreach($locationColumns as $locationColumn)
                                @foreach($columns as $column)
                                    <td class="text-right"> {{ $column }} </td>
                                @endforeach
                            @endforeach                                    @foreach($columns as $column)
                                <td class="text-right"> {{ $column }} </td>
                            @endforeach
                            <td class="text-right"> Sell Through (%) </td>
                        </tr>

                        @foreach($record['variants'] as $variantName => $variant)
                            @if($variantName !== 'totals')
                                <tr>
                                    <td colspan="2"> {{ $variantName }} </td>
                                    @foreach($locationColumns as $locationColumn)
                                        @foreach($columns as $columnKey => $column)
                                            @php
                                                $total = 0;
                                                foreach($variantColumns as $variantColumn) {
                                                    if (isset($variant[$locationColumn][$variantColumn][$columnKey])) {
                                                        $total += $variant[$locationColumn][$variantColumn][$columnKey];
                                                    }
                                                }
                                            @endphp
                                            <td class="text-right">{{ $total }}</td>
                                        @endforeach
                                    @endforeach
                                    @php
                                        $totalReceived = $totalSold = $totalReturned = $totalBalance = 0;
                                        foreach($locationColumns as $locationColumn) {
                                            foreach($variantColumns as $variantColumn) {
                                                if (isset($variant[$locationColumn][$variantColumn])) {
                                                    $totalReceived += $variant[$locationColumn][$variantColumn]['received'] ?? 0;
                                                    $totalSold += $variant[$locationColumn][$variantColumn]['sold'] ?? 0;
                                                    $totalReturned += $variant[$locationColumn][$variantColumn]['returned'] ?? 0;
                                                    $totalBalance += $variant[$locationColumn][$variantColumn]['balance'] ?? 0;
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="text-right">{{ $totalReceived }}</td>
                                    <td class="text-right">{{ $totalSold }}</td>
                                    <td class="text-right">{{ $totalReturned }}</td>
                                    <td class="text-right">{{ $totalBalance }}</td>
                                    <td class="text-right">
                                        @if($totalReceived > 0)
                                            {{ number_format((($totalSold - $totalReturned) * 100 / $totalReceived), 2) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>

                                @foreach($variantColumns as $variantColumn)
                                    <tr>
                                        <td></td>
                                        <td>{{ $variantColumn }}</td>
                                        @foreach($locationColumns as $locationColumn)
                                            @foreach($columns as $columnKey => $column)
                                                <td class="text-right">
                                                    {{ $variant[$locationColumn][$variantColumn][$columnKey] ?? 0 }}
                                                </td>
                                            @endforeach
                                        @endforeach
                                        @php
                                            $totalReceived = $totalSold = $totalReturned = $totalBalance = 0;
                                            foreach($locationColumns as $locationColumn) {
                                                if (isset($variant[$locationColumn][$variantColumn])) {
                                                    $totalReceived += $variant[$locationColumn][$variantColumn]['received'] ?? 0;
                                                    $totalSold += $variant[$locationColumn][$variantColumn]['sold'] ?? 0;
                                                    $totalReturned += $variant[$locationColumn][$variantColumn]['returned'] ?? 0;
                                                    $totalBalance += $variant[$locationColumn][$variantColumn]['balance'] ?? 0;
                                                }
                                            }
                                        @endphp
                                        <td class="text-right">{{ $totalReceived }}</td>
                                        <td class="text-right">{{ $totalSold }}</td>
                                        <td class="text-right">{{ $totalReturned }}</td>
                                        <td class="text-right">{{ $totalBalance }}</td>
                                        <td class="text-right">
                                            @if($totalReceived > 0)
                                                {{ number_format((($totalSold - $totalReturned) * 100 / $totalReceived), 2) }}
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
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
                                {{ $preparedRecords['final_total']['summary_grand_total'][$columnKey] ?? 0 }}
                            </b>
                        </th>
                    @endforeach
                    <th class="text-right">
                        <b>
                            {{ $preparedRecords['final_total']['summary_grand_total']['sell_through'] ?? '0.00' }}
                        </b>
                    </th>
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
