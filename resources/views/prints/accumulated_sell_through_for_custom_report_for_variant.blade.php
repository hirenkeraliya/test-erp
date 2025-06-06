<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Accumulated Sales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>{{ $company->name }} ({{ $company->code }})</h4>

    <h4><strong>Accumulated Sales Through Custom Report</strong></h4>

    <p>Accumulated Sales Through Report Till Date: {{ $date }}</p>
    <p>{{ $locations }}</p>

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($mainColumns as $mainColumn)
                    <th>{{ $mainColumn }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($preparedData as $record)
                <tr>
                    <td><img src="{{ $record['image'] }}" width="40" /></td>
                    <td>{{ $record['name'] }}</td>
                    <td>{{ $record['price'] }}</td>
                    <td>
                        <table class="table table-bordered bordered">
                            <thead>
                                <tr>
                                    <th></th>
                                    @foreach (['Received', 'Sold', 'Returned', 'Balance'] as $metric)
                                        <th>{{ $metric }}</th>
                                        @foreach ($columns as $col)
                                            @if ($col !== 'total')
                                                <th>{{ $col }}</th>
                                            @endif
                                        @endforeach
                                    @endforeach
                                    <th>Sell Through (%)</th>
                                </tr>                               
                            </thead>
                            <tbody>
                                @php 
                                    $total = 0
                                @endphp
                                @foreach ($record['variants'] as $variantName => $variantData)
                                    <tr>
                                        <td>
                                            @if ($variantName === 'grand_total')
                                                Total
                                            @else
                                                {{ $variantName }}
                                            @endif
                                        </td>

                                        @foreach ($columns as $col)
                                            <td>
                                                {{ is_array($variantData['received'][$col] ?? null)
                                                    ? $variantData['received'][$col]['value']
                                                    : ($variantData['received'][$col] ?? 0) }}
                                            </td>
                                        @endforeach

                                        @foreach ($columns as $col)
                                            <td>
                                                {{ is_array($variantData['sold'][$col] ?? null)
                                                    ? $variantData['sold'][$col]['value']
                                                    : ($variantData['sold'][$col] ?? 0) }}
                                            </td>
                                        @endforeach

                                        @foreach ($columns as $col)
                                            <td>
                                                {{ is_array($variantData['returned'][$col] ?? null)
                                                    ? $variantData['returned'][$col]['value']
                                                    : ($variantData['returned'][$col] ?? 0) }}
                                            </td>
                                        @endforeach

                                        @foreach ($columns as $col)
                                            <td>
                                                {{ is_array($variantData['balance'][$col] ?? null)
                                                    ? $variantData['balance'][$col]['value']
                                                    : ($variantData['balance'][$col] ?? 0) }}
                                            </td>
                                        @endforeach
                                        
                                        @foreach ($columns as $col)
                                            @php 
                                                $total += is_array($variantData['accumulated_sell_through'][$col] ?? 0)
                                                    ? $variantData['accumulated_sell_through'][$col]['value']
                                                    : ($variantData['accumulated_sell_through'][$col] ?? 0)
                                            @endphp
                                            
                                            @if($col === 'total')
                                                <td>
                                                    {{ $total }}
                                                </td>
                                            @endif
                                        @endforeach
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
