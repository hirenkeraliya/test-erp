<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Order (By Details)</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Order Report" reportType="By Details" :dateRange="$dateRange" :date="$date" />
    <div>
        <p> Location: <strong>{{ $location->getNameWithCode() }}</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                <th class="text-center">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($ordersData as $orderData)
            <tr class="page-break-inside-avoid">
                <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">{{ $orderData['date'] }}</td>
                <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $orderData['bill_reference_number'] }}
                </td>
                <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $orderData['receipt_number'] }}
                </td>
                <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">{{ $orderData['type'] }}</td>
                <td class="mt-2 text-center {{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $orderData['quantity'] }}
                </td>
                <td class="mt-2 text-right {{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $orderData['total_price'] }}
                </td>
            </tr>
            @if (array_key_exists('description', $orderData))
            <tr class="page-break-inside-avoid">
                <td></td>
                <td colspan="3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="border-top-none">Name</th>
                                <th class="border-top-none">Article number</th>
                                <th class="text-right border-top-none">Quantity</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($orderData['description'] as $key => $product)
                            <tr>
                                <td class="border-top-none">{{ $product['name'] }}</td>
                                <td class="border-top-none">{{ $product['article_number'] }}</td>
                                <td class="text-right border-top-none">{{ $product['total_quantity'] }}
                                </td>
                            </tr>
                            @if (array_key_exists('color_wise_products', $product))
                            <tr>
                                <td class="border-top-none"></td>
                                <td colspan="2" class="border-top-none">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="border-top-none">UPC</th>
                                                @if(config('app.product_variant'))
                                                    <th class="border-top-none">Attributes</th>
                                                @else
                                                    <th class="border-top-none">Color</th>
                                                    <th class="border-top-none">Size</th>
                                                @endif
                                                <th class="text-right border-top-none">Quantity</th>
                                                <th class="text-right border-top-none">Rec.Quantity</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($product['color_wise_products'] as $colorWiseProduct)
                                            <tr>
                                                <td class="border-top-none">
                                                    {{ $colorWiseProduct['upc'] }}
                                                </td>
                                                @if(config('app.product_variant'))
                                                    <td class="border-top-none">{{ $colorWiseProduct['attributes'] }}</td>
                                                @else
                                                    <td class="border-top-none">{{ $colorWiseProduct['color'] }}</td>
                                                    <td class="border-top-none">{{ $colorWiseProduct['size'] }}</td>
                                                @endif
                                                <td class="text-right border-top-none">
                                                    {{ $colorWiseProduct['quantity'] }}
                                                </td>
                                                <td class="text-right border-top-none">
                                                    {{ $colorWiseProduct['quantity'] }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td></td>
                <td></td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="6" class="text-center">No Records</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
