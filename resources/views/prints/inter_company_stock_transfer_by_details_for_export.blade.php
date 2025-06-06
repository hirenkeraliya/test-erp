<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Transfer (By Details)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Transfer Report" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Show Purchase Cost: <strong>{{ $displayPurchaseCost ? 'Yes' : 'No'}}</strong> </p>
        <p> Location: <strong>{{ $location['name'] }} ({{ $location['code']}})</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-center">Transfer Date</th>
                <th class="text-center">Reference Number</th>
                <th class="text-center">Order Number</th>
                <th class="text-center">External Order Number</th>
                <th class="text-center">Transfer Type</th>
                <th class="text-center">Status</th>
                <th class="text-center">Main Quantity</th>
                @if($displayPurchaseCost)
                    <th class="text-center">Main Price</th>
                @endif
                <th class="text-center">Main Package Type</th>
                <th class="text-center">Remark</th>
                <th class="text-center">Product Name</th>
                <th class="text-center">Product Article Number</th>
                <th class="text-center">Sub Quantity</th>
                <th class="text-center">Sub Package Quantity</th>
                <th class="text-center">Product UPC</th>
                <th class="text-center">Product Color</th>
                <th class="text-center">Product Size</th>
                <th class="text-center">Quantity</th>
                <th class="text-center">Received Quantity</th>
                <th class="text-center">Package Type</th>
            </tr>
        </thead>

        <tbody>
            @forelse($interCompanyStockTransfersData as $stockTransferData)
                @if(array_key_exists('transfer_from_and_to', $stockTransferData))
                    @foreach($stockTransferData['transfer_from_and_to'] as $key => $product)
                        @if(array_key_exists('color_wise_products', $product))
                            @foreach($product['color_wise_products'] as $key => $colorWiseProduct)
                                <tr>
                                    <td>{{ $stockTransferData['transfer_date'] }}</td>
                                    <td>{{ $stockTransferData['reference_number'] }}</td>
                                    <td>{{ $stockTransferData['order_number'] }}</td>
                                    <td>{{ $stockTransferData['external_order_number'] }}</td>
                                    <td>{{ $stockTransferData['transfer_type'] }}</td>
                                    <td>{{ $stockTransferData['status'] }}</td>
                                    <td>{{ $stockTransferData['quantity'] }}</td>
                                    @if($displayPurchaseCost)
                                        <td>{{ $stockTransferData['total_purchase_cost'] }}</td>
                                    @endif
                                    <td>
                                        @if (array_key_exists('package_type', $stockTransferData) && $stockTransferData['package_type'])
                                            @foreach ($stockTransferData['package_type'] as $packageTypeName => $packageTypeQuantity)
                                                @if ($packageTypeName !== 'N/A')
                                                    {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                @endif
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>{{ $stockTransferData['remark'] }}</td>
                                    <td>{{ $product['name'] }}</td>
                                    <td>{{ $product['article_number'] }}</td>
                                    <td>{{ $product['total_quantity'] }}</td>
                                    <td>
                                        @if(array_key_exists('total_package_quantity', $product))
                                            @foreach ($product['total_package_quantity'] as $packageTypeName => $packageTypeQuantity)
                                                @if ($packageTypeName !== 'N/A')
                                                    {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                @endif
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>{{ $colorWiseProduct['upc'] }}</td>
                                    <td>{{ $colorWiseProduct['color'] }}</td>
                                    <td>{{ $colorWiseProduct['size'] }}</td>
                                    <td>{{ $colorWiseProduct['quantity'] }}</td>
                                    <td>{{ $colorWiseProduct['transferred_quantity'] }}</td>
                                    <td>
                                        @if (array_key_exists('package_type', $colorWiseProduct) && $colorWiseProduct['package_type'])
                                            @foreach ($colorWiseProduct['package_type'] as $packageTypeName => $packageTypeQuantity)
                                                @if ($packageTypeName !== 'N/A')
                                                    {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                @endif
                                            @endforeach
                                        @endif
                                    </td>

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
