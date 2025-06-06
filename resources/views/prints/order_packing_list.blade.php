<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Order Packing List</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header reportName="Order Picking List" :date="$date" :company="$company" reportType="" />

    <div class="mb-2">Picking List Number: <b>{{ $pickingListNumber }}</b></div>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-left">Name</th>
                    <th class="text-left">Upc</th>
                    <th class="text-left">Article number</th>
                    @if ($productVariant)
                        <th class="text-left">Attributes</th>
                    @else
                        <th class="text-left">Color</th>
                        <th class="text-left">Size</th>
                    @endif
                    <th class="text-right">Quantity</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orderItems as $orderItem)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $orderItem['name'] === 'Total' ? 'text-bold' : '' }}">{{ $orderItem['name'] }}</td>
                        <td >{{ $orderItem['upc'] }}</td>
                        <td >{{ $orderItem['article_number'] }}</td>
                        @if ($productVariant)
                            <td >{{ $orderItem['attributes'] }}</td>
                        @else
                            <td >{{ $orderItem['color'] }}</td>
                            <td >{{ $orderItem['size'] }}</td>
                        @endif
                        <td class="text-right">{{ $orderItem['quantity'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
</body>
</html>
