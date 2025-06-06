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
    <x-report-header :company="$company" reportName="Stock Transfer Discrepancy Report" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        @if (isset($location))
            <p> Location: <strong>{{ $location['name'] }} ({{ $location['code']}})</strong> </p>
        @endif
        <p> Records From Selected Date: <strong>{{ $dateSelectionType }}</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-center">Date ( {{ $displayDateSelectionType }} ) </th>
                <th class="text-center">Reference Number</th>
                <th class="text-center">Transfer Number</th>
                <th class="text-center">Transfer Type</th>
                <th class="text-center">Status</th>
                <th class="text-center">Location</th>
                <th class="text-center">Main Quantity</th>
                <th class="text-center">Main Received Quantity</th>
                <th class="text-center">Main Discrepancy Quantity</th>
                <th class="text-center">Main Package Type</th>
                <th class="text-center">Product Name</th>
                <th class="text-center">Product Article Number</th>
                <th class="text-center">Sub Quantity</th>
                <th class="text-center">Sub Received Quantity</th>
                <th class="text-center">Sub Discrepancy Quantity</th>
                <th class="text-center">Sub Package Quantity</th>
                <th class="text-center">Product UPC</th>
                @if(config('app.product_variant'))
                    <th class="text-center">Attributes</th>
                @else
                    <th class="text-center">Product Color</th>
                    <th class="text-center">Product Size</th>
                @endif
                <th class="text-center">Quantity</th>
                <th class="text-center">Received Quantity</th>
                <th class="text-center">Discrepancy Quantity</th>
                <th class="text-center">Package Type</th>
            </tr>
        </thead>

        <tbody>
            @forelse($stockTransfersData as $stockTransferData)
                @foreach($stockTransferData['stock_transfers'] as $stockTransfer)
                    @if(array_key_exists('transfer_from_and_to', $stockTransfer))
                        @foreach($stockTransfer['transfer_from_and_to'] as $key => $product)
                            @if(array_key_exists('color_wise_products', $product))
                                @foreach($product['color_wise_products'] as $key => $colorWiseProduct)
                                    <tr>
                                        <td>{{ $stockTransfer['transfer_date'] }}</td>
                                        <td>{{ $stockTransfer['reference_number'] }}</td>
                                        <td>{{ $stockTransfer['transfer_number'] }}</td>
                                        <td>{{ $stockTransfer['transfer_type'] }}</td>
                                        <td>{{ $stockTransfer['status'] }}</td>
                                        <td>{{ $stockTransfer['receiver_location'] }}</td>
                                        <td>{{ $stockTransfer['quantity'] }}</td>
                                        <td>{{ $stockTransfer['received_quantity'] }}</td>
                                        <td>{{ $stockTransfer['discrepancy_quantity'] }}</td>
                                        <td>
                                            @if (array_key_exists('package_type', $stockTransfer) && $stockTransfer['package_type'])
                                                @foreach ($stockTransfer['package_type'] as $packageTypeName => $packageTypeQuantity)
                                                    @if ($packageTypeName !== 'N/A')
                                                        {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $product['name'] }}</td>
                                        <td>{{ $product['article_number'] }}</td>
                                        <td>{{ $product['total_quantity'] }}</td>
                                        <td>{{ $product['total_received_quantity'] }}</td>
                                        <td>{{ $product['total_discrepancy_quantity'] }}</td>
                                        <td>
                                            @if ($product['total_package_quantity'])
                                                @foreach ($product['total_package_quantity'] as $packageTypeName => $packageTypeQuantity)
                                                    @if ($packageTypeName !== 'N/A')
                                                        {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $colorWiseProduct['upc'] }}</td>
                                        @if(config('app.product_variant'))
                                            <td>{{ $colorWiseProduct['attributes'] }}</td>
                                        @else
                                            <td>{{ $colorWiseProduct['color'] }}</td>
                                            <td>{{ $colorWiseProduct['size'] }}</td>
                                        @endif
                                        <td>{{ $colorWiseProduct['quantity'] }}</td>
                                        <td>{{ $colorWiseProduct['received_quantity'] }}</td>
                                        <td>{{ $colorWiseProduct['discrepancy_quantity'] }}</td>
                                        <td>{{ $colorWiseProduct['package_type'] }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
