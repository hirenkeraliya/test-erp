<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Collection By Month And Brand</title>

    <style>
        td {
            border: 1px solid;
        }

        th {
            border-top: 1px solid;
            border-left: 1px solid;
            border-right: 1px solid;
        }
    </style>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Sales Collection By Summary Month And Brand" reportType="By Summary Month And Brand" :date="$date" :dateRange="$dateRange" />
    @if($excludeByEInvoiceFilter !== null)
        <p>Exclude By E-Invoice Generated : <strong> {{ $excludeByEInvoiceFilter ? 'Yes' : 'No' }}</strong></p>
    @endif

    <table class="table">
        @forelse ($brandLocationsSalesCollection as $brandLocationsSaleCollection)
            <thead>
                <tr>
                    <th colspan="{{ count($columns) }}" class="text-left" style="border: none; font-size:15px;">
                        <h4>
                            Brand: {{ $brandLocationsSaleCollection['brand_name'] }}
                        </h4>
                    </th>
                </tr>

                <tr>
                    @foreach ($columns as $column)
                        <th class="text-center">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($brandLocationsSaleCollection['locations'] as $saleCollectionData)
                    <tr class="page-break-inside-avoid">
                        @foreach ($columns as $key => $column)
                            @if ($column === 'Location Name')
                                <td style="width: 450px !important;">{{ $saleCollectionData['location_name'] }}</td>
                            @elseif ($column === 'Total')
                                <td class="text-right">@currencyFormat($saleCollectionData['total'])</td>
                            @elseif (array_key_exists($key, $saleCollectionData))
                                <td class="text-right"> @currencyFormat($saleCollectionData[$key])</td>
                            @else
                                <td class="text-right">0.00</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No Records</td>
                    </tr>
                @endforelse

            </tbody>
        @empty
            <tr>
                <td colspan="5" class="text-center">No Records</td>
            </tr>
        @endforelse

        <tr>
            <td colspan="{{ count($columns) }}" class="text-center" style="border: none;"> &nbsp;</td>
        </tr>

        <tr>
            @if (count($grandTotal) > 2)
                @foreach ($columns as $key => $column)
                    @if ($column === 'Location Name')
                        <td>{{ $grandTotal['location_name'] }}</td>
                    @elseif ($column === 'Total')
                        <td class="text-right">@currencyFormat($grandTotal['total'])</td>
                    @elseif (array_key_exists($key, $grandTotal))
                        <td class="text-right"> @currencyFormat($grandTotal[$key])</td>
                    @else
                        <td class="text-right">0.00</td>
                    @endif
                @endforeach
            @endif
        </tr>
    </table>
</body>

</html>
