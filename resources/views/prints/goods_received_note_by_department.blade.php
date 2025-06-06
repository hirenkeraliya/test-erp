<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Goods Received Note(ByDept.)</title>
</head>
<body class="arial-font-custom-report">
    <table>
        <tr>
            <td style="width: 550px;">
                <h4>
                    {{ $company->name }} ( {{ $company->code }} )
                </h4>

                <h4>
                    <strong>Goods Received Notes (By Department)</strong>
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
                    <th class="text-center">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($goodsReceivedNoteProducts as $goodsReceivedNoteProduct)
                <tr class="page-break-inside-avoid">
                    <td style="width:70px" class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['date'] }}</td>
                    <td style="width:70px" class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['location'] }}</td>
                    <td class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['upc'] }}</td>
                    <td class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['article_number'] }}</td>
                    <td class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['department'] }}</td>
                    <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['name'] }}</td>
                    <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['color'] }}</td>
                    <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['size'] }}</td>
                    <td class="text-center mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['quantity'] }}</td>
                    <td class="text-right mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">{{ $goodsReceivedNoteProduct['total_price'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
