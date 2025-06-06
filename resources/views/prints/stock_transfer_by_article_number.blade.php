<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Transfer(By Article No)</title>
</head>
<body class="arial-font-custom-report">
    <table>
        <tr>
            <td style="width: 550px;">
                <h4>
                    {{ $company->name }} ( {{ $company->code }} )
                </h4>

                <h4>
                    <strong>Stock Transfer (ArticleNumber)</strong>
                </h4>

                <p>
                    Date: {{ $date }}
                </p>
            </td>
            <td style="width: 550px;">
                <h3> {{ $location['name'] }} ({{ $location['code']}}) </h3>

                <p>
                    from {{ $dateRange[0] }} to {{ $dateRange[1] }}
                </p>
            </td>
        </tr>
    </table>

    <table class="table table-bordered">
        <thead >
            <tr>
                @foreach($columns as $column)
                    @if($column === 'Price')
                        <th class="text-center">{{ $displayTotal ? $column : ''}}</th>
                    @else
                        <th class="text-center">{{ $column }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($stockTransfersData as $stockTransferData)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['upc'] }}</td>
                    <td class="{{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['article_number'] }}</td>
                    <td class="{{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $stockTransferData['location_name'] }}</td>
                    <td class="{{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['name'] }}</td>
                    <td class="mt-2 {{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['color'] }}</td>
                    <td class="mt-2 {{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['size'] }}</td>
                    <td class="mt-2 text-center {{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                    @if($displayTotal)
                        <td class="mt-2 text-right {{ $stockTransferData['upc'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['total_price'] }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
