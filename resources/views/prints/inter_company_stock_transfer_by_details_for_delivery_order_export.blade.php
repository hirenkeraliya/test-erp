<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Delivery Order (By Details)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Delivery Order Report" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Location: <strong>{{ $location['name'] }} ({{ $location['code']}})</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-center"> Date</th>
                <th class="text-center">Delivery Order Number</th>
                <th class="text-center">Sales Order Number</th>
                <th class="text-center">Purchase Order Number</th>
                <th class="text-center">Invoice Number</th>
                <th class="text-center">Status</th>
                <th class="text-center">Main Quantity</th>
                <th class="text-center">Main Received Quantity</th>
                <th class="text-center">Remark</th>
                <th class="text-center">Product Name</th>
                <th class="text-center">Product Article Number</th>
                <th class="text-center">Sub Quantity</th>
                <th class="text-center">Product UPC</th>
                <th class="text-center">Product Color</th>
                <th class="text-center">Product Size</th>
                <th class="text-center">Quantity</th>
                @if ($displayPurchaseCost)
                    <th class="text-right">Purchase Cost</th>
                    <th class="text-right">Total Purchase Cost({{ $currencySymbol }})</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse($interCompanyDeliveryOrdersData as $deliveryOrderData)
                @if(array_key_exists('transfer_from_and_to', $deliveryOrderData))
                    @foreach($deliveryOrderData['transfer_from_and_to'] as $key => $product)
                        @if(array_key_exists('color_wise_products', $product))
                            @foreach($product['color_wise_products'] as $key => $colorWiseProduct)
                                <tr>
                                    <td>{{ $deliveryOrderData['date'] }}</td>
                                    <td>{{ $deliveryOrderData['delivery_order_number'] }}</td>
                                    <td>{{ $deliveryOrderData['sales_order_number'] }}</td>
                                    <td>{{ $deliveryOrderData['purchase_order_number'] }}</td>
                                    <td>{{ $deliveryOrderData['invoice_number'] }}</td>
                                    <td>{{ $deliveryOrderData['status'] }}</td>
                                    <td>{{ $deliveryOrderData['quantity'] }}</td>
                                    <td>{{ $deliveryOrderData['received_quantity'] }}</td>
                                    <td>{{ $deliveryOrderData['remark'] }}</td>
                                    <td>{{ $product['name'] }}</td>
                                    <td>{{ $product['article_number'] }}</td>
                                    <td>{{ $product['total_quantity'] }}</td>
                                    <td>{{ $colorWiseProduct['upc'] }}</td>
                                    <td>{{ $colorWiseProduct['color'] }}</td>
                                    <td>{{ $colorWiseProduct['size'] }}</td>
                                    <td>{{ $colorWiseProduct['transfer_quantity'] }}</td>
                                    @if ($displayPurchaseCost)
                                    <td>{{ $colorWiseProduct['purchase_cost'] }}</td>
                                    <td>{{ $colorWiseProduct['total_purchase_cost'] }}</td>
                                    @endif
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
