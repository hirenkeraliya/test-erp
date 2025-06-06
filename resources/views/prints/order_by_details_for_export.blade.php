<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Order (By Details)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Order Report" reportType="By Details"  :dateRange="$dateRange" :date="$date"  />
    <div>
        <p> Location: <strong>{{ $location->getNameWithCode() }}</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead >
            <tr>
               <th class="text-center">Date</th>
                <th class="text-center"># Reference</th>
                <th class="text-center">Receipt Number</th>
                <th class="text-center">Type</th>
                <th class="text-center">Main Quantity</th>
                <th class="text-center">Main Price</th>
                <th class="text-center">Product Name</th>
                <th class="text-center">Product Article Number</th>
                <th class="text-center">Sub Quantity</th>
                <th class="text-center">Product UPC</th>
                @if(config('app.product_variant'))
                    <th class="text-center">Attributes</th>
                @else
                    <th class="text-center">Product Color</th>
                    <th class="text-center">Product Size</th>
                @endif
                <th class="text-center">Quantity</th>
            </tr>
        </thead>

        <tbody>
            @forelse($ordersData as $orderData)
                @if(array_key_exists('description', $orderData))
                    @foreach($orderData['description'] as $key => $product)
                        @if(array_key_exists('color_wise_products', $product))
                            @foreach($product['color_wise_products'] as $key => $colorWiseProduct)
                                <tr>
                                    <td>{{ $orderData['date'] }}</td>
                                    <td>{{ $orderData['bill_reference_number'] }}</td>
                                    <td>{{ $orderData['receipt_number'] }}</td>
                                    <td>{{ $orderData['type'] }}</td>
                                    <td>{{ $orderData['quantity'] }}</td>
                                    <td>{{ $orderData['total_price'] }}</td>
                                    <td>{{ $product['name'] }}</td>
                                    <td>{{ $product['article_number'] }}</td>
                                    <td>{{ $product['total_quantity'] }}</td>
                                    <td>{{ $colorWiseProduct['upc'] }}</td>
                                    @if(config('app.product_variant'))
                                        <td>{{ $colorWiseProduct['attributes'] }}</td>
                                    @else
                                        <td>{{ $colorWiseProduct['color'] }}</td>
                                        <td>{{ $colorWiseProduct['size'] }}</td>
                                    @endif
                                    <td>{{ $colorWiseProduct['quantity'] }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
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
