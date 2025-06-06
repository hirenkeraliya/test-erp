<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/stock-transfer-print.css') }}">
    <title>Stock Summary In By Brand</title>

    <style>
        table {
            font-size: 12px;
        }

        td { display: table-cell; margin: 10px 15px; padding: 10px;}

        .no-border {
            border: 0px !important;
        }
    </style>
</head>
<body class="arial-font-custom-report">
    <table>
        <tr>
            <td style="width: 550px;">
                <h4>
                    {{ $company->name }} ( {{ $company->code }} )
                </h4>

                <h4>
                    <strong>Stock Summary In By Brand</strong>
                </h4>

                <p>
                    from {{ $dateRange[0] }} to {{ $dateRange[1] }}
                </p>

                <p>
                    Date: {{ $date }}
                </p>
                <h3> {{ $locationName }} </h3>
            </td>
        </tr>
    </table>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-center">Article No</th>
                <th class="text-center">Location</th>
                <th class="text-center">Description</th>
                <th class="text-center mt-2" >Qty</th>
                @if($displayTotal)
                    <th class="text-center">Price</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse($stockTransferProducts as $stockTransferProduct)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $stockTransferProduct['product']['selection'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['product']['selection'] }}</td>
                    <td class="{{ $stockTransferProduct['product']['selection'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['product']['location'] }}</td>
                    <td class="{{ $stockTransferProduct['product']['selection'] === 'Total' ? 'text-bold' : ''}}">
                        {{ $stockTransferProduct['product']['description'] }}
                        @if(array_key_exists('items',$stockTransferProduct))
                            <table class="table mt-2">
                                <thead>
                                    <tr>
                                        <th>Upc</th>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Brand</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                @foreach($stockTransferProduct['items'] as $items)
                                    @foreach($items as $item)
                                        <tbody>
                                            <tr class="page-break-inside-avoid">
                                                <td rowspan="2" width="100" class="no-border">{{ $item['product_no'] }}</td>
                                                <td rowspan="2" width="100" class="no-border">{{ $item['color'] }}</td>
                                                <td rowspan="2" width="100" class="no-border">{{ $item['size'] }}</td>
                                                <td rowspan="2" width="100" class="no-border">{{ $item['brand'] }}</td>
                                                <td rowspan="2" width="100" class="no-border">{{ $item['qty'] }}</td>
                                            </tr>
                                        </tbody>
                                    @endforeach
                                @endforeach
                            </table>
                        @endif
                    </td>
                    <td class="text-center mt-2 {{ $stockTransferProduct['product']['selection'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['product']['qty'] }}</td>
                    @if($displayTotal)
                        <td class="text-right mt-2 {{ $stockTransferProduct['product']['selection'] === 'Total' ? 'text-bold' : ''}}">@currencyFormat($stockTransferProduct['product']['total_price'])</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
