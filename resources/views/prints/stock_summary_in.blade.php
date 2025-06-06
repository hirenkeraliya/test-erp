<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Transfer In By Department</title>

</head>
<body class="arial-font-custom-report">
    <table>
        <tr>
            <td style="width: 550px;">
                <h4>
                    {{ $company->name }} ( {{ $company->code }} )
                </h4>

                <h4>
                    <strong>Stock Transfer In By Department</strong>
                </h4>

                <p>
                    from {{ $dateRange[0] }} to {{ $dateRange[1] }}
                </p>

                <p>
                    Date: {{ $date }}
                </p>
                <h3> {{ $locationName }} </h3>
                @if($departmentName)
                <p> Department: {{ $departmentName }} </p>
                @endif
            </td>
        </tr>
    </table>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-center">Article No</th>
                <th class="text-center">Location</th>
                <th class="text-center">UPC</th>
                <th class="text-center item">Description</th>
                <th class="text-center item">Color</th>
                <th class="text-center item">Size</th>
                <th class="text-center mt-2" >Qty</th>
                @if($displayTotal)
                    <th class="text-center">Price</th>
               @endif
            </tr>
        </thead>

        <tbody>
            @forelse($stockTransferProducts as $stockTransferProduct)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['article_number'] }}</td>
                    <td class="{{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['location'] }}</td>
                    <td class="{{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['upc'] }}</td>
                    <td class="{{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['description'] }}</td>
                    <td class="{{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['color'] }}</td>
                    <td class="{{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['size'] }}</td>
                    <td class="text-center mt-2 {{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferProduct['qty'] }}</td>
                    @if($displayTotal)
                        <td class="text-right mt-2 {{ $stockTransferProduct['article_number'] === 'Total' ? 'text-bold' : ''}}">@currencyFormat($stockTransferProduct['total_price'])</td>
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
