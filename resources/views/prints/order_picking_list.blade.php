<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Order Packaging</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header reportName="Order Picking List" :date="$date" :company="$company" reportType="" />

    <div class="mb-2">Picking List Number: <b>{{ $pickingListNumber }}</b></div>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-left"> Date</th>
                    <th class="text-left">Receipt Number</th>
                    <th class="text-left">Member</th>
                    <th class="text-right">Quantity</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orders as $order)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $order['date'] === 'Total' ? 'text-bold' : '' }}">{{ $order['date'] }}</td>
                        <td >{{ $order['receipt_number'] }}</td>
                        <td >{{ $order['member'] }}</td>
                        <td class="text-right">{{ $order['total_quantity'] }}</td>
                    </tr>
                    @if ($order['order_items'])
                    <tr class="page-break-inside-avoid">
                        <td></td>
                        <td colspan="2">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="border-top-none">Upc</th>
                                        <th class="border-top-none">Article number</th>
                                        <th class="border-top-none">Name</th>
                                        @if ($productVariant)
                                            <th class="border-top-none">Attributes</th>
                                        @else
                                            <th class="border-top-none">Color</th>
                                            <th class="border-top-none">Size</th>
                                        @endif
                                        <th class="text-right border-top-none">Quantity</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($order['order_items'] as $key => $product)
                                        <tr>
                                            <td class="border-top-none">{{ $product['upc'] }}</td>
                                            <td class="border-top-none">{{ $product['article_number'] }}</td>
                                            <td class="border-top-none">{{ $product['name'] }}</td>
                                            @if ($productVariant)
                                                <td class="border-top-none">{{ $product['attributes'] }}</td>
                                            @else
                                                <td class="border-top-none">{{ $product['color'] }}</td>
                                                <td class="border-top-none">{{ $product['size'] }}</td>
                                            @endif
                                            <td class="text-right border-top-none">{{ $product['quantity'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td></td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
</body>
</html>
